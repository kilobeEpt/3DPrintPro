<?php

namespace App\Services;

/**
 * SSE Broadcaster Service
 * 
 * Broadcasts events to Server-Sent Events clients for real-time updates.
 * Events are stored in a JSON file and picked up by the SSE endpoint.
 */
class SSEBroadcaster
{
    private $eventsFile;
    private $maxEvents = 100;
    
    public function __construct()
    {
        $this->eventsFile = __DIR__ . '/../../storage/cache/sse_events.json';
        $this->ensureEventsFile();
    }
    
    private function ensureEventsFile()
    {
        $dir = dirname($this->eventsFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (!file_exists($this->eventsFile)) {
            file_put_contents($this->eventsFile, json_encode([
                'events' => [],
                'counter' => 0
            ]));
        }
    }
    
    public function broadcast($eventType, $data)
    {
        $this->ensureEventsFile();
        
        $content = file_get_contents($this->eventsFile);
        $eventsData = json_decode($content, true);
        
        if (!$eventsData) {
            $eventsData = ['events' => [], 'counter' => 0];
        }
        
        $eventsData['counter']++;
        
        $event = [
            'id' => $eventsData['counter'],
            'type' => $eventType,
            'data' => $data,
            'timestamp' => time()
        ];
        
        array_unshift($eventsData['events'], $event);
        
        if (count($eventsData['events']) > $this->maxEvents) {
            $eventsData['events'] = array_slice($eventsData['events'], 0, $this->maxEvents);
        }
        
        file_put_contents($this->eventsFile, json_encode($eventsData));
        
        return $event['id'];
    }
    
    public function broadcastContentUpdate($entityType, $entityId, $action = 'updated')
    {
        return $this->broadcast("content.{$action}", [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'timestamp' => time()
        ]);
    }
    
    public function broadcastCacheInvalidation($resource)
    {
        return $this->broadcast('cache.invalidated', [
            'resource' => $resource,
            'timestamp' => time()
        ]);
    }
    
    public function getRecentEvents($lastId = 0, $limit = 50)
    {
        $this->ensureEventsFile();
        
        $content = file_get_contents($this->eventsFile);
        $eventsData = json_decode($content, true);
        
        if (!$eventsData || !isset($eventsData['events'])) {
            return [];
        }
        
        $events = array_filter($eventsData['events'], function($event) use ($lastId) {
            return $event['id'] > $lastId;
        });
        
        return array_slice($events, 0, $limit);
    }
    
    public function clearEvents()
    {
        $this->ensureEventsFile();
        
        file_put_contents($this->eventsFile, json_encode([
            'events' => [],
            'counter' => 0
        ]));
    }
}
