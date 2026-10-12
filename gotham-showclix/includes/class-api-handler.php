<?php

class Gotham_Showclix_API_Handler {
    
    private $token;
    private $seller_id;
    private $base_url = 'https://events.leapevents.com/api/events';

    public function __construct() {
        $this->token = get_option('gotham_showclix_api_token', '');
        $this->seller_id = get_option('gotham_showclix_seller_id', '');
    }

    public function get_upcoming_events() {
        if (empty($this->token)) {
            return false;
        }

        $transient_key = 'gotham_showclix_events_master_v2';
        $cached = get_transient($transient_key);
        if ($cached !== false) {
            return $cached;
        }

        // Build filter: always filter upcoming; add seller filter if seller_id is set
        $filter_parts = array('upcoming=true');
        if (!empty($this->seller_id)) {
            $filter_parts[] = 'seller=' . $this->seller_id;
        }
        $filter_string = implode(',', $filter_parts);

        $all_events = array();
        $page       = 1;
        $per_page   = 100;

        // Paginate through all results
        do {
            $url = add_query_arg(
                array(
                    'filter' => $filter_string,
                    'limit'  => $per_page,
                    'offset' => ($page - 1) * $per_page,
                ),
                $this->base_url
            );

            $response = wp_remote_get($url, array(
                'headers' => array(
                    'x-api-token' => $this->token,
                    'Accept'      => 'application/vnd.api+json'
                ),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                Gotham_Showclix_Sync_Manager::log("HTTP Error (page {$page}): " . $response->get_error_message());
                break;
            }

            $body = wp_remote_retrieve_body($response);
            $code = wp_remote_retrieve_response_code($response);

            if ($code != 200) {
                Gotham_Showclix_Sync_Manager::log("API Error $code (page {$page}). Body: " . substr($body, 0, 200));
                break;
            }

            $data = json_decode($body, true);

            // Support both wrapped {data:[]} and unwrapped [] responses
            if (isset($data['data']) && is_array($data['data'])) {
                $page_events = $data['data'];
            } elseif (is_array($data) && isset($data[0])) {
                $page_events = $data;
            } else {
                Gotham_Showclix_Sync_Manager::log("API page {$page} returned empty or unparseable. Payload: " . substr($body, 0, 300));
                break;
            }

            $all_events = array_merge($all_events, $page_events);

            // Check if there are more pages
            $total_pages = isset($data['page']['total_pages']) ? (int) $data['page']['total_pages'] : 1;
            Gotham_Showclix_Sync_Manager::log("Fetched page {$page}/{$total_pages}: " . count($page_events) . " events.");

            $page++;
        } while ($page <= $total_pages);

        if (empty($all_events)) {
            Gotham_Showclix_Sync_Manager::log("API returned 0 events total.");
            return array();
        }

        set_transient($transient_key, $all_events, 5 * MINUTE_IN_SECONDS);
        return $all_events;
    }

    public function get_series_children($series_id) {
        if (empty($this->token)) {
            return false;
        }

        $transient_key = 'gotham_showclix_series_children_' . $series_id;
        $cached = get_transient($transient_key);
        if ($cached !== false) {
            return $cached;
        }

        $all_children = array();
        $page         = 1;
        $per_page     = 100;

        // Filter: upcoming children of this series only
        $filter_string = 'series=' . $series_id . ',upcoming=true';

        do {
            $url = add_query_arg(
                array(
                    'filter' => $filter_string,
                    'limit'  => $per_page,
                    'offset' => ($page - 1) * $per_page,
                ),
                $this->base_url
            );

            $response = wp_remote_get($url, array(
                'headers' => array(
                    'x-api-token' => $this->token,
                    'Accept'      => 'application/vnd.api+json'
                ),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                Gotham_Showclix_Sync_Manager::log("Series {$series_id} HTTP Error (page {$page}): " . $response->get_error_message());
                break;
            }

            $body = wp_remote_retrieve_body($response);
            $code = wp_remote_retrieve_response_code($response);

            if ($code != 200) {
                Gotham_Showclix_Sync_Manager::log("Series {$series_id} API Error {$code} (page {$page}).");
                break;
            }

            $data = json_decode($body, true);

            if (isset($data['data']) && is_array($data['data'])) {
                $page_children = $data['data'];
            } elseif (is_array($data) && isset($data[0])) {
                $page_children = $data;
            } else {
                Gotham_Showclix_Sync_Manager::log("Series {$series_id} page {$page} empty or unparseable.");
                break;
            }

            $all_children = array_merge($all_children, $page_children);

            $total_pages = isset($data['page']['total_pages']) ? (int) $data['page']['total_pages'] : 1;
            Gotham_Showclix_Sync_Manager::log("Series {$series_id} page {$page}/{$total_pages}: " . count($page_children) . " children.");

            $page++;
        } while ($page <= $total_pages);

        if (empty($all_children)) {
            return false;
        }

        set_transient($transient_key, $all_children, 5 * MINUTE_IN_SECONDS);
        return $all_children;
    }
}
