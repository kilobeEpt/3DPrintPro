<?php
// ========================================
// Database Backup Script
// Version: 1.0
// ========================================
//
// This script creates timestamped backups of schema and data
// Backups are saved to database/backups/ directory
//
// CLI Usage:
//   php database/backup.php [options]
//
// Options:
//   --schema-only    Backup only schema (no data)
//   --data-only      Backup only data (no schema)
//   --tables=t1,t2   Backup specific tables only
//
// Examples:
//   php database/backup.php
//   php database/backup.php --schema-only
//   php database/backup.php --tables=orders,settings
//
// HTTP Usage:
//   https://your-site.com/database/backup.php?token=SECRET
//
// Security:
//   Set BACKUP_TOKEN in this file for HTTP access
//
// ========================================

// Determine if running from CLI or HTTP
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    // Security token for HTTP access (change this!)
    define('BACKUP_TOKEN', 'CHANGE_ME_FOR_PRODUCTION');
    
    if (!isset($_GET['token']) || $_GET['token'] !== BACKUP_TOKEN) {
        http_response_code(403);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Invalid or missing token',
            'help' => 'Use ?token=YOUR_TOKEN or run from CLI'
        ], JSON_PRETTY_PRINT);
        exit(1);
    }
}

// Load database config
$configPath = __DIR__ . '/../api/config.php';
if (!file_exists($configPath)) {
    $error = [
        'status' => 'ERROR',
        'message' => 'Config file not found',
        'help' => 'Copy api/config.example.php to api/config.php and configure credentials'
    ];
    echo json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}

require_once $configPath;

// Parse CLI arguments
$options = [
    'schema_only' => false,
    'data_only' => false,
    'tables' => null
];

if ($isCli && $argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        if ($argv[$i] === '--schema-only') {
            $options['schema_only'] = true;
        } elseif ($argv[$i] === '--data-only') {
            $options['data_only'] = true;
        } elseif (strpos($argv[$i], '--tables=') === 0) {
            $tables = substr($argv[$i], 9);
            $options['tables'] = explode(',', $tables);
        }
    }
}

// Parse HTTP parameters
if (!$isCli) {
    if (isset($_GET['schema_only'])) $options['schema_only'] = true;
    if (isset($_GET['data_only'])) $options['data_only'] = true;
    if (isset($_GET['tables'])) {
        $options['tables'] = explode(',', $_GET['tables']);
    }
}

$response = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => DB_NAME,
    'host' => DB_HOST,
    'options' => $options,
    'files_created' => []
];

try {
    // Create backups directory if it doesn't exist
    $backupDir = __DIR__ . '/backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
        $response['info'][] = 'Created backups directory';
    }
    
    // Generate timestamp for filename
    $timestamp = date('Y-m-d_H-i-s');
    
    // Connect to database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get list of tables to backup
    if ($options['tables']) {
        $tables = $options['tables'];
        $response['info'][] = 'Backing up specific tables: ' . implode(', ', $tables);
    } else {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $response['info'][] = 'Backing up all ' . count($tables) . ' tables';
    }
    
    // Build mysqldump command
    $dumpFile = $backupDir . '/' . DB_NAME . '_backup_' . $timestamp . '.sql';
    
    $command = sprintf(
        'mysqldump -h %s -u %s -p%s %s %s %s > %s 2>&1',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        $options['schema_only'] ? '--no-data' : '',
        $options['data_only'] ? '--no-create-info' : '',
        escapeshellarg(DB_NAME)
    );
    
    // Add specific tables if requested
    if ($options['tables']) {
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s %s %s %s > %s 2>&1',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            $options['schema_only'] ? '--no-data' : '',
            $options['data_only'] ? '--no-create-info' : '',
            escapeshellarg(DB_NAME),
            implode(' ', array_map('escapeshellarg', $tables)),
            escapeshellarg($dumpFile)
        );
    } else {
        $command .= ' ' . implode(' ', array_map('escapeshellarg', $tables));
    }
    
    // Execute mysqldump
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('mysqldump failed: ' . implode("\n", $output));
    }
    
    if (!file_exists($dumpFile)) {
        throw new Exception('Backup file was not created');
    }
    
    $fileSize = filesize($dumpFile);
    $response['files_created'][] = [
        'filename' => basename($dumpFile),
        'path' => $dumpFile,
        'size' => $fileSize,
        'size_formatted' => formatBytes($fileSize),
        'type' => $options['schema_only'] ? 'schema-only' : ($options['data_only'] ? 'data-only' : 'full')
    ];
    
    // Create a compressed version
    $gzFile = $dumpFile . '.gz';
    if (function_exists('gzencode')) {
        $sqlContent = file_get_contents($dumpFile);
        $gzContent = gzencode($sqlContent, 9);
        file_put_contents($gzFile, $gzContent);
        
        $gzSize = filesize($gzFile);
        $response['files_created'][] = [
            'filename' => basename($gzFile),
            'path' => $gzFile,
            'size' => $gzSize,
            'size_formatted' => formatBytes($gzSize),
            'type' => 'compressed',
            'compression_ratio' => round(($fileSize - $gzSize) / $fileSize * 100, 1) . '%'
        ];
    }
    
    $response['summary'] = sprintf(
        'Backup completed successfully. %d file(s) created.',
        count($response['files_created'])
    );
    
} catch (Exception $e) {
    $response['status'] = 'ERROR';
    $response['error'] = $e->getMessage();
    $response['help'] = [
        'Ensure mysqldump is installed and in PATH',
        'Check database credentials in api/config.php',
        'Verify database exists and is accessible',
        'Check write permissions for database/backups/ directory'
    ];
}

// Output result
$json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $json;

// Exit with appropriate code for CLI
if ($isCli) {
    exit($response['status'] === 'OK' ? 0 : 1);
}

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
