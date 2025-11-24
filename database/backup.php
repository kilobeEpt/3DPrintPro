<?php
// ========================================
// Database Backup Script v2.0
// Enhanced with rotation, checksums, and scheduling
// ========================================
//
// This script creates timestamped backups of schema and data
// Backups are saved to storage/backups/ directory with rotation
//
// CLI Usage:
//   php database/backup.php [options]
//
// Options:
//   --schema-only    Backup only schema (no data)
//   --data-only      Backup only data (no schema)
//   --tables=t1,t2   Backup specific tables only
//   --retention=N    Keep only N most recent backups (default: 30)
//   --compress       Create compressed version (default: true)
//   --verify         Verify backup integrity after creation
//
// Examples:
//   php database/backup.php
//   php database/backup.php --schema-only
//   php database/backup.php --tables=orders,settings --retention=7
//   php database/backup.php --verify
//
// HTTP Usage (Admin-only):
//   https://your-site.com/database/backup.php (requires admin authentication)
//
// Cron Setup:
//   # Daily backup at 2 AM
//   0 2 * * * cd /path/to/project && php database/backup.php --retention=30 >> logs/backup.log 2>&1
//
//   # Weekly schema-only backup
//   0 3 * * 0 cd /path/to/project && php database/backup.php --schema-only --retention=12 >> logs/backup.log 2>&1
//
// ========================================

// Determine if running from CLI or HTTP
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // HTTP access requires admin authentication
    require_once __DIR__ . '/../api/helpers/admin_auth.php';
    require_once __DIR__ . '/../api/helpers/security_headers.php';
    
    SecurityHeaders::apply(SecurityHeaders::CONTEXT_API);
    requireAdminAuth();
    
    header('Content-Type: application/json; charset=utf-8');
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
    'tables' => null,
    'retention' => 30,
    'compress' => true,
    'verify' => false
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
        } elseif (strpos($argv[$i], '--retention=') === 0) {
            $options['retention'] = intval(substr($argv[$i], 12));
        } elseif ($argv[$i] === '--no-compress') {
            $options['compress'] = false;
        } elseif ($argv[$i] === '--verify') {
            $options['verify'] = true;
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
    if (isset($_GET['retention'])) {
        $options['retention'] = intval($_GET['retention']);
    }
    if (isset($_GET['no_compress'])) {
        $options['compress'] = false;
    }
    if (isset($_GET['verify'])) {
        $options['verify'] = true;
    }
    
    // Log admin action
    if (function_exists('logAdminAction')) {
        logAdminAction('trigger_backup', 'backup', null, $options);
    }
}

$response = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => DB_NAME,
    'host' => DB_HOST,
    'options' => $options,
    'files_created' => [],
    'checksums' => []
];

try {
    // Create backups directory if it doesn't exist
    $backupDir = __DIR__ . '/../storage/backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
        $response['info'][] = 'Created backups directory';
    }
    
    // Generate timestamp for filename
    $timestamp = date('Y-m-d_H-i-s');
    
    // Connect to database for table listing
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
    $type = $options['schema_only'] ? 'schema' : ($options['data_only'] ? 'data' : 'full');
    $dumpFile = $backupDir . '/' . DB_NAME . '_' . $type . '_' . $timestamp . '.sql';
    
    $cmdParts = [
        'mysqldump',
        '-h ' . escapeshellarg(DB_HOST),
        '-u ' . escapeshellarg(DB_USER),
        '-p' . escapeshellarg(DB_PASS),
        '--single-transaction',
        '--routines',
        '--triggers'
    ];
    
    if ($options['schema_only']) {
        $cmdParts[] = '--no-data';
    }
    
    if ($options['data_only']) {
        $cmdParts[] = '--no-create-info';
    }
    
    $cmdParts[] = escapeshellarg(DB_NAME);
    
    // Add specific tables if requested
    if ($options['tables']) {
        foreach ($tables as $table) {
            $cmdParts[] = escapeshellarg($table);
        }
    }
    
    $cmdParts[] = '> ' . escapeshellarg($dumpFile);
    $cmdParts[] = '2>&1';
    
    $command = implode(' ', $cmdParts);
    
    // Execute mysqldump
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('mysqldump failed: ' . implode("\n", $output));
    }
    
    if (!file_exists($dumpFile)) {
        throw new Exception('Backup file was not created');
    }
    
    $fileSize = filesize($dumpFile);
    
    // Calculate checksum
    $checksum = md5_file($dumpFile);
    $response['checksums'][$dumpFile] = $checksum;
    
    $response['files_created'][] = [
        'filename' => basename($dumpFile),
        'path' => $dumpFile,
        'size' => $fileSize,
        'size_formatted' => formatBytes($fileSize),
        'type' => $type,
        'checksum' => $checksum
    ];
    
    // Create checksum file
    file_put_contents($dumpFile . '.md5', $checksum . '  ' . basename($dumpFile));
    
    // Create a compressed version
    if ($options['compress']) {
        $gzFile = $dumpFile . '.gz';
        
        $sqlContent = file_get_contents($dumpFile);
        $gzContent = gzencode($sqlContent, 9);
        file_put_contents($gzFile, $gzContent);
        
        $gzSize = filesize($gzFile);
        $gzChecksum = md5_file($gzFile);
        $response['checksums'][$gzFile] = $gzChecksum;
        
        $response['files_created'][] = [
            'filename' => basename($gzFile),
            'path' => $gzFile,
            'size' => $gzSize,
            'size_formatted' => formatBytes($gzSize),
            'type' => 'compressed',
            'compression_ratio' => round(($fileSize - $gzSize) / $fileSize * 100, 1) . '%',
            'checksum' => $gzChecksum
        ];
        
        // Create checksum file for compressed version
        file_put_contents($gzFile . '.md5', $gzChecksum . '  ' . basename($gzFile));
    }
    
    // Verify backup integrity
    if ($options['verify']) {
        $verifyResult = verifyBackup($dumpFile, $checksum);
        $response['verification'] = $verifyResult;
        
        if (!$verifyResult['valid']) {
            $response['status'] = 'WARNING';
            $response['warning'] = 'Backup created but verification failed';
        }
    }
    
    // Apply retention policy
    if ($options['retention'] > 0) {
        $deleted = applyRetentionPolicy($backupDir, $options['retention']);
        if ($deleted > 0) {
            $response['info'][] = "Deleted {$deleted} old backup(s) per retention policy";
        }
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
        'Check write permissions for storage/backups/ directory'
    ];
}

// Output result
$json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $json;

// Write backup log
$logFile = __DIR__ . '/../storage/backups/backup.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $response['status'] . ' - ' . ($response['summary'] ?? $response['error'] ?? 'Unknown') . "\n", FILE_APPEND);

// Exit with appropriate code for CLI
if ($isCli) {
    exit($response['status'] === 'OK' ? 0 : 1);
}

// ========================================
// Helper Functions
// ========================================

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function verifyBackup($file, $expectedChecksum) {
    $result = [
        'valid' => false,
        'checksum_match' => false,
        'readable' => false,
        'has_data' => false
    ];
    
    // Verify file is readable
    if (!is_readable($file)) {
        return $result;
    }
    $result['readable'] = true;
    
    // Verify checksum
    $actualChecksum = md5_file($file);
    if ($actualChecksum === $expectedChecksum) {
        $result['checksum_match'] = true;
    }
    
    // Verify file has data
    $fileSize = filesize($file);
    if ($fileSize > 100) { // At least 100 bytes
        $result['has_data'] = true;
    }
    
    // Overall validity
    $result['valid'] = $result['readable'] && $result['checksum_match'] && $result['has_data'];
    
    return $result;
}

function applyRetentionPolicy($backupDir, $retention) {
    // Get all backup files
    $files = glob($backupDir . '/*.sql');
    $files = array_merge($files, glob($backupDir . '/*.sql.gz'));
    
    if (count($files) <= $retention) {
        return 0; // No files to delete
    }
    
    // Sort by modification time (oldest first)
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    // Delete oldest files
    $toDelete = count($files) - $retention;
    $deleted = 0;
    
    for ($i = 0; $i < $toDelete; $i++) {
        $file = $files[$i];
        
        // Delete main file
        if (file_exists($file)) {
            unlink($file);
            $deleted++;
        }
        
        // Delete checksum file
        if (file_exists($file . '.md5')) {
            unlink($file . '.md5');
        }
    }
    
    return $deleted;
}
