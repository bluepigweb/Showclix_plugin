<?php

class Gotham_Showclix {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'gotham-showclix';
		$this->version = defined('GOTHAM_SHOWCLIX_VERSION') ? GOTHAM_SHOWCLIX_VERSION : '1.0.0';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_cron_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gotham-showclix-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-db-manager.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-api-handler.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sync-manager.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-gotham-showclix-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-gotham-showclix-public.php';

		$this->loader = new Gotham_Showclix_Loader();
	}

	private function define_admin_hooks() {
		$plugin_admin = new Gotham_Showclix_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_post_gotham_showclix_sync', $plugin_admin, 'trigger_manual_sync' );
		$this->loader->add_action( 'admin_post_gotham_showclix_delete_all', $plugin_admin, 'trigger_delete_all_events' );
		$this->loader->add_action( 'admin_post_gotham_showclix_reconcile', $plugin_admin, 'trigger_reconcile_events' );
	}

	private function define_public_hooks() {
		$plugin_public = new Gotham_Showclix_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        
        $this->loader->add_action( 'init', $plugin_public, 'add_rewrite_rules' );
        $this->loader->add_filter( 'query_vars', $plugin_public, 'add_query_vars' );
        $this->loader->add_action( 'template_redirect', $plugin_public, 'template_redirect' );

        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        $this->loader->add_action( 'wp_ajax_gotham_embed_proxy',        $plugin_public, 'embed_proxy' );
        $this->loader->add_action( 'wp_ajax_nopriv_gotham_embed_proxy', $plugin_public, 'embed_proxy' );
	}

    private function define_cron_hooks() {
        $this->loader->add_filter( 'cron_schedules', $this, 'add_cron_interval' );
        $this->loader->add_action( 'gotham_showclix_cron', $this, 'run_scheduled_sync' );
    }

    public function add_cron_interval( $schedules ) {
        $schedules['halfhourly'] = array(
            'interval' => 1800, // 30 minutes in seconds
            'display'  => esc_html__( 'Every 30 Minutes' ),
        );
        return $schedules;
    }

    public function run_scheduled_sync() {
        $sync_manager = new Gotham_Showclix_Sync_Manager();
        $sync_manager->run_sync();
    }

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}

	public static function get_timezone() {
		$tz_string = get_option( 'gotham_showclix_timezone', 'America/New_York' );
		if ( empty( $tz_string ) ) {
			$tz_string = 'America/New_York';
		}
		try {
			return new DateTimeZone( $tz_string );
		} catch ( Exception $e ) {
			return new DateTimeZone( 'America/New_York' );
		}
	}
}
