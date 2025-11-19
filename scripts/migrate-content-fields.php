<?php
/**
 * Content API Migration Script
 * 
 * Applies database migrations for the Content API overhaul:
 * - Adds slug, featured, and media fields to content tables
 * - Backfills slugs for existing records
 * - Makes slugs NOT NULL and UNIQUE after backfill
 * 
 * Usage: php scripts/migrate-content-fields.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

echo "========================================\n";
echo "Content API Migration\n";
echo "========================================\n\n";

// Read migration file
$migrationFile = __DIR__ . '/../database/migrations/add_content_media_fields.sql';

if (!file_exists($migrationFile)) {
    echo "❌ Migration file not found: {$migrationFile}\n";
    exit(1);
}

$sql = file_get_contents($migrationFile);

try {
    // Get PDO connection from Eloquent
    $pdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', preg_split('/;[\s]*\n/', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    echo "Executing " . count($statements) . " SQL statements...\n\n";
    
    $successCount = 0;
    $skipCount = 0;
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Extract first line for logging
        $firstLine = explode("\n", $statement)[0];
        echo "[" . ($index + 1) . "] " . substr($firstLine, 0, 80) . "...\n";
        
        try {
            $pdo->exec($statement . ';');
            $successCount++;
            echo "    ✅ Success\n";
        } catch (PDOException $e) {
            // Check if this is a "duplicate" or "already exists" error
            if (strpos($e->getMessage(), 'Duplicate') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                $skipCount++;
                echo "    ⏭️  Skipped (already exists)\n";
            } else {
                echo "    ❌ Error: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
    
    echo "\n========================================\n";
    echo "Migration Complete!\n";
    echo "========================================\n";
    echo "✅ Successful: $successCount\n";
    echo "⏭️  Skipped: $skipCount\n";
    echo "\n";
    
    // Verify changes
    echo "Verifying schema changes...\n\n";
    
    $tables = ['portfolio', 'testimonials', 'faq', 'content_blocks'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table}");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasSlug = false;
        $hasFeatured = false;
        $hasMediaFields = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'slug') {
                $hasSlug = true;
            }
            if ($column['Field'] === 'featured') {
                $hasFeatured = true;
            }
            if (in_array($column['Field'], ['image_path', 'avatar_path'])) {
                $hasMediaFields = true;
            }
        }
        
        echo "Table: {$table}\n";
        echo "  - slug: " . ($hasSlug ? "✅" : "❌") . "\n";
        
        if (in_array($table, ['portfolio', 'testimonials'])) {
            echo "  - featured: " . ($hasFeatured ? "✅" : "❌") . "\n";
            echo "  - media fields: " . ($hasMediaFields ? "✅" : "❌") . "\n";
        }
        
        echo "\n";
    }
    
    echo "✅ Migration completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
