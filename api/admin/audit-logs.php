<?php
// ========================================
// Admin Audit Logs API
// Provides access to admin action logs with filtering and export
// ========================================

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/admin_auth.php';
require_once __DIR__ . '/../helpers/api_response.php';

SecurityHeaders::apply(SecurityHeaders::CONTEXT_API);
SecurityHeaders::handlePreflight();

// Require admin authentication
requireAdminAuth();

use App\Models\AdminActionLog;
use App\Models\AdminUser;

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet();
            break;
            
        case 'DELETE':
            handleDelete();
            break;
            
        default:
            ApiResponse::methodNotAllowed(['GET', 'DELETE']);
    }
    
} catch (Exception $e) {
    ApiResponse::error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGet() {
    // Stats request
    if (isset($_GET['stats'])) {
        $stats = getStats();
        ApiResponse::success($stats);
        return;
    }
    
    // Export request
    if (isset($_GET['export'])) {
        handleExport($_GET['export']);
        return;
    }
    
    // Parse pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(10, intval($_GET['per_page']))) : 50;
    
    // Build query
    $query = AdminActionLog::with('user');
    
    // Apply filters
    if (isset($_GET['user_id'])) {
        if ($_GET['user_id'] === 'null') {
            $query->whereNull('user_id');
        } else {
            $query->where('user_id', $_GET['user_id']);
        }
    }
    
    if (isset($_GET['action'])) {
        $query->where('action', $_GET['action']);
    }
    
    if (isset($_GET['entity_type'])) {
        $query->where('entity_type', $_GET['entity_type']);
    }
    
    if (isset($_GET['date_from'])) {
        $query->where('created_at', '>=', $_GET['date_from'] . ' 00:00:00');
    }
    
    if (isset($_GET['date_to'])) {
        $query->where('created_at', '<=', $_GET['date_to'] . ' 23:59:59');
    }
    
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $query->where(function($q) use ($search) {
            $q->where('ip_address', 'LIKE', "%{$search}%")
              ->orWhere('user_agent', 'LIKE', "%{$search}%")
              ->orWhere('entity_id', 'LIKE', "%{$search}%")
              ->orWhere('payload', 'LIKE', "%{$search}%");
        });
    }
    
    // Order by newest first
    $query->orderBy('created_at', 'desc');
    
    // Paginate
    $total = $query->count();
    $logs = $query->skip(($page - 1) * $perPage)
                  ->take($perPage)
                  ->get();
    
    // Format response
    $data = $logs->map(function($log) {
        return [
            'id' => $log->id,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
                'role' => $log->user->role
            ] : null,
            'action' => $log->action,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'payload' => $log->payload,
            'created_at' => $log->created_at
        ];
    });
    
    $meta = [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'from' => ($page - 1) * $perPage + 1,
        'to' => min($page * $perPage, $total)
    ];
    
    ApiResponse::success($data, $meta);
}

function handleDelete() {
    verifyCsrfToken();
    
    // Delete logs older than specified days
    if (isset($_GET['older_than'])) {
        $days = intval($_GET['older_than']);
        if ($days < 30) {
            ApiResponse::error('Cannot delete logs newer than 30 days', 400);
            return;
        }
        
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $deleted = AdminActionLog::where('created_at', '<', $date)->delete();
        
        // Log the cleanup action
        logAdminAction('cleanup_audit_logs', 'admin_action_log', null, [
            'older_than_days' => $days,
            'deleted_count' => $deleted
        ]);
        
        ApiResponse::success([
            'deleted' => $deleted,
            'before_date' => $date
        ], null, 'Audit logs cleaned up successfully');
        return;
    }
    
    ApiResponse::error('Missing required parameter: older_than', 400);
}

function getStats() {
    $today = date('Y-m-d');
    
    return [
        'total' => AdminActionLog::count(),
        'today' => AdminActionLog::whereDate('created_at', $today)->count(),
        'violations' => AdminActionLog::where('action', 'rate_limit_violation')->count(),
        'unique_ips' => AdminActionLog::distinct('ip_address')->count('ip_address')
    ];
}

function handleExport($format) {
    if ($format !== 'csv') {
        ApiResponse::error('Only CSV export is supported', 400);
        return;
    }
    
    // Build query with filters
    $query = AdminActionLog::with('user');
    
    // Apply same filters as GET
    if (isset($_GET['user_id'])) {
        if ($_GET['user_id'] === 'null') {
            $query->whereNull('user_id');
        } else {
            $query->where('user_id', $_GET['user_id']);
        }
    }
    
    if (isset($_GET['action'])) {
        $query->where('action', $_GET['action']);
    }
    
    if (isset($_GET['entity_type'])) {
        $query->where('entity_type', $_GET['entity_type']);
    }
    
    if (isset($_GET['date_from'])) {
        $query->where('created_at', '>=', $_GET['date_from'] . ' 00:00:00');
    }
    
    if (isset($_GET['date_to'])) {
        $query->where('created_at', '<=', $_GET['date_to'] . ' 23:59:59');
    }
    
    $query->orderBy('created_at', 'desc');
    
    // Limit to 10,000 records for export
    $logs = $query->limit(10000)->get();
    
    // Generate CSV
    $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = __DIR__ . '/../../storage/cache/' . $filename;
    
    $fp = fopen($filepath, 'w');
    
    // UTF-8 BOM for Excel compatibility
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($fp, [
        'ID',
        'Date/Time',
        'User ID',
        'User Name',
        'User Email',
        'Action',
        'Entity Type',
        'Entity ID',
        'IP Address',
        'User Agent',
        'Payload'
    ]);
    
    // Data
    foreach ($logs as $log) {
        fputcsv($fp, [
            $log->id,
            $log->created_at,
            $log->user_id,
            $log->user ? $log->user->name : '',
            $log->user ? $log->user->email : '',
            $log->action,
            $log->entity_type,
            $log->entity_id,
            $log->ip_address,
            $log->user_agent,
            $log->payload
        ]);
    }
    
    fclose($fp);
    
    // Log the export action
    logAdminAction('export_audit_logs', 'admin_action_log', null, [
        'format' => 'csv',
        'records' => count($logs),
        'filename' => $filename
    ]);
    
    // Return download URL (file will be accessible for 1 hour)
    $url = '/storage/cache/' . $filename;
    
    ApiResponse::success([
        'url' => $url,
        'filename' => $filename,
        'records' => count($logs)
    ], null, 'Export generated successfully');
}
