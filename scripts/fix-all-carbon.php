#!/usr/bin/env php
<?php

/**
 * Complete Carbon Fix Script
 * 
 * Systematically fixes all Carbon namespace and now() issues in app/ directory:
 * - Removes malformed Carbon imports and usage patterns
 * - Adds proper Illuminate\Support\Carbon import
 * - Replaces all now() calls with Carbon::now()
 * - Validates syntax with php -l
 * 
 * Usage: php scripts/fix-all-carbon.php [--dry-run]
 */

class CarbonFixer
{
    private $dryRun = false;
    private $stats = [
        'scanned' => 0,
        'modified' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];
    
    private $errors = [];
    
    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
    }
    
    /**
     * Scan and fix all PHP files in app/ directory
     */
    public function fixAll()
    {
        echo "🔍 Scanning app/ directory for Carbon issues...\n\n";
        
        $directories = [
            __DIR__ . '/../app/Models',
            __DIR__ . '/../app/Services',
            __DIR__ . '/../app/Http/Controllers',
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                echo "⚠️  Directory not found: $dir\n";
                continue;
            }
            
            $this->processDirectory($dir);
        }
        
        $this->printSummary();
        
        return $this->stats['errors'] === 0;
    }
    
    /**
     * Process all PHP files in a directory recursively
     */
    private function processDirectory($dir)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }
    
    /**
     * Process a single PHP file
     */
    private function processFile($filePath)
    {
        $this->stats['scanned']++;
        
        $relativePath = str_replace(__DIR__ . '/../', '', $filePath);
        
        $content = file_get_contents($filePath);
        
        // Check if file needs fixing
        if (!$this->needsFix($content)) {
            echo "✓ {$relativePath} - No changes needed\n";
            $this->stats['skipped']++;
            return;
        }
        
        echo "🔧 Fixing {$relativePath}...\n";
        
        // Apply fixes
        $fixed = $this->fixContent($content);
        
        // Validate syntax
        if (!$this->validateSyntax($fixed)) {
            echo "❌ {$relativePath} - Syntax validation failed!\n";
            $this->errors[] = $relativePath;
            $this->stats['errors']++;
            return;
        }
        
        // Write fixed content
        if (!$this->dryRun) {
            file_put_contents($filePath, $fixed);
            echo "✅ {$relativePath} - Fixed and validated\n";
        } else {
            echo "✅ {$relativePath} - Would be fixed (dry run)\n";
        }
        
        $this->stats['modified']++;
    }
    
    /**
     * Check if file needs fixing
     */
    private function needsFix($content)
    {
        // Check for now() calls or Carbon usage
        return preg_match('/\bnow\(\)/', $content) ||
               preg_match('/\bCarbon::/', $content) ||
               preg_match('/\\\\Carbon::/', $content) ||
               preg_match('/Carbon::Carbon::/', $content);
    }
    
    /**
     * Apply all Carbon fixes to content
     */
    private function fixContent($content)
    {
        // Step 1: Extract namespace
        if (!preg_match('/^<\?php\s+namespace\s+([^;]+);/m', $content, $matches)) {
            return $content; // No namespace, skip
        }
        
        $namespace = $matches[0];
        
        // Step 2: Remove all existing Carbon imports (they may be malformed)
        $content = preg_replace('/^use\s+.*Carbon.*?;\s*$/m', '', $content);
        
        // Step 3: Clean up malformed Carbon usage
        // Remove Carbon::Carbon:: patterns
        $content = preg_replace('/Carbon::Carbon::/', 'Carbon::', $content);
        $content = preg_replace('/\\\\Carbon::/', 'Carbon::', $content);
        
        // Step 4: Replace all now() calls with Carbon::now()
        // Handle various patterns:
        // - now() at start or after non-word chars
        // - now()->method() chains
        // - \now() with leading backslash
        $content = preg_replace('/\bnow\(\)/', 'Carbon::now()', $content);
        $content = preg_replace('/\\\\now\(\)/', 'Carbon::now()', $content);
        
        // Step 5: Add proper Carbon import after namespace
        // Find the position right after namespace declaration
        $namespaceEndPos = strpos($content, ';', strpos($content, 'namespace'));
        
        if ($namespaceEndPos !== false) {
            // Check if we already have use statements
            $afterNamespace = substr($content, $namespaceEndPos + 1);
            
            // Find where to insert the import
            if (preg_match('/^(\s*)(use\s+)/m', $afterNamespace, $useMatch, PREG_OFFSET_CAPTURE)) {
                // There are existing use statements, insert before them
                $insertPos = $namespaceEndPos + 1 + $useMatch[0][1];
            } else {
                // No use statements, insert after namespace with proper spacing
                $insertPos = $namespaceEndPos + 1;
            }
            
            // Check if Carbon import already exists (after our cleanup)
            if (!preg_match('/use\s+Illuminate\\\\Support\\\\Carbon;/', $content)) {
                $carbonImport = "\n\nuse Illuminate\\Support\\Carbon;";
                $content = substr_replace($content, $carbonImport, $insertPos, 0);
            }
        }
        
        // Step 6: Clean up multiple blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return $content;
    }
    
    /**
     * Validate PHP syntax
     */
    private function validateSyntax($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'carbon_fix_');
        file_put_contents($tempFile, $content);
        
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($tempFile) . " 2>&1", $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode !== 0) {
            echo "   Syntax error: " . implode("\n", $output) . "\n";
            return false;
        }
        
        return true;
    }
    
    /**
     * Print summary of operations
     */
    private function printSummary()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 Summary\n";
        echo str_repeat("=", 60) . "\n";
        echo "Files scanned:  {$this->stats['scanned']}\n";
        echo "Files modified: {$this->stats['modified']}\n";
        echo "Files skipped:  {$this->stats['skipped']}\n";
        echo "Errors:         {$this->stats['errors']}\n";
        
        if (!empty($this->errors)) {
            echo "\n❌ Files with errors:\n";
            foreach ($this->errors as $error) {
                echo "   - $error\n";
            }
        }
        
        if ($this->dryRun) {
            echo "\n⚠️  DRY RUN - No files were actually modified\n";
        }
        
        if ($this->stats['errors'] === 0) {
            echo "\n✅ All files processed successfully!\n";
        } else {
            echo "\n❌ Some files had errors. Please review and fix manually.\n";
        }
        
        echo str_repeat("=", 60) . "\n";
    }
}

// Main execution
$dryRun = in_array('--dry-run', $argv ?? []);

if (in_array('--help', $argv ?? []) || in_array('-h', $argv ?? [])) {
    echo "Usage: php scripts/fix-all-carbon.php [--dry-run] [--help]\n\n";
    echo "Options:\n";
    echo "  --dry-run  Show what would be changed without modifying files\n";
    echo "  --help     Show this help message\n\n";
    exit(0);
}

$fixer = new CarbonFixer($dryRun);
$success = $fixer->fixAll();

exit($success ? 0 : 1);
