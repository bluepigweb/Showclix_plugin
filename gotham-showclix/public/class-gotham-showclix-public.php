<?php

class Gotham_Showclix_Public {

	private $plugin_name;
	private $version;
    private $db;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->db = new Gotham_Showclix_DB_Manager();
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/gotham-showclix-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
        wp_enqueue_script( 'fullcalendar-6', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.19/index.global.min.js', array(), '6.1.19', false );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/gotham-showclix-public.js', array( 'jquery', 'fullcalendar-6' ), $this->version, false );
        
        wp_localize_script( $this->plugin_name, 'gothamShowclix', array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        ));
	}

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^events/([^/]+)/?$',
            'index.php?gotham_event_id=$matches[1]',
            'top'
        );
    }

    public function add_query_vars( $query_vars ) {
        $query_vars[] = 'gotham_event_id';
        return $query_vars;
    }

    public function template_redirect() {
        $slug_or_id = get_query_var( 'gotham_event_id' );
        if ( ! empty( $slug_or_id ) ) {
            $event = $this->db->get_event_by_slug( $slug_or_id );
            
            // Fallback for older URLs
            if ( ! $event ) {
                $event = $this->db->get_event( $slug_or_id );
            }

            if ( $event ) {
                global $gotham_showclix_event;
                $gotham_showclix_event = $event;
                add_filter( 'template_include', array( $this, 'override_event_template' ), 99 );
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
            }
        }
    }

    public function override_event_template( $template ) {
        return plugin_dir_path( __FILE__ ) . 'partials/gotham-showclix-single-event.php';
    }

    public function register_shortcodes() {
        add_shortcode('gotham_events', array($this, 'shortcode_events_list'));
        add_shortcode('gotham_events_calendar', array($this, 'shortcode_events_calendar'));
    }

    public function shortcode_events_list($atts) {
        $atts = shortcode_atts(array(
            'limit' => 0,
        ), $atts, 'gotham_events');

        $limit = intval($atts['limit']);
        $events = $this->db->get_events($limit);
        
        ob_start();
        include plugin_dir_path( __FILE__ ) . 'partials/gotham-showclix-public-display.php';
        return ob_get_clean();
    }

    public function shortcode_events_calendar($atts) {
        // Retrieve events ahead of time to inject into JS for rendering
        $events = $this->db->get_events();
        ob_start();
        include plugin_dir_path( __FILE__ ) . 'partials/gotham-showclix-public-calendar.php';
        return ob_get_clean();
    }

    /**
     * Server-side proxy for LeapEvents API calls.
     * Bypasses CORS when the browser origin doesn't match the whitelisted https origin.
     */
    public function embed_proxy() {
        $url = isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : '';

        // Only allow proxying to leapevents.com
        if ( empty( $url ) || strpos( $url, 'events.leapevents.com' ) === false ) {
            status_header( 400 );
            echo 'Invalid URL';
            exit;
        }

        $method = ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) ? 'POST' : 'GET';
        $args   = array( 'timeout' => 20, 'method' => $method );

        if ( $method === 'POST' ) {
            $args['body']    = file_get_contents( 'php://input' );
            $args['headers'] = array( 'Content-Type' => 'application/json' );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            status_header( 502 );
            echo esc_html( $response->get_error_message() );
            exit;
        }

        $code         = wp_remote_retrieve_response_code( $response );
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        $body         = wp_remote_retrieve_body( $response );

        status_header( $code );
        if ( $content_type ) {
            header( 'Content-Type: ' . $content_type );
        }
        header( 'Access-Control-Allow-Origin: *' );
        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput
        exit;
    }

}
