<?php

/**
 * Main class to propagate wiki changes to all language sites.
 *
 * @since 0.1.0
 */
class JPry_Wiki_Propagation extends JPry_Abstract_Translation_Propagation {

	/**
	 * The args used to send a post request.
	 *
	 * @var array
	 */
	protected $args = array();

	/**
	 * The current site when iterating through sites.
	 *
	 * @var stdClass
	 */
	protected $current_site = null;

	/**
	 * The data to be sent with each request.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The relative URL endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $endpoint = 'wiki';

	/**
	 * The post meta key for the language site meta.
	 *
	 * @var string
	 */
	protected $meta_key;

	/**
	 * The language site meta prefix.
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	protected $prefix = 'language_site_';

	/**
	 * Whether to do logging.
	 *
	 * @since 0.2.0
	 *
	 * @var bool
	 */
	protected $logging = false;

	/**
	 * Data that should not be updated.
	 *
	 * @var array
	 */
	protected $no_update = array(
		'post_content' => 1,
		'post_name'    => 1,
	);

	/**
	 * All meta for the current post object.
	 *
	 * @var array
	 */
	protected $post_meta = array();

	/**
	 * The post object being processed.
	 *
	 * @var WP_Post
	 * @since 0.1.0
	 */
	protected $post_object = null;

	/**
	 * The post type of the post being processed.
	 *
	 * @var string
	 */
	protected $post_type = null;

	/**
	 * The current site when looping through sites.
	 *
	 * @var array
	 */
	protected $site = null;

	/**
	 * The Log ID
	 *
	 * @since 0.2.0
	 * @var int
	 */
	protected $log_id = null;

	/**
	 * Array of labels for logs.
	 * @since 0.1.0
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $path      The site path.
	 * @param array  $tax_array Multidimensional array of taxonomy terms and mappings.
	 */
	private function add_taxonomies( $post_id, $path, $tax_array ) {
		foreach ( $tax_array as $taxonomy => $mapping ) {
			$terms = array();
			foreach ( $mapping as $term_id => $languages ) {
				$key = "{$this->prefix}{$path}";
				if ( isset( $languages[ $key ] ) ) {
					$terms[] = $languages[ $key ][0];
				}
			}

			wp_set_object_terms( $post_id, $terms, $taxonomy );
		}
	}

	/**
	 * Delete language posts when the main site post is deleted.
	 *
	 * This uses post meta to determine the post ID for each language site.
	 *
	 * @since 0.2.0
	 * @var array
	 */
	protected $log_labels = array( 'general', 'propagation' );

	/**
	 * The title to use for the log.
	 *
	 * @var string
	 */
	protected $log_title = '';

	/**
	 * Get terms for each taxonomy.
	 *
	 * @var JPry_Translation_Propagation
	 */
	protected $translation_propagation;

	/**
	 * Constructor.
	 *
	 * Hooks up our actions.
	 *
	 * @param JPry_Translation_Propagation $translation_propagation The main class object.
	 */
	public function __construct( JPry_Translation_Propagation $translation_propagation ) {
		$this->logging                 = class_exists( 'JPry_Log_Post' );
		$this->translation_propagation = $translation_propagation;
		add_action( 'wds_async_save_post', array( $this, 'handle_data' ), 10, 2 );
		add_action( 'wds_async_nopriv_save_post', array( $this, 'handle_data' ), 10, 2 );
		add_filter( 'wds_log_post_log_types', array( $this, 'add_log_post_labels' ) );
		add_action( 'wds_async_before_delete_post', array( $this, 'delete_language_posts' ), 10, 2 );
	}

	/**
	 * Delete language posts when the main site post is deleted.
	 *
	 * This uses post meta to determine the post ID for each language site.
	 *
	 * @since 0.2.0
	 *
	 * @param array  $meta      The array of post meta from the original post.
	 * @param string $post_type The post type that is being deleted.
	 */
	public function delete_language_posts( $meta, $post_type ) {
		$this->post_meta = $meta;
		$this->post_type = $post_type;

		// Start logging for the request.
		$this->log_title = 'Deleting wikis';
		$this->log( $this->log_title, 'Beginning...' );

		// Set up the request args
		$args = array(
			'body'    => json_encode( array(
				'force'      => true,
				'wds_log_id' => $this->log_id,
			) ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'method'  => 'DELETE',
		);
		$args = JPry_Utility::deep_parse_args( $args, $this->get_default_api_args() );

		foreach ( $this->sites() as $this->site ) {
			$key   = "{$this->prefix}{$this->site['path']}";
			$value = isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : null;

			if ( null !== $value ) {
				$this->post_to_site( $args );
			} else {
				$this->log( $this->log_title, "No linked ID found for the [{$this->site['path']}] site. Continuing..." );
			}
		}

		$this->log_complete( $this->log_title, 'Wikis have finished being deleted.' );
	}

	/**
	 * Handle distributing the data to all of the child sites.
	 *
	 * @since 0.1.0
	 *
	 * @param int     $post_id The Post ID.
	 * @param WP_Post $post    The Post object.
	 */
	public function handle_data( $post_id, $post ) {
		$this->ensure_connector();

		// Set the initial object data.
		$this->post_object = $post;
		$this->post_type   = $post->post_type;

		// Initialize the post meta for this object.
		$this->post_meta = get_post_meta( $post_id );

		// Start the logging for this request
		$this->log_title = sprintf( __( 'Sending Wiki "%s" (%d) data to language sites', 'jpry-translation-propagation' ), $post->post_title, $post_id );
		$this->log( $this->log_title, sprintf( __( 'Beginning transfer of post "%s" (%d)', 'jpry-translation-propagation' ), $post->post_title, $post_id ) );

		// Set the propagation meta
		$meta_count = count( $this->sites() ) - 1;
		add_post_meta( $post_id, '_jpry_propagation', true, true );
		update_post_meta( $post_id, '_jpry_propagation_count', $meta_count );
		update_post_meta( $post_id, '_jpry_propagation_logging_id', $this->log_id );

		// Set up custom post data.
		$data = array(
			'wds_propagating' => true,
			'wds_log_id'      => $this->log_id,
		);
		$extra_meta = array(
			array(
				'key'   => 'wds_english_id',
				'value' => array( $this->post_object->ID ),
			),
		);

		// Use the connector to set up the remaining post data.
		$connector = new JPry_Network_Connect_JSON_Connect();
		$connector->setup_post_to_sync( $this->post_object );
		$this->data = array_merge( $connector->parse_data_for_json( $this->post_object ), $data );
		$this->data['post_meta'] = array_merge( $this->data['post_meta'], $extra_meta );

		$api_args = array();
		if ( 'trash' === $post->post_status ) {
			$api_args['method'] = 'DELETE';
		}

		$this->send_by_api( $api_args );
	}

	/**
	 * Send a post object over the API to each language site in the network.
	 *
	 * @param array $args Array of args to override the normal request args.
	 */
	protected function send_by_api( $args = array() ) {
		$args = wp_parse_args( $args, $this->get_default_api_args() );

		foreach ( $this->sites() as $this->site ) {
			$this->post_to_site( $args );
		}

		$this->log_complete( $this->log_title, 'Wiki propagation has completed.' );
	}

	/**
	 * Maybe log a message.
	 *
	 * Will only trigger a lot if the logging class was found when the object was instantiated.
	 *
	 * @since 0.2.0
	 *
	 * @param string $title   The title of the message.
	 * @param string $message The message content.
	 */
	protected function maybe_log( $title, $message ) {
		/**
		 * Filter whether we should do propagation logging.
		 *
		 * @param bool $logging Whether to do logging. Defaults to true.
		 */
		if ( $this->logging && apply_filters( 'wds_propagation_logging', true ) ) {
			try {
				JPry_Log_Post::log_message( $title, $message );
			} catch ( \Exception $e ) {
				// Logging failed? Move along, move along.
			}
		}
	}

	/**
	 * Send a POST request through the API with wp_remote_request().
	 *
	 * @param array $args The array of arguments for the request.
	 *
	 * @return array|WP_Error The results of wp_remote_request().
	 */
	protected function post_to_site( $args ) {
		$path           = $this->get_api_path();
		$this->meta_key = "{$this->prefix}{$this->site['path']}";
		if ( isset( $this->post_meta[ $this->meta_key ] ) ) {
			$path .= '/' . $this->post_meta[ $this->meta_key ][0];
		}

		$args = $this->maybe_fix_iis( $args );

		$url = get_rest_url( $this->site['blog_id'], $path );
		$this->log( $this->log_title, 'Posting to url: ' . print_r( $url, true ) );
		return wp_remote_request( $url, $args );
	}

	/**
	 * Build an array of cookies that can be passed via a POST request.
	 * Send data to each sub-site.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	protected function build_cookies() {
		static $cookies = array();

		if ( empty( $cookies ) ) {
			foreach ( $_COOKIE as $name => $value ) {
				$cookies[] = "$name=" . urlencode( is_array( $value ) ? serialize( $value ) : $value );
			}
		}

		return $cookies;
	}

	/**
	 * Get default set of args for an API request.
	 *
	 * @return array
	 */
	protected function get_default_api_args() {
		return array(
			'blocking' => false,
			'body'     => $this->data,
			'headers'  => array(
				'cookie'     => implode( '; ', $this->build_cookies() ),
				'X-WP-Nonce' => $this->translation_propagation->get_nonce_for_request(),
			),
			'method'   => 'POST',
			'timeout'  => 1,
		);
	}

	/**
	 * Get the API path based on the post type.
	 *
	 * @return string
	 */
	protected function get_api_path() {
		$object   = get_post_type_object( $this->post_type );
		$endpoint = isset( $object->rest_base ) ? $object->rest_base : $this->post_type;
		$path     = "/wp/v2/{$endpoint}";

		return $path;
	}

	/**
	 * Ensure the request will work on IIS.
	 *
	 * IIS will often restrict the HTTP verbs that can be used. This means we need to use an
	 * alternative method to pass certain HTTP verbs.
	 *
	 * @param array $args The array of args to make the request.
	 *
	 * @return array The updated array of args to make the request.
	 */
	protected function maybe_fix_iis( $args ) {
		/** @var bool */
		global $is_IIS;

		// Handle silly IIS restrictions on HTTP method type.
		$broken_iis_methods = array(
			'DELETE' => 1,
			'PATCH'  => 1,
			'PUT'    => 1,
		);

		if ( $is_IIS && isset( $broken_iis_methods[ $args['method'] ] ) ) {
			$args['headers']['X-HTTP-METHOD-OVERRIDE'] = $args['method'];
			$args['method']                            = 'GET';
		}

		return $args;
	}

	/**
	 * Send data to each sub-site.
	 *
	 * @param int   $object_id The Object ID.
	 * @param array $data      The array of data to send.
	 * @param array $meta      The object meta to attach to each object.
	 */
	protected function send_to_each( $object_id, $data, $meta ) {
		// no-op
	}

	/**
	 * Update existing data for each sub-site.
	 *
	 * @since 0.1.0
	 *
	 * @param int   $object_id The Object ID.
	 * @param array $data      The array of data to send.
	 * @param array $meta      The object meta to attach to each object.
	 */
	protected function update_each( $object_id, $data, $meta ) {
		// no-op
	}

	/**
	 * Add a custom log type for propagation.
	 *
	 * @since 0.1.1
	 * @param array $terms The terms to modify.
	 * @return array
	 */
	public function add_log_post_labels( $terms ) {
		if ( ! isset( $terms['Propagation'] ) ) {
			$terms['Propagation'] = array(
				'slug'        => 'propagation',
				'description' => 'background-color:#A38BD2',
			);
		}

		return $terms;
	}

	/**
	 * Add WDS Log Post logging.
	 *
	 * @since 0.2.0
	 * @param string $title    The title of the log.
	 * @param string $message  The message to log.
	 * @param array  $labels   Optional override for log labels, defaults to empty array.
	 * @param int    $progress Progress percentage to pass.
	 */
	protected function log( $title, $message, $labels = array(), $progress = null ) {
		$args = array(
			'title'    => $title,
			'message'  => $message,
			'labels'   => count( $labels ) ? $labels : $this->log_labels,
			'log_id'   => $this->log_id,
			'progress' => $progress,
		);

		$log_id = JPry_Utility::error_log( $args );

		if ( null === $this->log_id ) {
			$this->log_id = $log_id;
		}
	}

	/**
	 * Shorthand for error logging with $this->log.
	 *
	 * @since 0.2.0
	 * @param string $title   The title of the log.
	 * @param string $message The message to log.
	 */
	protected function error_log( $title, $message ) {
		$this->log( $title, $message, array( 'error', 'propagation' ) );
	}

	/**
	 * Add ability to complete logging process.
	 *
	 * @since 0.2.1
	 * @param string $title   The log title.
	 * @param string $message The message to log.
	 */
	protected function log_complete( $title, $message = '' ) {
		$args = array(
			'title'     => $title,
			'message'   => $message,
			'labels'    => $this->log_labels,
			'log_id'    => $this->log_id,
			'progress'  => 100,
			'completed' => true,
		);

		JPry_Utility::error_log( $args );
	}

	/**
	 * Ensure we have the appropriate connector class available.
	 *
	 * @throws Exception When the JPry_Network_Connect_JSON_Connect class doesn't exist.
	 */
	protected function ensure_connector() {
		if ( ! class_exists( 'JPry_Network_Connect_JSON_Connect' ) ) {
			throw new Exception( 'Missing the JPry_Network_Connect_JSON_Connect class!' );
		}
	}

	/**
	 * Get a nonce to use with an API request.
	 *
	 * @return string
	 */
	protected function get_nonce_for_request() {
		return wp_create_nonce( 'wp_rest' );
	}
}
