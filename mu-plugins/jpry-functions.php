<?php
/**
 * Plugin Name: JPry Functions
 * Plugin URI: http://jeremypry.com/
 * Description: Sitewide functions for jeremypry.com
 * Version: 1.6
 * Author: Jeremy Pry
 * Author URI: http://jeremypry.com/
 * License: GPL3
 *
 * @package JPry
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	die( "There's not much you can do by calling this file directly." );
}

/**
 * Custom functionality for all sites in the network.
 */
class JPry_Common_Functions extends JPry_Singleton {

	/**
	 * The Singleton instance of this class
	 *
	 * @since 1.0.0
	 *
	 * @var JPry_Common_Functions
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		// Nothing here.
	}

	/**
	 * Ensure that the main site is able to choose from all themes.
	 *
	 * @since 1.3.0
	 *
	 * @param bool $default The pre value for the option.
	 *
	 * @return array An array of WP_Theme objects.
	 */
	public function filter_allowedthemes( $default ) {
		if ( is_main_site() ) {
			if ( ! $themes = get_site_transient( 'main_site_themes' ) ) {
				$themes = wp_get_themes();
				set_site_transient( 'main_site_themes', $themes );
			}
			$default = $themes;
		}
		return $default;
	}

	/**
	 * Filters the excerpt more string (default [...]).
	 *
	 * @since 1.0.0
	 *
	 * @global WP_Post $post The post object for the current post.
	 *
	 * @param string $more The current more string.
	 *
	 * @return string The modified more sting.
	 */
	public function filter_excerpt_more( $more ) {
		global $post;
		return ' <a href="' . get_permalink( $post->ID ) . '">[more...]</a>';
	}

	/**
	 * Ensure that links to plugins always open in a new window/tab.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $plugin_meta An array of the plugin's metadata, including the version, author, author URI, and plugin URI.
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $status      Status of the plugin.
	 *
	 * @return array Modified array of plugin data.
	 */
	public function force_plugin_link_new_tab( $plugin_meta, $plugin_file, $plugin_data, $status ) {

		// Only rebuild meta if the PluginURI is set.
		if ( ! empty( $plugin_data['PluginURI'] ) ) {
			$plugin_meta = array();
			if ( ! empty( $plugin_data['Version'] ) ) {
				$plugin_meta[] = sprintf( __( 'Version %s' ), $plugin_data['Version'] );
			}
			if ( ! empty( $plugin_data['Author'] ) ) {
				$author = $plugin_data['Author'];
				if ( ! empty( $plugin_data['AuthorURI'] ) ) {
					$author = '<a href="' . $plugin_data['AuthorURI'] . '" title="' . esc_attr__( 'Visit author homepage' ) . '">' . $plugin_data['Author'] . '</a>';
				}
				$plugin_meta[] = sprintf( __( 'By %s' ), $author );
			}

			// No need to re-if test this one.
			$plugin_meta[] = '<a href="' . $plugin_data['PluginURI'] . '" title="' . esc_attr__( 'Visit plugin site' ) . '" target="_blank">' . __( 'Visit plugin site' ) . '</a>';
		}

		return $plugin_meta;
	}

	/**
	 * Remove post type support for Markdown on Pages.
	 *
	 * @since 1.6.0
	 */
	public function jetpack_markdown_support() {
		if ( class_exists( 'WPCom_Markdown', false ) ) {
			remove_post_type_support( 'page', WPCom_Markdown::POST_TYPE_SUPPORT );
		}
	}

	/**
	 * Check to ensure a user is logged in before allowing the JSON page to work.
	 *
	 * @since 1.4.0
	 */
	public function json_page_login_check() {
		if ( ! is_admin() && is_page( 'json-utils' ) ) {
			auth_redirect();
		}
	}

	/**
	 * Register the class methods with the appropriate WordPress hooks.
	 *
	 * @since 1.5.0
	 */
	public function register_hooks() {

		// General Filters.
		add_filter( 'excerpt_more', array( $this, 'filter_excerpt_more' ) );
		add_filter( 'widget_text', 'do_shortcode', 999 );
		add_filter( 'plugin_row_meta', array( $this, 'force_plugin_link_new_tab' ), 10, 4 );

		// Make all themes available for main site.
		add_filter( 'pre_option_allowedthemes', array( $this, 'filter_allowedthemes' ) );

		// Force all users to have a strong password.
		add_filter( 'slt_fsp_caps_check', '__return_empty_array' );

		// Actions.
		add_action( 'init', array( $this, 'json_page_login_check' ) );

		// Disable JetPack markdown on pages.
		add_action( 'init', array( $this, 'jetpack_markdown_support' ), 20 );

		// Register stuffs.
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	/**
	 * Register any and all custom post types.
	 */
	public function register_post_types() {
		if ( is_main_site() ) {
			$args = array(
				'labels'                => array(
					'name'          => 'Presentation',
					'singular_name' => 'Presentation',
				),
				'description'           => 'Presentation data.',
				'public'                => false,
				'hierarchiacal'         => false,
				'exclude_from_search'   => true,
				'publicly_queryable'    => false,
				'show_ui'               => true,
				'show_in_nav_menus'     => false,
				'supports'              => array(
					'title',
					'editor',
					'custom-fields',
				),
				'has_archive'           => false,
				'show_in_rest'          => true,
				'rest_base'             => 'presentations',
				'rest_controller_class' => 'JPry_REST_Private_Posts_Controller',
			);
			register_post_type( 'jpry_presentation', $args );
		}
	}
}

// Instantiate the class.
JPry_Common_Functions::get_instance()->register_hooks();
