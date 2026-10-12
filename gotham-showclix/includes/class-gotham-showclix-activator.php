<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Gotham_Showclix
 * @subpackage Gotham_Showclix/includes
 */

class Gotham_Showclix_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'showclix_events';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			event_id varchar(100) NOT NULL,
			title text NOT NULL,
			description longtext,
			event_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			venue text,
			image_url text,
			listings longtext,
			last_updated datetime DEFAULT CURRENT_TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY event_id (event_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		if ( ! wp_next_scheduled( 'gotham_showclix_cron' ) ) {
            $interval = get_option('gotham_showclix_sync_interval', 'halfhourly');
			wp_schedule_event( time(), $interval, 'gotham_showclix_cron' );
		}
		
		// Flush rewrite rules for our dynamic CPT / routes
		flush_rewrite_rules();
	}

}
