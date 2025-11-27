#!/usr/bin/env php
<?php
/**
 * Process Order Queue
 * 
 * Processes orders that failed to send to Telegram
 * and were queued for retry.
 * 
 * Run via cron: * * * * * php /path/to/process-order-queue.php
 */

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

require_once __DIR__ . '/php/TelegramBot.php';

class QueueProcessor {
    private $queueFile;
    private $logFile;
    private $maxAttempts = 5;
    
    public function __construct() {
        $this->queueFile = __DIR__ . '/storage/cache/order_queue.json';
        $this->logFile = __DIR__ . '/storage/logs/orders.log';
    }
    
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND);
    }
    
    private function loadQueue() {
        if (!file_exists($this->queueFile)) {
            return [];
        }
        
        $content = file_get_contents($this->queueFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveQueue($queue) {
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function processQueue() {
        $queue = $this->loadQueue();
        
        if (empty($queue)) {
            $this->log('INFO', 'Queue is empty, nothing to process');
            return;
        }
        
        $this->log('INFO', 'Processing queue', ['items' => count($queue)]);
        
        $telegramBot = new TelegramBot();
        $newQueue = [];
        $processedCount = 0;
        $failedCount = 0;
        
        foreach ($queue as $item) {
            $attempts = $item['attempts'] ?? 0;
            
            // Skip if max attempts reached
            if ($attempts >= $this->maxAttempts) {
                $this->log('WARNING', 'Max attempts reached, removing from queue', [
                    'order_id' => $item['id'],
                    'attempts' => $attempts
                ]);
                $failedCount++;
                continue;
            }
            
            // Format message
            $orderData = $item['data'];
            
            $message = "📋 <b>Новый заказ с сайта!</b>\n\n";
            $message .= "👤 <b>Имя:</b> " . htmlspecialchars($orderData['name']) . "\n";
            $message .= "📧 <b>Email:</b> " . htmlspecialchars($orderData['email']) . "\n";
            $message .= "📱 <b>Телефон:</b> " . htmlspecialchars($orderData['phone']) . "\n\n";
            $message .= "🔧 <b>Услуга:</b> " . htmlspecialchars($orderData['service']) . "\n\n";
            $message .= "📝 <b>Описание:</b>\n" . htmlspecialchars($orderData['description']) . "\n\n";
            
            if (!empty($orderData['files'])) {
                $message .= "📎 <b>Файлы:</b> " . count($orderData['files']) . " шт.\n";
                foreach ($orderData['files'] as $file) {
                    $message .= "  • {$file['original_name']} (" . round($file['size'] / 1024, 2) . " KB)\n";
                }
                $message .= "\n";
            }
            
            $message .= "⏰ <b>Время:</b> " . $orderData['timestamp'] . "\n";
            $message .= "🌍 <b>IP:</b> " . $orderData['ip'];
            
            // Try to send
            try {
                $responses = $telegramBot->broadcastMessage($message);
                
                // Check if any message was sent successfully
                $successCount = 0;
                foreach ($responses as $response) {
                    if ($response['ok']) {
                        $successCount++;
                    }
                }
                
                if ($successCount === 0) {
                    throw new Exception('Не удалось отправить уведомление ни одному пользователю');
                }
                
                $this->log('INFO', 'Queue item processed successfully', [
                    'order_id' => $item['id'],
                    'attempts' => $attempts + 1,
                    'recipients' => count($responses),
                    'success' => $successCount
                ]);
                
                $processedCount++;
                
            } catch (Exception $e) {
                $this->log('WARNING', 'Queue item processing failed', [
                    'order_id' => $item['id'],
                    'attempts' => $attempts + 1,
                    'error' => $e->getMessage()
                ]);
                
                // Re-queue with incremented attempts
                $item['attempts'] = $attempts + 1;
                $item['last_attempt'] = date('Y-m-d H:i:s');
                $newQueue[] = $item;
            }
        }
        
        // Save updated queue
        $this->saveQueue($newQueue);
        
        $this->log('INFO', 'Queue processing complete', [
            'processed' => $processedCount,
            'failed' => $failedCount,
            'remaining' => count($newQueue)
        ]);
    }
}

// Run processor
$processor = new QueueProcessor();
$processor->processQueue();
