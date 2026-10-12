<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    Gotham_Showclix
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'showclix_events';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
