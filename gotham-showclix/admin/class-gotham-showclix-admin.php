<?php

class Gotham_Showclix_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
        // Enqueue admin styles later if necessary
        // wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/gotham-showclix-admin.css', array(), $this->version, 'all' );
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'Gotham Showclix', 
			'Gotham Showclix', 
			'manage_options', 
			$this->plugin_name, 
			array($this, 'display_plugin_events_page'),
			'dashicons-tickets-alt',
			25
		);

		add_submenu_page(
			$this->plugin_name,
			'All Events',
			'All Events',
			'manage_options',
			$this->plugin_name,
			array($this, 'display_plugin_events_page')
		);

		add_submenu_page(
			$this->plugin_name,
			'Settings',
			'Settings',
			'manage_options',
			$this->plugin_name . '-settings',
			array($this, 'display_plugin_setup_page')
		);
	}

	public function register_settings() {
		register_setting( 'gotham_showclix_option_group', 'gotham_showclix_api_token', array('sanitize_callback' => 'sanitize_text_field') );
		register_setting( 'gotham_showclix_option_group', 'gotham_showclix_seller_id', array('sanitize_callback' => 'sanitize_text_field') );
        register_setting( 'gotham_showclix_option_group', 'gotham_showclix_sync_interval', array('sanitize_callback' => array($this, 'update_cron_interval')) );
        register_setting( 'gotham_showclix_option_group', 'gotham_showclix_timezone', array('sanitize_callback' => 'sanitize_text_field') );
	}

    public function update_cron_interval($new_interval) {
        $allowed = array('halfhourly', 'hourly', 'twicedaily', 'daily');
        if (!in_array($new_interval, $allowed)) {
            $new_interval = 'halfhourly';
        }

        // Reschedule the cron immediately if interval is changed
        $current_interval = get_option('gotham_showclix_sync_interval', 'halfhourly');
        if ($new_interval !== $current_interval) {
            wp_clear_scheduled_hook('gotham_showclix_cron');
            wp_schedule_event(time(), $new_interval, 'gotham_showclix_cron');
        }

        return $new_interval;
    }

	public function display_plugin_setup_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/gotham-showclix-admin-settings.php';
	}

	public function display_plugin_events_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/gotham-showclix-admin-events.php';
	}

    public function trigger_manual_sync() {
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'gotham_showclix_sync_nonce')) {
            wp_die('Unauthorized');
        }

        // Require necessary files if not already loaded in admin context (though core load_dependencies does this)
        $sync_manager = new Gotham_Showclix_Sync_Manager();
        $count = $sync_manager->run_sync();

        $redirect_url = add_query_arg(
            array('page' => $this->plugin_name . '-settings', 'sync' => ($count !== false ? 'success' : 'failed'), 'count' => (int)$count), 
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function trigger_delete_all_events() {
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'gotham_showclix_delete_all_nonce')) {
            wp_die('Unauthorized');
        }

        $db_manager = new Gotham_Showclix_DB_Manager();
        $db_manager->delete_all_events();

        // Also clear the sync state so dedupe starts fresh
        delete_option('gotham_showclix_state');

        $redirect_url = add_query_arg(
            array('page' => $this->plugin_name . '-settings', 'delete_all' => 'success'), 
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function trigger_reconcile_events() {
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'gotham_showclix_reconcile_nonce')) {
            wp_die('Unauthorized');
        }

        // Clear transient caches so we get a fresh list from the API
        delete_transient('gotham_showclix_events_master_v2');

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-api-handler.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-db-manager.php';

        $api = new Gotham_Showclix_API_Handler();
        $db  = new Gotham_Showclix_DB_Manager();

        $events = $api->get_upcoming_events();

        if ( $events === false ) {
            $redirect_url = add_query_arg(
                array('page' => $this->plugin_name . '-settings', 'reconcile' => 'failed'),
                admin_url('admin.php')
            );
            wp_safe_redirect($redirect_url);
            exit;
        }

        // Build the full list of active event IDs from the API
        $active_api_ids = array();
        foreach ( $events as $ev ) {
            $event_id = isset($ev['id']) ? sanitize_text_field($ev['id']) : '';
            if ( empty($event_id) ) continue;

            $at        = isset($ev['attributes']) ? $ev['attributes'] : array();
            $is_series = isset($at['behavior']['series']) && $at['behavior']['series'] === true;

            if ( $is_series ) {
                // Clear child transient for fresh data
                delete_transient('gotham_showclix_series_children_' . $event_id);
                $children = $api->get_series_children($event_id);
                if ( $children && is_array($children) ) {
                    foreach ( $children as $child ) {
                        $child_id = isset($child['id']) ? sanitize_text_field($child['id']) : '';
                        if ( ! empty($child_id) ) {
                            $active_api_ids[] = $child_id;
                        }
                    }
                }
            } else {
                $active_api_ids[] = $event_id;
            }
        }

        $active_api_ids = array_unique($active_api_ids);
        $removed        = $db->delete_removed_events($active_api_ids);

        Gotham_Showclix_Sync_Manager::log("Manual Reconcile: {$removed} stale/canceled event(s) removed from local DB.");

        $redirect_url = add_query_arg(
            array(
                'page'      => $this->plugin_name . '-settings',
                'reconcile' => 'success',
                'removed'   => (int) $removed,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

}
