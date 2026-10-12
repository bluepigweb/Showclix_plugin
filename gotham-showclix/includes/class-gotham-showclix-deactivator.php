<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Gotham_Showclix
 * @subpackage Gotham_Showclix/includes
 */

class Gotham_Showclix_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook('gotham_showclix_cron');
		flush_rewrite_rules();
	}

}
