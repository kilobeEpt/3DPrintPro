#!/usr/bin/env php
<?php
/**
 * Hosting Readiness Audit Utility
 * 
 * Validates hosting environment against 3D Print Pro deployment requirements.
 * Checks PHP version, extensions, CLI tools, services, permissions, and resources.
 * 
 * Usage:
 *   php scripts/hosting-audit.php [options]
 * 
 * Options:
 *   --format=json          Output results in JSON format for CI integration
 *   --strict               Enable strict mode (fail on warnings)
 *   --skip-redis           Skip Redis checks (for shared hosting)
 *   --assert ext,name      Only check specified extensions (comma-separated)
 *   --help                 Show this help message
 * 
 * Exit codes:
 *   0 - All required checks passed
 *   1 - One or more required checks failed
 *   2 - Invalid usage or arguments
 */

// Parse command-line arguments
$options = parseArguments($argv);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

// Initialize audit results
$audit = [
    'timestamp' => date('Y-m-d H:i:s'),
    'hostname' => gethostname(),
    'checks' => [],
    'summary' => [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'warnings' => 0
    ]
];

// Run all audit checks
$audit = checkPhpVersion($audit, $options);
$audit = checkPhpExtensions($audit, $options);
$audit = checkCliTools($audit, $options);
$audit = checkServices($audit, $options);
$audit = checkResources($audit, $options);
$audit = checkPermissions($audit, $options);
$audit = checkWriteAccess($audit, $options);

// Calculate summary
foreach ($audit['checks'] as $check) {
    $audit['summary']['total']++;
    if ($check['status'] === 'PASS') {
        $audit['summary']['passed']++;
    } elseif ($check['status'] === 'FAIL') {
        $audit['summary']['failed']++;
    } elseif ($check['status'] === 'WARN') {
        $audit['summary']['warnings']++;
    }
}

// Output results
if (isset($options['format']) && $options['format'] === 'json') {
    outputJson($audit);
} else {
    outputHuman($audit, $options);
}

// Exit with appropriate code
$hasFailures = $audit['summary']['failed'] > 0;
$hasWarnings = $audit['summary']['warnings'] > 0;

if ($hasFailures) {
    exit(1);
}

if ($hasWarnings && isset($options['strict'])) {
    exit(1);
}

exit(0);

// ============================================================================
// AUDIT FUNCTIONS
// ============================================================================

/**
 * Check PHP version requirement (>= 7.4)
 */
function checkPhpVersion(array $audit, array $options): array
{
    $requiredVersion = '7.4.0';
    $currentVersion = PHP_VERSION;
    
    $check = [
        'category' => 'PHP Runtime',
        'name' => 'PHP Version',
        'requirement' => '>= 7.4.0',
        'actual' => $currentVersion,
        'status' => version_compare($currentVersion, $requiredVersion, '>=') ? 'PASS' : 'FAIL',
        'critical' => true
    ];
    
    if ($check['status'] === 'FAIL') {
        $check['remediation'] = "Upgrade PHP to version 7.4 or higher. Current version: {$currentVersion}";
    }
    
    $audit['checks'][] = $check;
    return $audit;
}

/**
 * Check required PHP extensions
 */
function checkPhpExtensions(array $audit, array $options): array
{
    // Core required extensions
    $requiredExtensions = [
        'pdo_mysql' => ['critical' => true, 'description' => 'MySQL database connectivity'],
        'mbstring' => ['critical' => true, 'description' => 'Multi-byte string handling'],
        'intl' => ['critical' => true, 'description' => 'Internationalization support'],
        'json' => ['critical' => true, 'description' => 'JSON encoding/decoding'],
        'curl' => ['critical' => true, 'description' => 'HTTP client for API calls'],
        'openssl' => ['critical' => true, 'description' => 'SSL/TLS encryption'],
        'zip' => ['critical' => true, 'description' => 'Archive handling'],
        'gd' => ['critical' => false, 'description' => 'Image processing (optional)'],
        'imagick' => ['critical' => false, 'description' => 'Advanced image processing (optional)']
    ];
    
    // Filter extensions if --assert specified
    if (isset($options['assert'])) {
        $assertExtensions = array_map('trim', explode(',', $options['assert']));
        $requiredExtensions = array_filter(
            $requiredExtensions,
            function($key) use ($assertExtensions) {
                return in_array($key, $assertExtensions);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
    
    foreach ($requiredExtensions as $extension => $config) {
        $loaded = extension_loaded($extension);
        
        $check = [
            'category' => 'PHP Extensions',
            'name' => "Extension: {$extension}",
            'requirement' => 'Loaded',
            'actual' => $loaded ? 'Loaded' : 'Not loaded',
            'status' => $loaded ? 'PASS' : ($config['critical'] ? 'FAIL' : 'WARN'),
            'critical' => $config['critical'],
            'description' => $config['description']
        ];
        
        if (!$loaded) {
            $check['remediation'] = $config['critical']
                ? "Install PHP extension: {$extension}. Run: apt-get install php-{$extension} (or equivalent for your OS)"
                : "Optional: Install PHP extension: {$extension} for enhanced functionality";
        }
        
        $audit['checks'][] = $check;
    }
    
    return $audit;
}

/**
 * Check CLI tools availability
 */
function checkCliTools(array $audit, array $options): array
{
    $tools = [
        'composer' => ['critical' => true, 'description' => 'PHP dependency manager'],
        'php' => ['critical' => true, 'description' => 'PHP CLI'],
        'mysql' => ['critical' => true, 'description' => 'MySQL client'],
        'mysqldump' => ['critical' => true, 'description' => 'Database backup utility'],
        'node' => ['critical' => false, 'description' => 'Node.js runtime (optional)'],
        'npm' => ['critical' => false, 'description' => 'Node package manager (optional)'],
        'redis-cli' => ['critical' => false, 'description' => 'Redis client (optional)'],
        'certbot' => ['critical' => false, 'description' => 'SSL certificate manager (optional)']
    ];
    
    // Skip Redis tools if requested
    if (isset($options['skip-redis'])) {
        unset($tools['redis-cli']);
    }
    
    foreach ($tools as $tool => $config) {
        $available = isCommandAvailable($tool);
        $version = $available ? getToolVersion($tool) : null;
        
        $check = [
            'category' => 'CLI Tools',
            'name' => "Tool: {$tool}",
            'requirement' => 'Available',
            'actual' => $available ? "Available" . ($version ? " ({$version})" : "") : 'Not found',
            'status' => $available ? 'PASS' : ($config['critical'] ? 'FAIL' : 'WARN'),
            'critical' => $config['critical'],
            'description' => $config['description']
        ];
        
        if (!$available) {
            $check['remediation'] = getToolInstallCommand($tool, $config['critical']);
        }
        
        $audit['checks'][] = $check;
    }
    
    return $audit;
}

/**
 * Check service daemons
 */
function checkServices(array $audit, array $options): array
{
    $services = [
        'mysql' => ['critical' => true, 'description' => 'MySQL database server'],
        'redis' => ['critical' => false, 'description' => 'Redis cache server (optional)']
    ];
    
    // Skip Redis if requested
    if (isset($options['skip-redis'])) {
        unset($services['redis']);
    }
    
    foreach ($services as $service => $config) {
        $running = isServiceRunning($service);
        
        $check = [
            'category' => 'Services',
            'name' => "Service: {$service}",
            'requirement' => 'Running',
            'actual' => $running ? 'Running' : 'Not running',
            'status' => $running ? 'PASS' : ($config['critical'] ? 'FAIL' : 'WARN'),
            'critical' => $config['critical'],
            'description' => $config['description']
        ];
        
        if (!$running) {
            $check['remediation'] = $config['critical']
                ? "Start {$service} service: systemctl start {$service} (or equivalent for your OS)"
                : "Optional: Start {$service} service for enhanced performance";
        }
        
        $audit['checks'][] = $check;
    }
    
    return $audit;
}

/**
 * Check system resources (disk, memory)
 */
function checkResources(array $audit, array $options): array
{
    // Check disk space
    $projectRoot = dirname(__DIR__);
    $diskFree = disk_free_space($projectRoot);
    $diskTotal = disk_total_space($projectRoot);
    
    if ($diskFree !== false && $diskTotal !== false) {
        $diskUsedPercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
        $diskFreeGb = round($diskFree / 1024 / 1024 / 1024, 2);
        $diskTotalGb = round($diskTotal / 1024 / 1024 / 1024, 2);
        
        $check = [
            'category' => 'Resources',
            'name' => 'Disk Space',
            'requirement' => '> 1 GB free',
            'actual' => "{$diskFreeGb} GB free of {$diskTotalGb} GB total",
            'status' => ($diskFreeGb > 1) ? 'PASS' : ($diskFreeGb > 0.5 ? 'WARN' : 'FAIL'),
            'critical' => true
        ];
        
        if ($diskFreeGb <= 1) {
            $check['remediation'] = "Free up disk space. Current: {$diskFreeGb} GB. Run: df -h to inspect usage";
        }
        
        $audit['checks'][] = $check;
    }
    
    // Check memory (if available)
    if (PHP_OS_FAMILY === 'Linux') {
        $memInfo = getMemoryInfo();
        if ($memInfo) {
            $memFreeMb = $memInfo['free'];
            $memTotalMb = $memInfo['total'];
            $memUsedPercent = (($memTotalMb - $memFreeMb) / $memTotalMb) * 100;
            
            $check = [
                'category' => 'Resources',
                'name' => 'Memory',
                'requirement' => '> 256 MB free',
                'actual' => "{$memFreeMb} MB free of {$memTotalMb} MB total",
                'status' => ($memFreeMb > 256) ? 'PASS' : ($memFreeMb > 128 ? 'WARN' : 'FAIL'),
                'critical' => true
            ];
            
            if ($memFreeMb <= 256) {
                $check['remediation'] = "Increase available memory. Current: {$memFreeMb} MB. Run: free -m to inspect usage";
            }
            
            $audit['checks'][] = $check;
        }
    }
    
    // Check PHP memory limit
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = convertToBytes($memoryLimit);
    $minRequired = 128 * 1024 * 1024; // 128MB
    
    $check = [
        'category' => 'Resources',
        'name' => 'PHP Memory Limit',
        'requirement' => '>= 128M',
        'actual' => $memoryLimit,
        'status' => ($memoryLimitBytes >= $minRequired || $memoryLimitBytes === -1) ? 'PASS' : 'WARN',
        'critical' => false
    ];
    
    if ($memoryLimitBytes < $minRequired && $memoryLimitBytes !== -1) {
        $check['remediation'] = "Increase memory_limit in php.ini to at least 128M. Current: {$memoryLimit}";
    }
    
    $audit['checks'][] = $check;
    
    // Check max execution time
    $maxExecutionTime = (int)ini_get('max_execution_time');
    
    $check = [
        'category' => 'Resources',
        'name' => 'PHP Max Execution Time',
        'requirement' => '>= 30 seconds',
        'actual' => $maxExecutionTime === 0 ? 'Unlimited' : "{$maxExecutionTime} seconds",
        'status' => ($maxExecutionTime >= 30 || $maxExecutionTime === 0) ? 'PASS' : 'WARN',
        'critical' => false
    ];
    
    if ($maxExecutionTime < 30 && $maxExecutionTime !== 0) {
        $check['remediation'] = "Increase max_execution_time in php.ini to at least 30. Current: {$maxExecutionTime}";
    }
    
    $audit['checks'][] = $check;
    
    return $audit;
}

/**
 * Check directory permissions
 */
function checkPermissions(array $audit, array $options): array
{
    $projectRoot = dirname(__DIR__);
    $directories = [
        'storage' => ['writable' => true, 'critical' => true],
        'logs' => ['writable' => true, 'critical' => true],
        'storage/cache' => ['writable' => true, 'critical' => true],
        'storage/uploads' => ['writable' => true, 'critical' => false],
        'storage/backups' => ['writable' => true, 'critical' => false]
    ];
    
    foreach ($directories as $dir => $config) {
        $fullPath = $projectRoot . '/' . $dir;
        $exists = file_exists($fullPath);
        $isDir = $exists && is_dir($fullPath);
        $writable = $isDir && is_writable($fullPath);
        
        $status = 'FAIL';
        $actual = 'Not found';
        $remediation = null;
        
        if (!$exists) {
            $actual = 'Directory does not exist';
            $status = $config['critical'] ? 'FAIL' : 'WARN';
            $remediation = "Create directory: mkdir -p {$fullPath} && chmod 755 {$fullPath}";
        } elseif (!$isDir) {
            $actual = 'Path exists but is not a directory';
            $status = 'FAIL';
            $remediation = "Remove file and create directory: rm {$fullPath} && mkdir -p {$fullPath}";
        } elseif (!$writable && $config['writable']) {
            $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
            $actual = "Not writable (permissions: {$perms})";
            $status = $config['critical'] ? 'FAIL' : 'WARN';
            $remediation = "Make directory writable: chmod 755 {$fullPath}";
        } else {
            $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
            $actual = "Writable (permissions: {$perms})";
            $status = 'PASS';
        }
        
        $check = [
            'category' => 'Permissions',
            'name' => "Directory: {$dir}",
            'requirement' => $config['writable'] ? 'Writable' : 'Readable',
            'actual' => $actual,
            'status' => $status,
            'critical' => $config['critical']
        ];
        
        if ($remediation) {
            $check['remediation'] = $remediation;
        }
        
        $audit['checks'][] = $check;
    }
    
    return $audit;
}

/**
 * Check write access to project root
 */
function checkWriteAccess(array $audit, array $options): array
{
    $projectRoot = dirname(__DIR__);
    $testFile = $projectRoot . '/.hosting_audit_write_test';
    
    $canWrite = false;
    $remediation = null;
    
    try {
        // Attempt to write test file
        $result = @file_put_contents($testFile, 'test');
        if ($result !== false) {
            $canWrite = true;
            @unlink($testFile);
        }
    } catch (Exception $e) {
        $remediation = "Error: " . $e->getMessage();
    }
    
    $check = [
        'category' => 'Permissions',
        'name' => 'Project Root Write Access',
        'requirement' => 'SSH user can write to project path',
        'actual' => $canWrite ? 'Writable' : 'Not writable',
        'status' => $canWrite ? 'PASS' : 'FAIL',
        'critical' => true
    ];
    
    if (!$canWrite) {
        $user = get_current_user();
        $check['remediation'] = "Grant write access to user '{$user}' for path: {$projectRoot}. Run: chown -R {$user}:{$user} {$projectRoot}";
        if ($remediation) {
            $check['remediation'] .= " | " . $remediation;
        }
    }
    
    $audit['checks'][] = $check;
    return $audit;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Check if a command is available in PATH
 */
function isCommandAvailable(string $command): bool
{
    $output = [];
    $return = 0;
    
    if (PHP_OS_FAMILY === 'Windows') {
        exec("where {$command} 2>&1", $output, $return);
    } else {
        exec("command -v {$command} 2>&1", $output, $return);
    }
    
    return $return === 0;
}

/**
 * Get version of a CLI tool
 */
function getToolVersion(string $tool): ?string
{
    $versionCommands = [
        'composer' => 'composer --version 2>&1 | head -n1',
        'php' => 'php --version 2>&1 | head -n1',
        'mysql' => 'mysql --version 2>&1',
        'mysqldump' => 'mysqldump --version 2>&1',
        'node' => 'node --version 2>&1',
        'npm' => 'npm --version 2>&1',
        'redis-cli' => 'redis-cli --version 2>&1',
        'certbot' => 'certbot --version 2>&1'
    ];
    
    if (!isset($versionCommands[$tool])) {
        return null;
    }
    
    $output = [];
    exec($versionCommands[$tool], $output);
    
    if (empty($output)) {
        return null;
    }
    
    $version = trim($output[0]);
    
    // Extract version number
    if (preg_match('/\d+\.\d+(\.\d+)?/', $version, $matches)) {
        return $matches[0];
    }
    
    return null;
}

/**
 * Get installation command for a tool
 */
function getToolInstallCommand(string $tool, bool $critical): string
{
    $commands = [
        'composer' => 'Install Composer: https://getcomposer.org/download/',
        'php' => 'Install PHP 7.4+: apt-get install php7.4-cli (or equivalent)',
        'mysql' => 'Install MySQL client: apt-get install mysql-client',
        'mysqldump' => 'Install MySQL client: apt-get install mysql-client',
        'node' => 'Optional: Install Node.js: https://nodejs.org/',
        'npm' => 'Optional: Install Node.js (includes npm): https://nodejs.org/',
        'redis-cli' => 'Optional: Install Redis tools: apt-get install redis-tools',
        'certbot' => 'Optional: Install Certbot: apt-get install certbot'
    ];
    
    $prefix = $critical ? '' : 'Optional: ';
    return $prefix . ($commands[$tool] ?? "Install {$tool}");
}

/**
 * Check if a service is running
 */
function isServiceRunning(string $service): bool
{
    // Try systemctl first (systemd)
    $output = [];
    $return = 0;
    exec("systemctl is-active {$service} 2>&1", $output, $return);
    if ($return === 0) {
        return true;
    }
    
    // Try service command (SysV)
    $output = [];
    $return = 0;
    exec("service {$service} status 2>&1", $output, $return);
    if ($return === 0) {
        return true;
    }
    
    // Try pgrep (process search)
    if ($service === 'mysql') {
        exec("pgrep -x mysqld 2>&1", $output, $return);
        if ($return === 0) {
            return true;
        }
    } elseif ($service === 'redis') {
        exec("pgrep -x redis-server 2>&1", $output, $return);
        if ($return === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get memory information (Linux only)
 */
function getMemoryInfo(): ?array
{
    if (!file_exists('/proc/meminfo')) {
        return null;
    }
    
    $meminfo = file_get_contents('/proc/meminfo');
    if (!$meminfo) {
        return null;
    }
    
    $lines = explode("\n", $meminfo);
    $info = [];
    
    foreach ($lines as $line) {
        if (preg_match('/^(MemTotal|MemAvailable|MemFree):\s+(\d+)\s+kB/', $line, $matches)) {
            $info[strtolower($matches[1])] = (int)($matches[2] / 1024); // Convert to MB
        }
    }
    
    if (isset($info['memtotal'])) {
        $free = isset($info['memavailable']) ? $info['memavailable'] : $info['memfree'];
        return [
            'total' => $info['memtotal'],
            'free' => $free
        ];
    }
    
    return null;
}

/**
 * Convert PHP memory notation to bytes
 */
function convertToBytes(string $value): int
{
    $value = trim($value);
    $last = strtolower($value[strlen($value) - 1]);
    $num = (int)$value;
    
    switch ($last) {
        case 'g':
            $num *= 1024;
        case 'm':
            $num *= 1024;
        case 'k':
            $num *= 1024;
    }
    
    return $num;
}

/**
 * Parse command-line arguments
 */
function parseArguments(array $argv): array
{
    $options = [];
    
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        } elseif ($arg === '--strict') {
            $options['strict'] = true;
        } elseif ($arg === '--skip-redis') {
            $options['skip-redis'] = true;
        } elseif (strpos($arg, '--format=') === 0) {
            $options['format'] = substr($arg, 9);
        } elseif (strpos($arg, '--assert') === 0) {
            if (strpos($arg, '=') !== false) {
                $options['assert'] = substr($arg, 9);
            } elseif (isset($argv[$i + 1])) {
                $options['assert'] = $argv[$i + 1];
                $i++;
            }
        }
    }
    
    return $options;
}

/**
 * Show help message
 */
function showHelp(): void
{
    echo <<<HELP
Hosting Readiness Audit Utility

Validates hosting environment against 3D Print Pro deployment requirements.

USAGE:
    php scripts/hosting-audit.php [options]

OPTIONS:
    --format=json      Output results in JSON format for CI integration
    --strict           Enable strict mode (fail on warnings)
    --skip-redis       Skip Redis checks (for shared hosting)
    --assert ext,name  Only check specified extensions (comma-separated)
    --help, -h         Show this help message

EXAMPLES:
    # Run full audit with human-readable output
    php scripts/hosting-audit.php

    # Output JSON for CI/CD integration
    php scripts/hosting-audit.php --format=json

    # Skip Redis checks for shared hosting
    php scripts/hosting-audit.php --skip-redis

    # Check only specific extensions
    php scripts/hosting-audit.php --assert pdo_mysql,mbstring,json

    # Strict mode (warnings treated as failures)
    php scripts/hosting-audit.php --strict

EXIT CODES:
    0 - All required checks passed
    1 - One or more required checks failed
    2 - Invalid usage or arguments

DOCUMENTATION:
    See docs/HOSTING_AUDIT.md for detailed information

HELP;
}

/**
 * Output results in JSON format
 */
function outputJson(array $audit): void
{
    echo json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

/**
 * Output results in human-readable format
 */
function outputHuman(array $audit, array $options): void
{
    $width = 80;
    
    // Header
    echo str_repeat('=', $width) . PHP_EOL;
    echo centerText('HOSTING READINESS AUDIT', $width) . PHP_EOL;
    echo centerText('3D Print Pro Platform', $width) . PHP_EOL;
    echo str_repeat('=', $width) . PHP_EOL;
    echo PHP_EOL;
    
    // Metadata
    echo "Timestamp: " . $audit['timestamp'] . PHP_EOL;
    echo "Hostname:  " . $audit['hostname'] . PHP_EOL;
    echo "PHP:       " . PHP_VERSION . PHP_EOL;
    echo PHP_EOL;
    
    // Group checks by category
    $categories = [];
    foreach ($audit['checks'] as $check) {
        $categories[$check['category']][] = $check;
    }
    
    // Output each category
    foreach ($categories as $category => $checks) {
        echo str_repeat('-', $width) . PHP_EOL;
        echo "  {$category}" . PHP_EOL;
        echo str_repeat('-', $width) . PHP_EOL;
        
        foreach ($checks as $check) {
            $status = formatStatus($check['status']);
            $critical = $check['critical'] ? ' [CRITICAL]' : '';
            
            echo sprintf("  %-50s %s%s", $check['name'], $status, $critical) . PHP_EOL;
            echo sprintf("    Requirement: %s", $check['requirement']) . PHP_EOL;
            echo sprintf("    Actual:      %s", $check['actual']) . PHP_EOL;
            
            if (isset($check['description'])) {
                echo sprintf("    Description: %s", $check['description']) . PHP_EOL;
            }
            
            if (isset($check['remediation'])) {
                echo sprintf("    Remediation: %s", wordwrap($check['remediation'], $width - 17, PHP_EOL . "                 ")) . PHP_EOL;
            }
            
            echo PHP_EOL;
        }
    }
    
    // Summary
    echo str_repeat('=', $width) . PHP_EOL;
    echo centerText('SUMMARY', $width) . PHP_EOL;
    echo str_repeat('=', $width) . PHP_EOL;
    echo PHP_EOL;
    
    $passed = $audit['summary']['passed'];
    $failed = $audit['summary']['failed'];
    $warnings = $audit['summary']['warnings'];
    $total = $audit['summary']['total'];
    
    echo sprintf("  Total Checks:   %3d", $total) . PHP_EOL;
    echo sprintf("  Passed:         %3d  %s", $passed, formatStatus('PASS')) . PHP_EOL;
    echo sprintf("  Failed:         %3d  %s", $failed, formatStatus('FAIL')) . PHP_EOL;
    echo sprintf("  Warnings:       %3d  %s", $warnings, formatStatus('WARN')) . PHP_EOL;
    echo PHP_EOL;
    
    // Overall status
    if ($failed > 0) {
        echo "  Overall Status: " . formatStatus('FAIL') . PHP_EOL;
        echo PHP_EOL;
        echo "  ❌ HOSTING ENVIRONMENT NOT READY" . PHP_EOL;
        echo "  Please address the failed checks above before deployment." . PHP_EOL;
    } elseif ($warnings > 0) {
        echo "  Overall Status: " . formatStatus('WARN') . PHP_EOL;
        echo PHP_EOL;
        echo "  ⚠️  HOSTING ENVIRONMENT PARTIALLY READY" . PHP_EOL;
        echo "  Some optional features may not be available." . PHP_EOL;
        
        if (isset($options['strict'])) {
            echo "  Running in --strict mode: Warnings will cause failure." . PHP_EOL;
        }
    } else {
        echo "  Overall Status: " . formatStatus('PASS') . PHP_EOL;
        echo PHP_EOL;
        echo "  ✅ HOSTING ENVIRONMENT READY" . PHP_EOL;
        echo "  All checks passed. Proceed with deployment." . PHP_EOL;
    }
    
    echo PHP_EOL;
    echo str_repeat('=', $width) . PHP_EOL;
}

/**
 * Center text within a given width
 */
function centerText(string $text, int $width): string
{
    $len = strlen($text);
    $padding = max(0, ($width - $len) / 2);
    return str_repeat(' ', (int)$padding) . $text;
}

/**
 * Format status with color/symbols
 */
function formatStatus(string $status): string
{
    switch ($status) {
        case 'PASS':
            return "\033[32m✓ PASS\033[0m";
        case 'FAIL':
            return "\033[31m✗ FAIL\033[0m";
        case 'WARN':
            return "\033[33m⚠ WARN\033[0m";
        default:
            return $status;
    }
}
