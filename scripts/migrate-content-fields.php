
<?php

/**

 * Migration: Add Content Media Fields and Slug Support

 * 

 * Adds slug-based URLs, featured flags, and media handling to content tables.

 * Safe to run multiple times (idempotent).

 */



require_once __DIR__ . '/../bootstrap/eloquent.php';



use Illuminate\Database\Capsule\Manager as Capsule;



class ContentFieldsMigration

{

    private $connection;

    private $errors = [];

    private $warnings = [];

    private $success = [];



    public function __construct()

    {

        // Get connection directly from Capsule

        $this->connection = Capsule::connection();

    }



    public function run()

    {

        echo "\n╔════════════════════════════════════════════════════════════╗\n";

        echo "║ Content API v2.0 Migration                                  ║\n";

        echo "╚════════════════════════════════════════════════════════════╝\n\n";



        $startTime = microtime(true);



        try {

            echo "🔄 Starting migration...\n\n";



            // 1. Portfolio table

            $this->migratePortfolio();



            // 2. Testimonials table

            $this->migrateTestimonials();



            // 3. FAQ table

            $this->migrateFAQ();



            // 4. Content Blocks table

            $this->migrateContentBlocks();



            // 5. Services table (add media fields)

            $this->migrateServices();



            // 6. Add Foreign Keys

            $this->addForeignKeys();



            // 7. Verify changes

            $this->verify();



            $duration = microtime(true) - $startTime;



            echo "\n╔════════════════════════════════════════════════════════════╗\n";

            echo "║ Migration Results                                           ║\n";

            echo "╠════════════════════════════════════════════════════════════╣\n";

            echo "║ ✅ Success: " . count($this->success) . " operations                             ║\n";

            echo "║ ⚠️  Warnings: " . count($this->warnings) . "                               ║\n";

            echo "║ ❌ Errors: " . count($this->errors) . "                                 ║\n";

            echo "║ ⏱️  Duration: " . round($duration, 2) . "s                              ║\n";

            echo "╚════════════════════════════════════════════════════════════╝\n\n";



            if (!empty($this->errors)) {

                echo "ERRORS:\n";

                foreach ($this->errors as $error) {

                    echo "  ❌ $error\n";

                }

                return false;

            }



            echo "🎉 Migration completed successfully!\n\n";

            return true;



        } catch (\Exception $e) {

            echo "\n❌ Migration failed: " . $e->getMessage() . "\n";

            echo "Stack trace: " . $e->getTraceAsString() . "\n";

            return false;

        }

    }



    private function migratePortfolio()

    {

        echo "📁 Migrating portfolio table...\n";



        try {

            $table = 'portfolio';



            // Add slug column

            if (!$this->hasColumn($table, 'slug')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN slug VARCHAR(255) NULL UNIQUE KEY"

                );

                $this->success[] = "Added slug column to portfolio";

                echo "  ✓ Added slug column\n";



                // Backfill slugs

                $this->backfillSlugs($table, 'name');

                echo "  ✓ Backfilled slugs from name\n";

            } else {

                $this->warnings[] = "portfolio.slug already exists";

            }



            // Add featured column

            if (!$this->hasColumn($table, 'featured')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER active"

                );

                $this->success[] = "Added featured column to portfolio";

                echo "  ✓ Added featured column\n";

            }



            // Add media columns

            if (!$this->hasColumn($table, 'image_path')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN image_path VARCHAR(255) NULL AFTER featured"

                );

                $this->success[] = "Added image_path to portfolio";

                echo "  ✓ Added image_path column\n";

            }



            if (!$this->hasColumn($table, 'image_size')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN image_size INT NULL AFTER image_path"

                );

                $this->success[] = "Added image_size to portfolio";

                echo "  ✓ Added image_size column\n";

            }



            if (!$this->hasColumn($table, 'image_mime')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN image_mime VARCHAR(50) NULL AFTER image_size"

                );

                $this->success[] = "Added image_mime to portfolio";

                echo "  ✓ Added image_mime column\n";

            }



            // Add indexes

            $this->addIndexIfNotExists($table, 'idx_portfolio_slug', ['slug']);

            $this->addIndexIfNotExists($table, 'idx_portfolio_featured', ['featured']);



        } catch (\Exception $e) {

            $this->errors[] = "Portfolio migration failed: " . $e->getMessage();

        }

    }



    private function migrateTestimonials()

    {

        echo "\n💬 Migrating testimonials table...\n";



        try {

            $table = 'testimonials';



            // Add slug column

            if (!$this->hasColumn($table, 'slug')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN slug VARCHAR(255) NULL UNIQUE KEY"

                );

                $this->success[] = "Added slug column to testimonials";

                echo "  ✓ Added slug column\n";



                // Backfill slugs

                $this->backfillSlugs($table, 'author_name');

                echo "  ✓ Backfilled slugs from author_name\n";

            }



            // Add featured column

            if (!$this->hasColumn($table, 'featured')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER active"

                );

                $this->success[] = "Added featured column to testimonials";

                echo "  ✓ Added featured column\n";

            }



            // Add media columns

            if (!$this->hasColumn($table, 'avatar_path')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN avatar_path VARCHAR(255) NULL AFTER featured"

                );

                $this->success[] = "Added avatar_path to testimonials";

                echo "  ✓ Added avatar_path column\n";

            }



            if (!$this->hasColumn($table, 'avatar_size')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN avatar_size INT NULL AFTER avatar_path"

                );

                $this->success[] = "Added avatar_size to testimonials";

                echo "  ✓ Added avatar_size column\n";

            }



            if (!$this->hasColumn($table, 'avatar_mime')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN avatar_mime VARCHAR(50) NULL AFTER avatar_size"

                );

                $this->success[] = "Added avatar_mime to testimonials";

                echo "  ✓ Added avatar_mime column\n";

            }



            // Add indexes

            $this->addIndexIfNotExists($table, 'idx_testimonials_slug', ['slug']);

            $this->addIndexIfNotExists($table, 'idx_testimonials_featured', ['featured']);



        } catch (\Exception $e) {

            $this->errors[] = "Testimonials migration failed: " . $e->getMessage();

        }

    }



    private function migrateFAQ()

    {

        echo "\n❓ Migrating faq table...\n";



        try {

            $table = 'faq';



            // Add slug column

            if (!$this->hasColumn($table, 'slug')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN slug VARCHAR(255) NULL UNIQUE KEY"

                );

                $this->success[] = "Added slug column to faq";

                echo "  ✓ Added slug column\n";



                // Backfill slugs

                $this->backfillSlugs($table, 'question');

                echo "  ✓ Backfilled slugs from question\n";

            }



            // Add featured column

            if (!$this->hasColumn($table, 'featured')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER active"

                );

                $this->success[] = "Added featured column to faq";

                echo "  ✓ Added featured column\n";

            }



            // Add indexes

            $this->addIndexIfNotExists($table, 'idx_faq_slug', ['slug']);

            $this->addIndexIfNotExists($table, 'idx_faq_featured', ['featured']);



        } catch (\Exception $e) {

            $this->errors[] = "FAQ migration failed: " . $e->getMessage();

        }

    }



    private function migrateContentBlocks()

    {

        echo "\n📝 Migrating content_blocks table...\n";



        try {

            $table = 'content_blocks';



            // Add slug column

            if (!$this->hasColumn($table, 'slug')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN slug VARCHAR(255) NULL UNIQUE KEY"

                );

                $this->success[] = "Added slug column to content_blocks";

                echo "  ✓ Added slug column\n";



                // Backfill slugs

                $this->backfillSlugs($table, 'name');

                echo "  ✓ Backfilled slugs from name\n";

            }



            // Add featured column

            if (!$this->hasColumn($table, 'featured')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER active"

                );

                $this->success[] = "Added featured column to content_blocks";

                echo "  ✓ Added featured column\n";

            }



            // Add indexes

            $this->addIndexIfNotExists($table, 'idx_content_blocks_slug', ['slug']);

            $this->addIndexIfNotExists($table, 'idx_content_blocks_featured', ['featured']);



        } catch (\Exception $e) {

            $this->errors[] = "Content blocks migration failed: " . $e->getMessage();

        }

    }



    private function migrateServices()

    {

        echo "\n🔧 Migrating services table...\n";



        try {

            $table = 'services';



            // Add media columns (for future use)

            if (!$this->hasColumn($table, 'image_path')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN image_path VARCHAR(255) NULL"

                );

                $this->success[] = "Added image_path to services";

                echo "  ✓ Added image_path column\n";

            }



            if (!$this->hasColumn($table, 'image_size')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN image_size INT NULL"

                );

                $this->success[] = "Added image_size to services";

                echo "  ✓ Added image_size column\n";

            }



            if (!$this->hasColumn($table, 'image_mime')) {

                $this->connection->statement(

                    "ALTER TABLE $table ADD COLUMN image_mime VARCHAR(50) NULL"

                );

                $this->success[] = "Added image_mime to services";

                echo "  ✓ Added image_mime column\n";

            }



        } catch (\Exception $e) {

            $this->errors[] = "Services migration failed: " . $e->getMessage();

        }

    }



    private function addForeignKeys()

    {

        echo "\n🔗 Adding foreign keys...\n";



        try {

            // Check and add missing FK constraints

            $fks = [

                [

                    'table' => 'form_submission_values',

                    'column' => 'form_submission_id',

                    'references_table' => 'form_submissions',

                    'references_column' => 'id',

                    'name' => 'fk_fsv_form_submission'

                ],

                [

                    'table' => 'form_submission_values',

                    'column' => 'form_field_id',

                    'references_table' => 'form_fields',

                    'references_column' => 'id',

                    'name' => 'fk_fsv_form_field'

                ],

                [

                    'table' => 'orders',

                    'column' => 'form_submission_id',

                    'references_table' => 'form_submissions',

                    'references_column' => 'id',

                    'name' => 'fk_orders_form_submission'

                ],

                [

                    'table' => 'order_notes',

                    'column' => 'admin_user_id',

                    'references_table' => 'admin_users',

                    'references_column' => 'id',

                    'name' => 'fk_order_notes_admin_user'

                ]

            ];



            foreach ($fks as $fk) {

                if (!$this->foreignKeyExists($fk['table'], $fk['name'])) {

                    try {

                        $this->connection->statement(

                            "ALTER TABLE {$fk['table']} ADD CONSTRAINT {$fk['name']} 

                             FOREIGN KEY ({$fk['column']}) REFERENCES {$fk['references_table']}({$fk['references_column']}) 

                             ON DELETE CASCADE ON UPDATE CASCADE"

                        );

                        $this->success[] = "Added FK constraint: {$fk['name']}";

                        echo "  ✓ Added {$fk['name']}\n";

                    } catch (\Exception $e) {

                        // May fail if column doesn't exist, that's ok

                        $this->warnings[] = "Could not add FK {$fk['name']}: " . $e->getMessage();

                    }

                }

            }



        } catch (\Exception $e) {

            $this->warnings[] = "Foreign key migration had issues: " . $e->getMessage();

        }

    }



    private function verify()

    {

        echo "\n✅ Verifying migration...\n";



        $tables = [

            'portfolio' => ['slug', 'featured', 'image_path', 'image_size', 'image_mime'],

            'testimonials' => ['slug', 'featured', 'avatar_path', 'avatar_size', 'avatar_mime'],

            'faq' => ['slug', 'featured'],

            'content_blocks' => ['slug', 'featured'],

            'services' => ['image_path', 'image_size', 'image_mime']

        ];



        foreach ($tables as $table => $columns) {

            foreach ($columns as $column) {

                if ($this->hasColumn($table, $column)) {

                    echo "  ✓ $table.$column exists\n";

                } else {

                    $this->errors[] = "Verification failed: $table.$column missing";

                    echo "  ❌ $table.$column missing\n";

                }

            }

        }

    }



    private function hasColumn($table, $column)

    {

        $result = $this->connection->select(

            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 

             WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND TABLE_SCHEMA = DATABASE()",

            [$table, $column]

        );

        return !empty($result);

    }



    private function foreignKeyExists($table, $keyName)

    {

        $result = $this->connection->select(

            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 

             WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND TABLE_SCHEMA = DATABASE()",

            [$table, $keyName]

        );

        return !empty($result);

    }



    private function indexExists($table, $indexName)

    {

        $result = $this->connection->select(

            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 

             WHERE TABLE_NAME = ? AND INDEX_NAME = ? AND TABLE_SCHEMA = DATABASE()",

            [$table, $indexName]

        );

        return !empty($result);

    }



    private function addIndexIfNotExists($table, $indexName, $columns)

    {

        if (!$this->indexExists($table, $indexName)) {

            try {

                $columnList = implode(', ', $columns);

                $this->connection->statement(

                    "ALTER TABLE $table ADD INDEX $indexName ($columnList)"

                );

                $this->success[] = "Added index: $indexName on $table";

                echo "  ✓ Added index $indexName\n";

            } catch (\Exception $e) {

                $this->warnings[] = "Could not add index $indexName: " . $e->getMessage();

            }

        }

    }



    private function backfillSlugs($table, $sourceColumn)

    {

        try {

            // Get all records that need slug

            $records = $this->connection->select(

                "SELECT id, $sourceColumn FROM $table WHERE slug IS NULL OR slug = ''"

            );



            foreach ($records as $record) {

                $slug = $this->generateSlug($record->$sourceColumn);

                $slug = $this->ensureUniqueSlug($table, $slug, $record->id);



                $this->connection->update(

                    "UPDATE $table SET slug = ? WHERE id = ?",

                    [$slug, $record->id]

                );

            }

        } catch (\Exception $e) {

            $this->warnings[] = "Backfill slugs for $table had issues: " . $e->getMessage();

        }

    }



    private function generateSlug($text)

    {

        // Transliterate Cyrillic

        $text = strtr($text, [

            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',

            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',

            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',

            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',

            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',

            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',

            'э' => 'e', 'ю' => 'yu', 'я' => 'ya'

        ]);



        // Lowercase and remove special chars

        $slug = strtolower($text);

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        $slug = trim($slug, '-');



        return $slug;

    }



    private function ensureUniqueSlug($table, $slug, $id)

    {

        $existing = $this->connection->select(

            "SELECT id FROM $table WHERE slug = ? AND id != ?",

            [$slug, $id]

        );



        if (empty($existing)) {

            return $slug;

        }



        // Add numeric suffix

        $counter = 1;

        while (!empty($this->connection->select(

            "SELECT id FROM $table WHERE slug = ? AND id != ?",

            ["$slug-$counter", $id]

        ))) {

            $counter++;

        }



        return "$slug-$counter";

    }

}



// Run migration

$migration = new ContentFieldsMigration();

$success = $migration->run();

exit($success ? 0 : 1);

