<?php
/**
 * Server-Sent Events (SSE) Endpoint for Real-Time Updates
 * 
 * Broadcasts cache invalidation events and content changes to connected admin clients.
 * Clients can listen for:
 * - content.updated: When any content is modified
 * - cache.invalidated: When cache is cleared
 * - item.created/updated/deleted: Specific entity changes
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

ob_end_flush();
flush();

$lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? intval($_SERVER['HTTP_LAST_EVENT_ID']) : 0;
$cacheTimestampFile = __DIR__ . '/../storage/cache/content_cache_timestamps.json';
$eventsFile = __DIR__ . '/../storage/cache/sse_events.json';

if (!file_exists(dirname($eventsFile))) {
    mkdir(dirname($eventsFile), 0755, true);
}

if (!file_exists($eventsFile)) {
    file_put_contents($eventsFile, json_encode(['events' => [], 'counter' => 0]));
}

function sendEvent($id, $event, $data) {
    echo "id: {$id}\n";
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

function getRecentEvents($lastId) {
    global $eventsFile;
    
    if (!file_exists($eventsFile)) {
        return [];
    }
    
    $content = file_get_contents($eventsFile);
    $data = json_decode($content, true);
    
    if (!$data || !isset($data['events'])) {
        return [];
    }
    
    return array_filter($data['events'], function($event) use ($lastId) {
        return $event['id'] > $lastId;
    });
}

function checkForUpdates($lastCheckTime) {
    global $cacheTimestampFile;
    
    if (!file_exists($cacheTimestampFile)) {
        return [];
    }
    
    $timestamps = json_decode(file_get_contents($cacheTimestampFile), true);
    if (!$timestamps) {
        return [];
    }
    
    $updates = [];
    foreach ($timestamps as $resource => $timestamp) {
        if ($timestamp > $lastCheckTime) {
            $updates[] = [
                'resource' => $resource,
                'timestamp' => $timestamp
            ];
        }
    }
    
    return $updates;
}

sendEvent(0, 'connected', ['message' => 'Connected to updates stream', 'timestamp' => time()]);

$recentEvents = getRecentEvents($lastEventId);
foreach ($recentEvents as $event) {
    sendEvent($event['id'], $event['type'], $event['data']);
}

$lastCheckTime = time();
$connectionStart = time();
$maxConnectionTime = 300;

while (true) {
    if (connection_aborted()) {
        break;
    }
    
    if ((time() - $connectionStart) > $maxConnectionTime) {
        sendEvent(time(), 'timeout', ['message' => 'Connection timeout, please reconnect']);
        break;
    }
    
    $newEvents = getRecentEvents($lastEventId);
    foreach ($newEvents as $event) {
        $lastEventId = $event['id'];
        sendEvent($event['id'], $event['type'], $event['data']);
    }
    
    $updates = checkForUpdates($lastCheckTime);
    if (!empty($updates)) {
        $eventId = time();
        sendEvent($eventId, 'cache.invalidated', [
            'updates' => $updates,
            'timestamp' => time()
        ]);
    }
    
    $lastCheckTime = time();
    
    sendEvent(time(), 'heartbeat', ['timestamp' => time()]);
    
    sleep(5);
}
