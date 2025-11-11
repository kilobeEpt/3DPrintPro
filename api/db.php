<?php
// ========================================
// Database Class - Generic CRUD Operations
// ========================================

require_once __DIR__ . '/config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    
    // ========================================
    // Settings Methods
    // ========================================
    
    public function getSetting($key) {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        if ($result && !empty($result['setting_value'])) {
            $decoded = json_decode($result['setting_value'], true);
            return $decoded !== null ? $decoded : $result['setting_value'];
        }
        
        return null;
    }
    
    public function getAllSettings() {
        $stmt = $this->pdo->query('SELECT setting_key, setting_value FROM settings');
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $decoded = json_decode($row['setting_value'], true);
            $settings[$row['setting_key']] = $decoded !== null ? $decoded : $row['setting_value'];
        }
        
        return $settings;
    }
    
    public function saveSetting($key, $value) {
        $jsonValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = ?'
        );
        
        return $stmt->execute([$key, $jsonValue, $jsonValue]);
    }
    
    public function deleteSetting($key) {
        $stmt = $this->pdo->prepare('DELETE FROM settings WHERE setting_key = ?');
        return $stmt->execute([$key]);
    }
    
    // ========================================
    // Generic CRUD Methods
    // ========================================
    
    public function getRecords($table, $where = [], $orderBy = 'sort_order', $limit = null, $offset = 0) {
        // Tables that don't have 'active' column
        $tables_without_active = ['settings', 'orders'];
        
        // Remove 'active' filter for tables that don't have this column
        if (in_array($table, $tables_without_active) && isset($where['active'])) {
            unset($where['active']);
        }
        
        $sql = "SELECT * FROM " . $this->escapeIdentifier($table) . " WHERE 1=1";
        $params = [];
        
        foreach ($where as $key => $value) {
            $sql .= " AND " . $this->escapeIdentifier($key) . " = ?";
            $params[] = $value;
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY " . $this->escapeIdentifier($orderBy);
            // If ordering by sort_order or id, use ASC for sort_order and DESC for others
            if (in_array($orderBy, ['sort_order', 'order'])) {
                $sql .= " ASC";
            } else {
                $sql .= " DESC";
            }
        }
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }
        
        $stmt = $this->pdo->prepare($sql);
        
        // Bind limit and offset as integers
        if ($limit !== null) {
            $paramCount = count($params);
            for ($i = 0; $i < $paramCount - 2; $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            $stmt->bindValue($paramCount - 1, (int)$params[$paramCount - 2], PDO::PARAM_INT);
            $stmt->bindValue($paramCount, (int)$params[$paramCount - 1], PDO::PARAM_INT);
        } else {
            $stmt->execute($params);
        }
        
        if ($limit !== null) {
            $stmt->execute();
        }
        
        $records = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($records as &$record) {
            $record = $this->decodeJsonFields($record);
        }
        
        return $records;
    }
    
    public function getRecordById($table, $id) {
        $sql = "SELECT * FROM " . $this->escapeIdentifier($table) . " WHERE id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        return $record ? $this->decodeJsonFields($record) : null;
    }
    
    public function insertRecord($table, $data) {
        // Encode arrays/objects to JSON
        $data = $this->encodeJsonFields($data);
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO " . $this->escapeIdentifier($table) . " 
                (" . implode(', ', array_map([$this, 'escapeIdentifier'], $columns)) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->pdo->lastInsertId();
    }
    
    public function updateRecord($table, $id, $data) {
        // Encode arrays/objects to JSON
        $data = $this->encodeJsonFields($data);
        
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $sets[] = $this->escapeIdentifier($key) . " = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE " . $this->escapeIdentifier($table) . " 
                SET " . implode(', ', $sets) . " 
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function deleteRecord($table, $id) {
        $sql = "DELETE FROM " . $this->escapeIdentifier($table) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function getCount($table, $where = []) {
        // Tables that don't have 'active' column
        $tables_without_active = ['settings', 'orders'];
        
        // Remove 'active' filter for tables that don't have this column
        if (in_array($table, $tables_without_active) && isset($where['active'])) {
            unset($where['active']);
        }
        
        $sql = "SELECT COUNT(*) as total FROM " . $this->escapeIdentifier($table) . " WHERE 1=1";
        $params = [];
        
        foreach ($where as $key => $value) {
            $sql .= " AND " . $this->escapeIdentifier($key) . " = ?";
            $params[] = $value;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return (int)$result['total'];
    }
    
    // ========================================
    // Helper Methods
    // ========================================
    
    private function escapeIdentifier($identifier) {
        // Remove any backticks and escape
        $identifier = str_replace('`', '', $identifier);
        return '`' . $identifier . '`';
    }
    
    private function encodeJsonFields($data) {
        $encoded = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $encoded[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $encoded[$key] = $value;
            }
        }
        
        return $encoded;
    }
    
    private function decodeJsonFields($record) {
        $decoded = [];
        
        foreach ($record as $key => $value) {
            // Try to decode JSON fields
            if (is_string($value) && !empty($value) && ($value[0] === '{' || $value[0] === '[')) {
                $jsonDecoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decoded[$key] = $jsonDecoded;
                    continue;
                }
            }
            $decoded[$key] = $value;
        }
        
        return $decoded;
    }
    
    public function getPDO() {
        return $this->pdo;
    }
    
    public function close() {
        $this->pdo = null;
    }
}
