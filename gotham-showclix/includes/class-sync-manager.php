<?php

class Gotham_Showclix_Sync_Manager {
    private $api;
    private $db;
    
    public function __construct() {
        $this->api = new Gotham_Showclix_API_Handler();
        $this->db = new Gotham_Showclix_DB_Manager();
    }

    public static function log($message) {
        $logs = get_option('gotham_showclix_sync_logs', array());
        if (!is_array($logs)) $logs = array();
        
        $time = current_time('mysql');
        array_unshift($logs, "[$time] $message");
        
        if (count($logs) > 50) {
            $logs = array_slice($logs, 0, 50);
        }
        
        update_option('gotham_showclix_sync_logs', $logs, false);
    }

    public function run_sync() {
        self::log("Sync started.");
        $imported = 0;

        $events = $this->api->get_upcoming_events();

        if ($events === false) {
            self::log("Error: Could not retrieve events from API. Check token and seller_id.");
            return false;
        }

        self::log("Fetched " . count($events) . " master events from API.");

        // Track every event_id returned by the API this cycle
        $active_api_ids = array();

        foreach ($events as $ev) {

            $event_id = isset($ev['id']) ? sanitize_text_field($ev['id']) : '';
            if (empty($event_id)) continue;

            $at = isset($ev['attributes']) ? $ev['attributes'] : array();
            $is_series = isset($at['behavior']['series']) && $at['behavior']['series'] === true;

            if ($is_series) {
                // Extract parent embed URL for series children
                $parent_ticket_url = isset($at['primary_uri']) && !empty($at['primary_uri'])
                    ? esc_url_raw($at['primary_uri'])
                    : '';

                self::log("Series {$event_id} | Parent embed URL: {$parent_ticket_url}");

                $children = $this->api->get_series_children($event_id);
                if ($children && is_array($children)) {
                    foreach ($children as $child) {
                        $child_id = isset($child['id']) ? sanitize_text_field($child['id']) : '';
                        if (empty($child_id)) continue;

                        $active_api_ids[] = $child_id;

                        $r = $this->upsert_event($child, $parent_ticket_url);
                        if ($r) $imported++;
                    }
                }
                // Series parent itself is not a bookable event — skip upserting it
                continue;
            }

            // Non-series: track and upsert
            $active_api_ids[] = $event_id;
            $r = $this->upsert_event($ev);
            if ($r) $imported++;
        }

        self::log("Sync complete. Upserted: {$imported} events.");

        // Remove any local events that are no longer in the API (canceled / paused)
        $active_api_ids = array_unique($active_api_ids);
        $removed = $this->db->delete_removed_events($active_api_ids);
        if ($removed > 0) {
            self::log("Reconcile: removed {$removed} stale/canceled event(s) from local DB.");
        }

        // Clean up past events from local DB
        $this->db->delete_past_events();

        return $imported;
    }

    private function upsert_event($ev, $fallback_ticket_url = '') {
        $event_id = isset($ev['id']) ? sanitize_text_field($ev['id']) : '';
        if (empty($event_id)) return false;

        $at = isset($ev['attributes']) ? $ev['attributes'] : array();

        $start = isset($at['start']) ? sanitize_text_field($at['start']) : '';
        if (empty($start)) return false;
        
        $title = isset($at['name']) ? wp_kses_post($at['name']) : 'Untitled Event';
        $description = isset($at['description']) ? wp_kses_post($at['description']) : '';
        $event_date = $start;
        // The ShowClix API sometimes provides event image/venue inside attributes, we map defaults if missing
        $venue_name = 'Gotham Comedy Club'; 
        $image_url = isset($at['image']) ? esc_url_raw($at['image']) : '';
        $listings = array();

        $slug = isset($at['short_name']) && !empty($at['short_name']) ? sanitize_title($at['short_name']) : sanitize_title($title . '-' . $event_id);

        // Use child's own primary_uri only if it contains /embed/ (i.e. it IS an embed URL with UUID)
        // Otherwise fall back to the parent series embed URL
        $child_uri = isset($at['primary_uri']) ? esc_url_raw($at['primary_uri']) : '';
        if (!empty($child_uri) && strpos($child_uri, '/embed/') !== false) {
            $ticket_url = $child_uri;
        } elseif (!empty($fallback_ticket_url) && strpos($fallback_ticket_url, '/embed/') !== false) {
            $ticket_url = $fallback_ticket_url;
        } else {
            $ticket_url = $child_uri; // last resort — store whatever we have
        }

        self::log("Event {$event_id} | Ticket URL: {$ticket_url}");

        $parsed_date = date('Y-m-d H:i:s', strtotime($event_date));

        $this->db->upsert_event($event_id, $title, $description, $parsed_date, $venue_name, $image_url, $listings, $slug, $ticket_url);
        return true;
    }
}
