<?php


/**
 * Main initiation class
 *
 * @since  0.2.0
 * @var  string $version  Plugin version
 * @var  string $basename Plugin basename
 * @var  string $url      Plugin URL
 * @var  string $path     Plugin Path
 */
class JPry_Translation_Propagation {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since  0.1.0
	 */
	const VERSION = '0.2.0';

	/**
	 * JPry_Async_Link_Main instance for adding a link.
	 *
	 * @var JPry_Async_Link_Main
	 */
	protected $add_link;

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $basename = '';

	/**
	 * The JPry_Async_Delete_Post instance.
	 *
	 * @var JPry_Async_Delete_Post
	 */
	protected $delete_async;

	/**
	 * The JPry_Async_Delete_Link instance.
	 *
	 * @var JPry_Async_Delete_Link
	 */
	protected $delete_link;

	/**
	 * JPry_Async_Link_Main for editing a link.
	 *
	 * @var JPry_Async_Link_Main
	 */
	protected $edit_link;

	/**
	 * JPry_Link_Propagation instance.
	 *
	 * @var JPry_Link_Propagation
	 */
	protected $link;

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $path = '';

	/**
	 * Post types that use propagation.
	 *
	 * @var array
	 */
	protected $post_types = array(
		'incsub_wiki' => 1,
		'page'        => 1,
	);

	/**
	 * JPry_Taxonomy_Propagation instance.
	 *
	 * @var JPry_Taxonomy_Propagation
	 */
	protected $taxonomy;

	/**
	 * JPry_Async_Terms instance.
	 *
	 * @var JPry_Async_Terms
	 */
	protected $taxonomy_async;

	/**
	 * URL of plugin directory
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $url = '';

	/**
	 * JPry_Wiki_Propagation instance.
	 *
	 * @var JPry_Wiki_Propagation
	 */
	protected $wiki;

	/**
	 * JPry_Async_Post instance for Wikis.
	 *
	 * @var JPry_Async_Post
	 */
	protected $wiki_async;

	/**
	 * Singleton instance of plugin
	 *
	 * @var JPry_Translation_Propagation
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  0.1.0
	 * @return JPry_Translation_Propagation A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 *
	 * @since  0.1.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( JPRY_TRANSLATION_PROPAGATION );
		$this->url      = plugin_dir_url( JPRY_TRANSLATION_PROPAGATION );
		$this->path     = plugin_dir_path( JPRY_TRANSLATION_PROPAGATION );
	}

	/**
	 * Hook up the various asynchronous classes.
	 *
	 * This should be done no earlier than the plugins_loaded hook.
	 */
	protected function async_classes() {
		// Check for the abstract class, upon which all the other classes hinge.
		if ( class_exists( 'JPry_Async_Abstract' ) ) {
			$this->wiki_async     = new JPry_Async_Post( $this->post_types );
			$this->taxonomy_async = new JPry_Async_Terms();
			$this->delete_async   = new JPry_Async_Delete_Post( $this->post_types );
			$this->add_link       = new JPry_Async_Link_Main( 'add_link' );
			$this->edit_link      = new JPry_Async_Link_Main( 'edit_link' );
			$this->delete_link    = new JPry_Async_Delete_Link();
		} else {
			add_action( 'all_admin_notices', array( $this, 'missing_async_classes' ) );
		}
	}

	/**
	 * Notify administrators that WP Asynchronous Task is required for this plugin.
	 * @since 0.1.1
	 */
	public function missing_async_classes() {
		echo '<div id="message" class="error">';
		echo '<p>' . __( 'JPry Translation Propagation plugin requires "WP Asynchronous Tasks" plugin, please make sure it is activated.', 'jpry-translation-propagation' );
		echo '</div>';
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since 0.1.0
	 */
	public function plugin_classes() {
		$this->async_classes();

		/*
		 * Only add the hook when on the main site for the network. We don't want all of the child
		 * sites to duplicate the post they just received.
		 */
		if ( is_main_site() ) {
			$this->wiki     = new JPry_Wiki_Propagation( $this );
			$this->taxonomy = new JPry_Taxonomy_Propagation();
			$this->link     = new JPry_Link_Propagation();
		}
	}

	/**
	 * Add hooks and filters
	 *
	 * @since 0.1.0
	 */
	public function do_hooks() {
		$this->plugin_classes();
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'rest_check_nonce' ) );
		add_filter( 'wds_json_post_provider_can_post', array( $this, 'post_translation_check' ), 10, 3 );
		add_action( 'rest_api_init', array( $this, 'register_meta_route' ) );
		add_action( 'wds_json_post_provider_handle_rest_post', array( $this, 'handle_rest_post' ), 10, 3 );
		add_action( 'wds_json_post_provider_handle_rest_post', array( $this, 'update_english_meta' ), 10, 3 );
		add_action( 'rest_delete_post', array( $this, 'handle_delete_post' ), 10, 3 );

		// Admin Notice based on post meta.
		add_action( 'admin_notices', array( $this, 'post_propagation_notice' ) );

		// Ensure deletion of a post can actually happen.
		if ( is_main_site() ) {
			add_action( 'pre_delete_post', array( $this, 'pre_delete_post' ), 10, 2 );
		}

		// Custom action links for wikis.
		add_filter( 'page_row_actions', array( $this, 'clear_propagation_status_action' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'clear_propagation_status_action' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'link_english_post_action' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'link_english_post_action' ), 10, 2 );
		add_action( 'admin_action_jpry_clear_prop_status', array( $this, 'clear_prop_status' ) );
		add_action( 'admin_action_jpry_link_english_post', array( $this, 'link_english_post' ) );

		// Hook into meta creation to watch for language site meta.
		add_action( 'added_post_meta', array( $this, 'language_meta_watcher' ), 10, 3 );

		// Ensure content isn't updated for each post type when the post is translated.
		foreach ( $this->post_types as $post_type => $val ) {
			add_filter( "rest_pre_insert_{$post_type}", array( $this, 'maybe_filter_content' ), 10, 2 );
		}

		// Ensure certain post meta is not updated when the post has been translated.
		add_filter( 'wds_json_post_provider_handle_post_meta', array( $this, 'filter_meta' ), 10, 4 );

		if ( ! is_main_site() ) {
			//add_action( 'rest_prepare_link_name', array( $this, 'maybe_strip_link_name' ), 10, 2 );
			add_filter( 'rest_prepare_link_url', array( $this, 'maybe_localize_link_url' ), 10, 2 );
		}
	}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 */
	public function init() {
		if ( $this->check_requirements() ) {
			load_plugin_textdomain( 'jpry-translation-propagation', false, dirname( $this->basename ) . '/languages/' );
		}

		if ( '0.2.0' === self::VERSION ) {
			$this->upgrade_020();
		}
	}

	/**
	 * Upgrade routine for the 0.2.0 version.
	 *
	 * This will delete all Wiki Category
	 */
	protected function upgrade_020() {
		// For language sites, maybe delete all Wiki Categories
		if ( is_main_site() ) {
			return;
		}

		$option = get_option( 'jpry_reset_terms', '0.0.0' );
		if ( version_compare( $option, self::VERSION, '<' ) ) {
			// Reset wiki categories
			$wiki_terms = get_terms( 'incsub_wiki_category', array(
				'hide_empty' => false,
			) );

			foreach ( $wiki_terms as $term ) {
				wp_delete_term( $term->term_taxonomy_id, 'incsub_wiki_category' );
			}

			// update the option so we don't do this again.
			update_option( 'jpry_reset_terms', self::VERSION );
		}
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @since  0.1.0
	 * @return bool
	 */
	public static function meets_requirements() {
		return function_exists( 'get_main_site_for_network' );
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.1.0
	 * @return bool result of meets_requirements
	 */
	public function check_requirements() {
		if ( ! $this->meets_requirements() ) {

			// Add a dashboard notice.
			add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

			// Deactivate our plugin.
			deactivate_plugins( $this->basename );

			return false;
		}

		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met
	 *
	 * @since  0.1.0
	 * @return null
	 */
	public function requirements_not_met_notice() {
		// Output our error
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'JPry Translation Propagation is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'jpry-translation-propagation' ), admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}

	/**
	 * Get a universal nonce for dealing with REST requests.
	 *
	 * @return string The nonce.
	 */
	public function get_nonce_for_request() {
		$nonce = get_site_transient( 'jpry_rest_propagation_nonce' );
		if ( false === $nonce ) {
			$nonce = wp_create_nonce( 'wp_rest' );
			set_site_transient( 'jpry_rest_propagation_nonce', $nonce, DAY_IN_SECONDS );
		}

		return $nonce;
	}

	/**
	 * Attempt to authenticate with a rest nonce.
	 *
	 * @param WP_Error|null|bool $result The current authentication status.
	 *
	 * @return WP_Error|null|bool WP_Error if authentication error, null if authentication method wasn't used,
	 *                                   true if authentication succeeded.
	 */
	public function rest_check_nonce( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		// Try to get the nonce from the request.
		$nonce = null;
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = $_REQUEST['_wpnonce'];
		} elseif ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			$nonce = $_SERVER['HTTP_X_WP_NONCE'];
		}

		// If there's no nonce, don't authenticate this request.
		if ( null === $nonce ) {
			return null;
		}

		// Attempt normal nonce verification first.
		$result = (bool) wp_verify_nonce( $nonce, 'wp_rest' );

		// Attempt to fallback to the network stored nonce.
		if ( ! $result ) {
			if ( $this->get_nonce_for_request() === $nonce ) {
				$result = true;
			} else {
				$message = __( 'The propagation nonce is invalid.', 'jpry-translation-propagation' );
				error_log( 'REST Authorization failed: ' . $message );
				$result = new WP_Error(
					'rest_propagation_invalid_nonce',
					$message,
					array( 'status' => 403 )
				);
			}
		}

		// Maybe set the current user.
		if ( 0 == get_current_user_id() && true === $result ) {
			wp_set_current_user( JPry_Utility::get_fallback_user_id() );
		}

		return $result;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.1.0
	 *
	 * @param string $field
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'path':
			case 'url':
			case 'wiki':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	/**
	 * Determine whether a post has been translated.
	 *
	 * @since 0.3.0
	 *
	 * @param bool     $can_post
	 * @param stdClass $post
	 * @param WP_REST_Request $request
	 *
	 * @return bool Whether the post is translated.
	 */
	public function post_translation_check( $can_post, $post, $request ) {
		if ( ! isset( $post->ID ) ) {
			return false;
		}

		$existing_posts = get_post_meta( $post->ID, 'wds_link_check_ts' );
		$is_propagating = isset( $request['wds_propagating'] ) && $request['wds_propagating'];
		return $can_post && empty( $existing_posts ) && ! $is_propagating;
	}

	/**
	 * Register a REST route for decrementing meta.
	 */
	public function register_meta_route() {
		$args = array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'decrement_post_meta' ),
			'permission_callback' => array( $this, 'post_meta_permission_callback' ),
			'args'                => array(
				'post_id' => array(
					'required' => true,
				),
				'log_id' => array(
					'required' => false,
				),
			),
		);
		register_rest_route( 'wds/v1', 'propagation_meta_decrement/(?P<post_id>\d+)', $args );
	}

	/**
	 * Handler for decrementing the propagation post meta.
	 *
	 * @param WP_REST_Request|array $request The Request object.
	 *
	 * @return array
	 */
	public function decrement_post_meta( $request ) {
		$meta = (int) get_post_meta( $request['post_id'], '_jpry_propagation_count', true );

		if ( $meta > 0 ) {
			$meta--;

			if ( $meta <= 0 ) {
				$result = delete_post_meta( $request['post_id'], '_jpry_propagation_count' );
			} else {
				$result = update_post_meta( $request['post_id'], '_jpry_propagation_count', $meta );
			}
		} else {
			$result = true;
		}

		return array(
			'count'         => $meta,
			'update_result' => $result,
		);
	}

	/**
	 * Check whether the current user has permission to edit post meta.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool|WP_Error True if the request can continue, or WP_Error object on error.
	 */
	public function post_meta_permission_callback( WP_REST_Request $request ) {
		$post = get_post( $request['post_id'] );
		if ( ! ( $post instanceof WP_Post ) ) {
			return new WP_Error(
				'rest_invalid_post',
				__( 'Sorry, a post with that ID does not exist.', 'jpry-translation-propagation' ),
				array( 'status' => 404 )
			);
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit these posts in this post type', 'jpry-translation-propagation' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Ping the meta decrement endpoint when a post is updated.
	 *
	 * @param WP_Post         $post     The post object.
	 * @param WP_REST_Request $request  The request sent to the API.
	 * @param bool            $creating Whether this is creating a new post.
	 */
	public function handle_rest_post( $post, $request, $creating ) {
		if ( ! ( isset( $request['wds_propagating'] ) && $request['wds_propagating'] ) ) {
			return;
		}

		// Posts that are just being created will have the meta decremented elsewhere.
		if ( $creating ) {
			return;
		}

		$this->ping_meta_decrement( $request );
	}

	/**
	 * Ping the meta decrement endpoint when a post is trashed.
	 *
	 * @param object          $post    The deleted or trashed post.
	 * @param array           $data    The response data.
	 * @param WP_REST_Request $request The request sent to the API.
	 */
	public function handle_delete_post( $post, $data, $request ) {
		// Only update post meta if the post was trashed. A deleted post has no meta to update!
		if ( isset( $data['trashed'] ) ) {
			if ( null === $request->get_param( 'site_url' ) ) {
				$request->set_param( 'site_url', get_site_url( get_main_site_for_network() ) );
			}
			if ( null === $request->get_param( 'orig_post_id' ) ) {
				$request->set_param( 'orig_post_id', get_post_meta( $post->ID, 'orig_post_id', true ) );
			}
			$this->ping_meta_decrement( $request );
		}
	}

	/**
	 * Send a request to decrement the post meta.
	 *
	 * @param WP_REST_Request|array $request The request sent to the API.
	 */
	protected function ping_meta_decrement( $request ) {
		$url = trailingslashit( $request['site_url'] ) . 'wp-json/wds/v1';
		$url .= "/propagation_meta_decrement/{$request['orig_post_id']}";

		$args = array(
			'blocking' => false,
			'body'     => json_encode( array(
				'log_id' => $request['wds_log_id'],
			) ),
			'headers'  => array(
				'Content-Type' => 'application/json',
				'X-WP-Nonce'   => $this->get_nonce_for_request(),
			),
			'timeout'  => 1, // Send it and forget it.
		);

		wp_remote_get( $url, $args );
	}

	/**
	 * Display an admin notice if the post is propagating.
	 */
	public function post_propagation_notice() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! isset( $screen->base, $screen->id ) ) {
			return;
		}

		if ( 'post' !== $screen->base || 'incsub_wiki' !== $screen->id ) {
			return;
		}

		if ( ! isset( $GLOBALS['post_id'] ) ) {
			return;
		}

		$meta            = get_post_meta( $GLOBALS['post_id'], '_jpry_propagation_count', true );
		$just_updated    = isset( $_GET['message'] ) && 'publish' == $GLOBALS['post']->post_status;
		$display_message = is_main_site() && jpry_is_authoring() && ( ! empty( $meta ) || $just_updated );
		if ( $display_message ) {
			echo '<div class="notice error"><p>';
			_e( 'This post is currently propagating. Any changes made during this time may not properly propagate.', 'jpry-translation-propagation' );
			echo '</p></div>';
		}
	}

	/**
	 * Create a link to clear the propagation status meta.
	 *
	 * @param array   $actions An array of action links.
	 * @param WP_Post $post    The post object.
	 *
	 * @return array The array of action links.
	 */
	public function clear_propagation_status_action( $actions, $post ) {
		if ( ! is_main_site() ) {
			return $actions;
		}

		if ( ! isset( $this->post_types[ $post->post_type ] ) ) {
			return $actions;
		}

		$args = array(
			'post'   => $post->ID,
			'action' => 'wds_clear_prop_status',
		);
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		$actions['clear_prop_status'] = sprintf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			$url,
			__( 'Remove the propagation status for this item.', 'jpry-translation-propagation' ),
			__( 'Clear Propagation Status', 'jpry-translation-propagation' )
		);

		return $actions;
	}

	/**
	 * Create a link to link this post to its English original.
	 *
	 * @param array   $actions An array of action links.
	 * @param WP_Post $post    The post object.
	 *
	 * @return array The array of action links.
	 */
	public function link_english_post_action( $actions, $post ) {
		if ( is_main_site() ) {
			return $actions;
		}

		if ( ! isset( $this->post_types[ $post->post_type ] ) ) {
			return $actions;
		}

		$args = array(
			'post'   => $post->ID,
			'action' => 'wds_link_english_post',
		);
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		$actions['wds_link_english_post'] = sprintf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			$url,
			__( 'Link this item to the original English post.', 'jpry-translation-propagation' ),
			__( 'Link to English Post', 'jpry-translation-propagation' )
		);

		return $actions;
	}

	/**
	 * Clear the propagation meta from a given post.
	 */
	public function clear_prop_status() {
		$post_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
		if ( ! $post_id ) {
			wp_die( __( 'No post ID supplied!', 'jpry-translation-propagation' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( __( 'That post does not exist.', 'jpry-translation-propagation' ) );
		}

		delete_post_meta( $post_id, '_jpry_propagation_count' );

		$args = array(
			'post_type'      => $post->post_type,
			'wds_prop_clear' => true,
		);
		$url = add_query_arg( $args, admin_url( 'edit.php' ) );
		wp_redirect( $url );
		exit();
	}

	/**
	 * Link a given post on a language site to the original English post.
	 */
	public function link_english_post() {
		$post_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
		if ( ! $post_id ) {
			wp_die( __( 'No post ID supplied!', 'jpry-translation-propagation' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( __( 'That post does not exist.', 'jpry-translation-propagation' ) );
		}

		$orig_id = get_post_meta( $post_id, 'wds_english_id', true );
		if ( empty( $orig_id ) ) {
			wp_die( __( 'This post does not seem to have an original English post.', 'jpry-translation-propagation' ) );
		}

		$ping_args = array(
			'wds_propagating' => true,
			'orig_post_id'    => $orig_id,
			'site_url'        => get_site_url( get_main_site_for_network() ),
		);
		$this->update_english_meta( $post, $ping_args, true );

		$args = array(
			'post_type'        => $post->post_type,
			'wds_english_link' => true,
		);
		$url = add_query_arg( $args, admin_url( 'edit.php' ) );
		wp_redirect( $url );
		exit();
	}

	/**
	 * Update the English post meta.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_Post               $post     The post object.
	 * @param WP_REST_Request|array $request  The request object.
	 * @param bool                  $creating Whether this is creating a new post.
	 */
	public function update_english_meta( $post, $request, $creating ) {
		if ( ! ( isset( $request['wds_propagating'] ) && $request['wds_propagating'] ) ) {
			return;
		}

		if ( ! $creating ) {
			return;
		}

		/** @var stdClass */
		global $current_blog;

		// Build the REST meta URL.
		$url = trailingslashit( $request['site_url'] ) . 'wp-json/wp/v2/wiki';
		$url .= "/{$request['orig_post_id']}/meta";

		$args = array(
			'blocking' => false,
			'body'     => array(
				'key'   => 'language_site_' . trim( $current_blog->path, '/' ),
				'value' => $post->ID,
			),
			'headers'  => array(
				'X-WP-Nonce' => $this->get_nonce_for_request(),
			),
			'timeout'  => 1, // Send it and forget it.
		);

		wp_remote_post( $url, $args );
	}

	/**
	 * Determine whether the post should be deleted or not.
	 *
	 * @param bool    $delete Whether to go forward with deletion.
	 * @param WP_Post $post   Post object.
	 *
	 * @return bool|null Null to allow the deletion to continue, or any other value to return to the original caller.
	 */
	public function pre_delete_post( $delete, $post ) {
		if ( get_post_meta( $post->ID, '_jpry_propagation_count', true ) ) {
			$delete = false;
			add_filter( 'wp_die_handler', array( $this, 'setup_die_handler' ) );
		}

		return $delete;
	}

	/**
	 * Set up our own handler for wp_die().
	 *
	 * @return callable The callable method to replace wp_die().
	 */
	public function setup_die_handler() {
		return array( $this, 'deletion_die_handler' );
	}

	/**
	 * Wrap the default die handler to include our own message.
	 *
	 * @param string       $message Error message.
	 * @param string       $title   Optional. Error title. Default empty.
	 * @param string|array $args    Optional. Arguments to control behavior. Default empty array.
	 */
	public function deletion_die_handler( $message, $title = '', $args = array() ) {
		// Just append our own message.
		$message .= ' The post is currently propagating and cannot be deleted.';
		_default_wp_die_handler( $message, $title, $args );
	}

	/**
	 * Trigger the decrement_post_meta method when a language site meta is added.
	 *
	 * @param int    $mid       The meta ID after successful update.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 */
	public function language_meta_watcher( $mid, $object_id, $meta_key ) {
		if ( ! is_main_site() ) {
			return;
		}

		if ( false === strpos( $meta_key, 'language_site_' ) ) {
			return;
		}

		$this->decrement_post_meta( array( 'post_id' => $object_id ) );
	}

	/**
	 * Possibly filter a post's content, if it's translated.
	 *
	 * @param stdClass        $prepared_post The prepared post data. Object fields should match WP_Post.
	 * @param WP_REST_Request $request       The request object.
	 *
	 * @return stdClass The filtered prepared post.
	 */
	public function maybe_filter_content( $prepared_post, $request ) {
		// If there's no ID, then this is a new post.
		if ( ! isset( $prepared_post->ID ) ) {
			return $prepared_post;
		}

		// Check to see if the post is translated, and remove the content if it is.
		try {
			if ( JPry_Utility::is_post_translated( $prepared_post->ID ) ) {
				unset( $prepared_post->post_content, $prepared_post->post_title, $prepared_post->post_excerpt );
			}
		} catch ( Exception $e ) {
			// This shouldn't happen, but if it does return a WP_Error.
			$prepared_post = new WP_Error( 'wds_translation_check', $e->getMessage(), array( 'status' => 400 ) );
		}

		return $prepared_post;
	}

	/**
	 * Maybe filter the post meta before it is inserted.
	 *
	 * This will check to see if the post has been translated, and if so some of the meta data
	 * will be removed.
	 *
	 * @param array           $post_meta The array of post meta data to save.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param bool            $creating  True for a new post, false for an updated post.
	 * @param stdClass        $post      Inserted post object (NOT a WP_Post object).
	 *
	 * @return array The filtered post meta.
	 */
	public function filter_meta( $post_meta, $request, $creating, $post ) {
		if ( is_main_site() ) {
			return $post_meta;
		}

		$blacklist = array();
		$update    = array();

		// Non-translated and new posts only have show in help pane removed.
		if ( $creating || ! JPry_Language_Utility::is_post_translated( $post->ID ) ) {
			$update['incsub_wiki_show_in_help_pane'] = false;
		} else {

			// Post is translated, so remove some of the meta.
			try {
				$handler = new JPry_Translation_Post( array( 'post_id' => $post->ID ) );

				// The translation whitelist is our blacklist of what should NOT be propagated.
				$blacklist = (array) $handler->meta_whitelist;
			} catch ( \Exception $e ) {
				return $post_meta;
			}
		}

		if ( array() === $blacklist && array() === $update ) {
			error_log( 'Propagation: No meta updates were made.' );
			return $post_meta;
		}

		foreach ( $post_meta as $index => &$meta ) {
			if ( isset( $blacklist[ $meta['key'] ] ) ) {
				unset( $post_meta[ $index ] );
			}

			if ( isset( $update[ $meta['key'] ] ) ) {
				$meta['value'] = $update[ $meta['key'] ];
			}
		}

		return $post_meta;
	}

	/**
	 * Possibly strip the link name field value.
	 *
	 * @param mixed           $field_data The field data to save.
	 * @param WP_REST_Request $request    Full request details.
	 *
	 * @return mixed The filtered field data.
	 */
	public function maybe_strip_link_name( $field_data, $request ) {
		// If there's an ID, then this is an update.
		if ( ! empty( $request['id'] ) ) {

			$link = get_bookmark( $request['id'] );
			if ( ! empty( $link->link_name ) && $field_data !== $link->link_name ) {
				$field_data = null;
			}
		}

		return $field_data;
	}

	/**
	 * Possibly localize a URL to a language site.
	 *
	 * @param mixed           $field_data The field data to save.
	 * @param WP_REST_Request $request    Full request details.
	 *
	 * @return mixed The filtered field data to save.
	 */
	public function maybe_localize_link_url( $field_data, $request ) {
		if ( ! empty( $request['url'] ) ) {
			$local_url_base = trailingslashit( get_home_url() );
			$en_url_base    = trailingslashit( get_home_url( get_main_site_for_network() ) );
			$field_data     = str_replace( $en_url_base, $local_url_base, $request['url'] );
			$field_data     = esc_url_raw( $field_data );
		}

		return $field_data;
	}
}
