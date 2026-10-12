<?php

class Gotham_Showclix_DB_Manager {
    public function __construct() {
        $this->maybe_upgrade_db();
    }

    private function maybe_upgrade_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id varchar(100) NOT NULL,
            slug varchar(255) DEFAULT '' NOT NULL,
            title text NOT NULL,
            description longtext,
            event_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            venue text,
            image_url text,
            ticket_url text,
            listings longtext,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY event_id (event_id),
            KEY slug (slug)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function upsert_event($event_id, $title, $description, $event_date, $venue, $image_url, $listings, $slug = '', $ticket_url = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE event_id = %s", $event_id));
        
        $data = array(
            'event_id'    => $event_id,
            'title'       => $title,
            'description' => $description,
            'event_date'  => $event_date,
            'venue'       => $venue,
            'image_url'   => $image_url,
            'slug'        => $slug,
            'ticket_url'  => $ticket_url,
            'listings'    => is_array($listings) || is_object($listings) ? wp_json_encode($listings) : $listings,
        );
        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
        
        if ($exists) {
            $wpdb->update($table_name, $data, array('event_id' => $event_id), $format, array('%s'));
        } else {
            $wpdb->insert($table_name, $data, $format);
        }
    }
    
    public function get_events($limit = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        $current_time = current_time('mysql', 1);
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE event_date >= %s ORDER BY event_date ASC", $current_time);
        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d", $limit);
        }
        return $wpdb->get_results($query);
    }
    
    public function get_event($event_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE event_id = %s", $event_id));
    }

    public function get_event_by_slug($slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $slug));
    }

    public function delete_all_events() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        $wpdb->query("TRUNCATE TABLE $table_name");
    }

    public function delete_past_events() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        $current_time = current_time('mysql', 1);
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE event_date < %s", $current_time));
    }

    /**
     * Return all event_id values currently stored in the local DB.
     */
    public function get_all_event_ids() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        return $wpdb->get_col("SELECT event_id FROM $table_name");
    }

    /**
     * Delete a single event from the local DB by its event_id.
     */
    public function delete_event_by_id($event_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'showclix_events';
        return $wpdb->delete($table_name, array('event_id' => $event_id), array('%s'));
    }

    /**
     * Given the current set of active API event IDs, remove any local
     * events that are NOT in that set (i.e., canceled / paused / removed).
     *
     * @param  array $active_ids  Flat array of event_id strings from the latest API pull.
     * @return int   Number of events deleted.
     */
    public function delete_removed_events( array $active_ids ) {
        $local_ids = $this->get_all_event_ids();
        if ( empty( $local_ids ) ) {
            return 0;
        }

        $stale_ids = array_diff( $local_ids, $active_ids );
        $deleted   = 0;
        foreach ( $stale_ids as $stale_id ) {
            $this->delete_event_by_id( $stale_id );
            $deleted++;
        }
        return $deleted;
    }
}
