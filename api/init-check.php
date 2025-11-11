<?php
// ========================================
// Database Initialization Check & Fix
// ========================================

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><meta charset='utf-8'><title>Database Check</title>";
echo "<style>body{font-family:sans-serif;padding:20px;background:#f5f5f5}";
echo ".success{color:green}.error{color:red}.warning{color:orange}";
echo "pre{background:#fff;padding:15px;border-radius:8px;overflow:auto}</style></head><body>";
echo "<h1>🔧 Database Integration Check</h1>";

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    
    echo "<h2 class='success'>✅ Config & DB class loaded successfully</h2>";
    
    $db = new Database();
    echo "<h2 class='success'>✅ Database connected successfully</h2>";
    
    echo "<h3>Database Info:</h3>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
    echo "<li><strong>Database:</strong> " . DB_NAME . "</li>";
    echo "<li><strong>User:</strong> " . DB_USER . "</li>";
    echo "<li><strong>Charset:</strong> " . DB_CHARSET . "</li>";
    echo "</ul>";
    
    // Check tables
    echo "<h3>Tables Status:</h3>";
    $tables = ['settings', 'services', 'portfolio', 'testimonials', 'faq', 'orders', 'content_blocks'];
    echo "<table border='1' cellpadding='10' cellspacing='0' style='background:#fff;border-collapse:collapse'>";
    echo "<tr><th>Table</th><th>Total Records</th><th>Active Records</th><th>Status</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $total = $db->getCount($table);
            $active = $db->getCount($table, ['active' => 1]);
            
            $status = $total > 0 ? "✅ OK" : "⚠️ Empty";
            $statusClass = $total > 0 ? "success" : "warning";
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$total</td>";
            echo "<td>$active</td>";
            echo "<td class='$statusClass'>$status</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td colspan='3' class='error'>❌ Error: " . $e->getMessage() . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // Sample data
    echo "<h3>Sample Data:</h3>";
    
    // Services
    echo "<h4>Services (first 3):</h4>";
    try {
        $services = $db->getRecords('services', ['active' => 1], 'sort_order', 3);
        if (count($services) > 0) {
            echo "<ul>";
            foreach ($services as $s) {
                echo "<li><strong>" . htmlspecialchars($s['name']) . "</strong> (ID: " . $s['id'] . ", Active: " . ($s['active'] ? 'Yes' : 'No') . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='warning'>⚠️ No active services found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // FAQ
    echo "<h4>FAQ (first 3):</h4>";
    try {
        $faq = $db->getRecords('faq', ['active' => 1], 'sort_order', 3);
        if (count($faq) > 0) {
            echo "<ul>";
            foreach ($faq as $f) {
                $question = mb_substr($f['question'], 0, 80);
                echo "<li><strong>" . htmlspecialchars($question) . "...</strong> (ID: " . $f['id'] . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='warning'>⚠️ No active FAQ found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // Testimonials
    echo "<h4>Testimonials (first 3):</h4>";
    try {
        $testimonials = $db->getRecords('testimonials', ['active' => 1, 'approved' => 1], 'sort_order', 3);
        if (count($testimonials) > 0) {
            echo "<ul>";
            foreach ($testimonials as $t) {
                echo "<li><strong>" . htmlspecialchars($t['name']) . "</strong> (Rating: " . ($t['rating'] ?? 5) . "⭐)</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='warning'>⚠️ No active testimonials found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // API Endpoints Test
    echo "<h3>API Endpoints Test:</h3>";
    $baseUrl = 'https://ch167436.tw1.ru/api';
    
    echo "<ul>";
    echo "<li><a href='$baseUrl/test.php' target='_blank'>Test API (Diagnostics)</a></li>";
    echo "<li><a href='$baseUrl/settings.php' target='_blank'>Settings API</a></li>";
    echo "<li><a href='$baseUrl/services.php' target='_blank'>Services API</a></li>";
    echo "<li><a href='$baseUrl/portfolio.php' target='_blank'>Portfolio API</a></li>";
    echo "<li><a href='$baseUrl/testimonials.php' target='_blank'>Testimonials API</a></li>";
    echo "<li><a href='$baseUrl/faq.php' target='_blank'>FAQ API</a></li>";
    echo "<li><a href='$baseUrl/orders.php' target='_blank'>Orders API (GET)</a></li>";
    echo "</ul>";
    
    // Fix suggestions
    echo "<h3>🔧 Fix Actions:</h3>";
    
    $emptyTables = [];
    foreach ($tables as $table) {
        try {
            $count = $db->getCount($table);
            if ($count === 0) {
                $emptyTables[] = $table;
            }
        } catch (Exception $e) {
            // Skip
        }
    }
    
    if (count($emptyTables) > 0) {
        echo "<p class='warning'>⚠️ Some tables are empty: <strong>" . implode(', ', $emptyTables) . "</strong></p>";
        echo "<p>To populate these tables with default data, run:</p>";
        echo "<p><a href='init-database.php' style='display:inline-block;background:#007bff;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px'>Initialize Database</a></p>";
    } else {
        echo "<p class='success'>✅ All tables have data</p>";
    }
    
    // Update active=1 for all records
    echo "<h4>Quick Fix: Set all records to active=1</h4>";
    if (isset($_GET['fix_active'])) {
        echo "<div style='background:#fff;padding:15px;border-radius:8px;margin:15px 0'>";
        foreach ($tables as $table) {
            if ($table === 'settings' || $table === 'orders') continue; // Skip these
            
            try {
                $pdo = $db->getPDO();
                $stmt = $pdo->prepare("UPDATE `$table` SET active = 1 WHERE 1=1");
                $stmt->execute();
                $affected = $stmt->rowCount();
                echo "<p class='success'>✅ Updated $table: $affected records set to active=1</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error updating $table: " . $e->getMessage() . "</p>";
            }
        }
        
        // Update approved=1 for testimonials
        try {
            $pdo = $db->getPDO();
            $stmt = $pdo->prepare("UPDATE testimonials SET approved = 1 WHERE 1=1");
            $stmt->execute();
            $affected = $stmt->rowCount();
            echo "<p class='success'>✅ Updated testimonials: $affected records set to approved=1</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        echo "<p><a href='init-check.php'>Refresh to see changes</a></p>";
        echo "</div>";
    } else {
        echo "<p><a href='init-check.php?fix_active=1' style='display:inline-block;background:#28a745;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px'>Fix: Set all to active=1</a></p>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Summary</h3>";
    echo "<p><strong>Database integration is working!</strong></p>";
    echo "<p>Next steps:</p>";
    echo "<ol>";
    echo "<li>Check that API endpoints return correct JSON (click links above)</li>";
    echo "<li>Clear browser cache and localStorage</li>";
    echo "<li>Open website and check Console (F12) for API logs</li>";
    echo "<li>Test forms and data loading</li>";
    echo "</ol>";
    
    $db->close();
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Database connection failed</h2>";
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    echo "<h3>Possible fixes:</h3>";
    echo "<ul>";
    echo "<li>Check database credentials in <code>api/config.php</code></li>";
    echo "<li>Make sure MySQL database exists</li>";
    echo "<li>Make sure database tables are created (run schema.sql)</li>";
    echo "<li>Check database user permissions</li>";
    echo "</ul>";
}

echo "</body></html>";
