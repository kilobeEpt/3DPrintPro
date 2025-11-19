<?php
/**
 * Orders Export API Endpoint
 * 
 * Handles CSV and PDF exports with signed URL security.
 * 
 * Endpoints:
 * - GET /api/orders/export.php?token=...&sig=... - Execute export
 * - POST /api/orders/export.php - Generate signed export URL
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Services\OrderExportService;
use App\Models\AdminActionLog;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdminAuth();
    verifyCsrfToken();
    
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $type = $input['type'] ?? 'csv';
    if (!in_array($type, ['csv', 'pdf'])) {
        ApiResponse::error('Invalid export type. Must be csv or pdf.', 400);
    }
    
    $filters = $input['filters'] ?? [];
    $fields = $input['fields'] ?? null;
    $expiresIn = $input['expires_in'] ?? 60;
    
    $exportService = new OrderExportService();
    $signedUrl = $exportService->generateSignedUrl($type, $filters, $fields, $expiresIn);
    
    AdminActionLog::log(
        $_SESSION['ADMIN_USER_ID'] ?? null,
        'generate_export_url',
        'order',
        null,
        [
            'type' => $type,
            'filters' => $filters,
            'expires_in' => $expiresIn,
        ]
    );
    
    ApiLogger::info("Generated export URL", [
        'type' => $type,
        'expires_at' => $signedUrl['expires_at']
    ]);
    
    ApiResponse::success([
        'url' => $signedUrl['url'],
        'expires_at' => $signedUrl['expires_at'],
        'type' => $type,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdminAuth();
    
    $token = $_GET['token'] ?? '';
    $signature = $_GET['sig'] ?? '';
    
    if (empty($token) || empty($signature)) {
        ApiResponse::error('Missing token or signature', 400);
    }
    
    $exportService = new OrderExportService();
    $validation = $exportService->validateSignedUrl($token, $signature);
    
    if (!$validation['valid']) {
        ApiLogger::warning("Invalid export token", [
            'error' => $validation['error'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        ApiResponse::error($validation['error'], 403);
    }
    
    $type = $validation['type'];
    $filters = $validation['filters'];
    $fields = $validation['fields'];
    
    try {
        if ($type === 'csv') {
            $content = $exportService->exportCsv($filters, $fields);
            $filename = 'orders_export_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo $content;
        } else {
            $content = $exportService->exportPdf($filters, $fields);
            $filename = 'orders_export_' . date('Y-m-d_His') . '.pdf';
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo $content;
        }
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            AdminActionLog::ACTION_EXPORT,
            'order',
            null,
            [
                'type' => $type,
                'filters' => $filters,
                'file_size' => strlen($content),
            ]
        );
        
        ApiLogger::info("Export downloaded", [
            'type' => $type,
            'size' => strlen($content)
        ]);
        
    } catch (Exception $e) {
        ApiLogger::error("Export failed", [
            'type' => $type,
            'error' => $e->getMessage()
        ]);
        ApiResponse::serverError('Export failed: ' . $e->getMessage());
    }
    
    exit;
}

ApiResponse::error('Method not allowed', 405);
