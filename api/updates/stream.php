<?php
/**
 * Server-Sent Events (SSE) Stream for Content Updates
 * 
 * Provides real-time cache invalidation events to connected clients.
 * Clients can subscribe to this endpoint to receive notifications when
 * content (services, portfolio, testimonials, FAQ, etc.) is updated.
 */

// SSE headers MUST be set BEFORE any output or other headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Load only necessary dependencies (skip bootstrap.php to avoid SecurityHeaders)
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap/eloquent.php';

use App\Services\ContentCacheService;
use App\Services\SSEBroadcaster;

// Disable output buffering for SSE
if (ob_get_level()) {
    ob_end_clean();
}

$cacheService = new ContentCacheService();
$sseBroadcaster = new SSEBroadcaster();
$clientId = uniqid('client_', true);
$lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? (int)$_SERVER['HTTP_LAST_EVENT_ID'] : null;
$since = isset($_GET['since']) ? (int)$_GET['since'] : ($lastEventId ?? time());

// Send initial connection message
sendEvent([
    'event' => 'connected',
    'clientId' => $clientId,
    'timestamp' => time(),
    'message' => 'Connected to content updates stream'
], 'init');

// Send any cached invalidation events
$cacheEvents = $cacheService->getInvalidationEvents($since);
foreach ($cacheEvents as $event) {
    sendEvent($event, 'invalidate', $event['timestamp']);
}

// Send any SSE broadcaster events
$lastId = $lastEventId ?? 0;
$broadcastEvents = $sseBroadcaster->getRecentEvents($lastId);
foreach ($broadcastEvents as $event) {
    if ($event['type'] === 'cache.invalidated') {
        sendEvent([
            'resource' => $event['data']['resource'],
            'timestamp' => $event['data']['timestamp'],
            'event' => 'invalidate'
        ], 'invalidate', $event['id']);
    } elseif (strpos($event['type'], 'content.') === 0) {
        sendEvent([
            'resource' => $event['data']['entity_type'],
            'action' => $event['data']['action'],
            'entity_id' => $event['data']['entity_id'],
            'timestamp' => $event['data']['timestamp'],
            'event' => 'content_changed'
        ], 'content_changed', $event['id']);
    }
    $lastId = max($lastId, $event['id']);
}

// Keep connection alive and check for updates every 2 seconds
$maxDuration = 300; // 5 minutes max connection
$startTime = time();
$checkInterval = 2; // Check every 2 seconds
$lastCheckTime = time();

while (true) {
    // Check if connection is still open
    if (connection_aborted()) {
        break;
    }
    
    // Check for max duration
    if (time() - $startTime > $maxDuration) {
        sendEvent([
            'event' => 'timeout',
            'message' => 'Connection timeout, please reconnect'
        ], 'timeout');
        break;
    }
    
    // Check for new SSE broadcaster events
    $newBroadcastEvents = $sseBroadcaster->getRecentEvents($lastId);
    
    if (!empty($newBroadcastEvents)) {
        foreach ($newBroadcastEvents as $event) {
            if ($event['type'] === 'cache.invalidated') {
                sendEvent([
                    'resource' => $event['data']['resource'],
                    'timestamp' => $event['data']['timestamp'],
                    'event' => 'invalidate'
                ], 'invalidate', $event['id']);
            } elseif (strpos($event['type'], 'content.') === 0) {
                sendEvent([
                    'resource' => $event['data']['entity_type'],
                    'action' => $event['data']['action'],
                    'entity_id' => $event['data']['entity_id'],
                    'timestamp' => $event['data']['timestamp'],
                    'event' => 'content_changed'
                ], 'content_changed', $event['id']);
            }
            $lastId = max($lastId, $event['id']);
        }
    }
    
    // Send heartbeat every 30 seconds
    $currentTime = time();
    if ($currentTime - $lastCheckTime >= 30) {
        sendEvent([
            'event' => 'heartbeat',
            'timestamp' => $currentTime
        ], 'heartbeat');
        $lastCheckTime = $currentTime;
    }
    
    // Flush output
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
    
    // Wait before next check
    sleep($checkInterval);
}

/**
 * Send SSE event to client
 * 
 * @param array $data Event data
 * @param string $eventName Event name
 * @param int|null $id Event ID
 * @return void
 */
function sendEvent($data, $eventName = 'message', $id = null)
{
    if ($id !== null) {
        echo "id: {$id}\n";
    }
    
    echo "event: {$eventName}\n";
    echo "data: " . json_encode($data) . "\n\n";
    
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}
