<?php
/**
 * Plugin Name: JPry Post Metabox
 * Plugin URI:  http://jeremypry.com
 * Description: Creates custom metaboxes for posts.
 * Version:     0.3.0
 * Author:      JPry
 * Author URI:  http://jeremypry.com
 * Donate link: http://jeremypry.com
 * License:     GPLv2
 * Text Domain: jpry-post-metabox
 * Domain Path: /languages
 *
 * @link    http://jeremypry.com
 *
 * @package JPry Post Metabox
 * @version 0.2.0
 */

/**
 * Copyright (c) 2016 JPry
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using generator-plugin-wp
 */

/**
 * CHANGELOG
 *
 * == 0.1.0 ==
 * - Initial plugin creation
 *
 * == 0.2.0 ==
 * - Add class methods for retrieving featured content.
 * - Add helper functions for accessing class methods.
 * - Add class method to delete transients when a post is updated.
 *
 * == 0.3.0 ==
 * - Add method for retrieving 3 featured category posts
 */


/**
 * Main initiation class
 *
 * @since 0.1.0
 */
final class JPry_Post_Metabox {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since 0.1.0
	 */
	const VERSION = '0.3.0';

	/**
	 * URL of plugin directory
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $path = '';

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $basename = '';

	/**
	 * Singleton instance of plugin
	 *
	 * @var JPry_Post_Metabox
	 * @since 0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since 0.1.0
	 * @return JPry_Post_Metabox A single instance of this class.
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
	 * @since 0.1.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Add hooks and filters
	 *
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'acf/init', array( $this, 'register_metabox_fields' ) );
		add_filter( 'acf/update_value', array( $this, 'clear_transients' ), 10, 3 );
	}

	/**
	 * Init hooks
	 *
	 * @since 0.1.0
	 */
	public function init() {
		load_plugin_textdomain( 'jpry-post-metabox', false, dirname( $this->basename ) . '/languages/' );
	}

	/**
	 * Register our custom ACF fields.
	 *
	 * @see    https://www.advancedcustomfields.com/resources/register-fields-via-php/
	 *
	 * @author Jeremy Pry
	 * @since  0.1.0
	 */
	public function register_metabox_fields() {
		acf_add_local_field_group( array(
			'key'      => 'group_jpry_featured',
			'title'    => __( 'Post Feature Content', 'jpry-post-metabox' ),
			'fields'   => array(
				array(
					'key'      => 'field_featured_on_home',
					'message'  => __( 'Featured on Home', 'jpry-post-metabox' ),
					'name'     => 'featured_on_home',
					'type'     => 'true_false',
					'required' => false,
				),
				array(
					'key'      => 'field_featured_on_category',
					'message'  => __( 'Featured on Category', 'jpry-post-metabox' ),
					'name'     => 'featured_on_category',
					'type'     => 'true_false',
					'required' => false,
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			),
			'position' => 'side',
		) );
	}

	/**
	 * Get a query object for posts featured on the home page.
	 *
	 * @since  0.2.0
	 * @author Jeremy Pry
	 *
	 * @return WP_Query
	 */
	public function get_home_featured_posts() {
		$key  = 'jpry_featured_on_home';
		$args = array(
			'meta_key'   => 'featured_on_home',
			'meta_value' => 1,
			'nopaging'   => true,
			'post_type'  => 'post',
		);

		return $this->get_cached_query( $key, $args );
	}

	/**
	 * Get the query object for posts featured on a category archive page.
	 *
	 * @since  0.2.0
	 * @author Jeremy Pry
	 *
	 * @param int $category The category ID to retrieve.
	 *
	 * @return WP_Query
	 */
	public function get_archive_featured_posts( $category ) {
		$key  = "jpry_featured_on_category_{$category}";
		$args = array(
			'cat'        => $category,
			'meta_key'   => 'featured_on_category',
			'meta_value' => 1,
			'nopaging'   => true,
			'post_type'  => 'post',
		);

		return $this->get_cached_query( $key, $args );
	}

	/**
	 * Get the first featured post for each of the 3 categories.
	 *
	 * @since  0.3.0
	 *
	 * @author Jeremy Pry
	 * @return WP_Query
	 */
	public function get_home_category_featured_posts() {
		$key      = 'jpry_featured_category_on_home';
		$cat_ids  = $this->get_category_ids();
		$post_ids = array();

		foreach ( $cat_ids as $cat_id ) {
			$post_ids[] = $this->get_single_category_post_id( $cat_id, $post_ids );
		}

		$args = array(
			'ignore_sticky_posts' => true,
			'post__in'            => $post_ids,
		);

		return $this->get_cached_query( $key, $args );
	}

	/**
	 * Get a WP_Query object using transient caching.
	 *
	 * @since  0.2.0
	 * @author Jeremy Pry
	 *
	 * @param string $key  The transient key.
	 * @param array  $args Array of arguments to pass to WP_Query.
	 *
	 * @return WP_Query The WP_Query object.
	 */
	public function get_cached_query( $key, $args ) {
		$ids = get_transient( $key );
		if ( false === $ids ) {
			$args['fields']              = 'ids';
			$args['ignore_stikcy_posts'] = true;
			$query                       = new WP_Query();
			$ids                         = $query->query( $args );
			set_transient( $key, $ids, DAY_IN_SECONDS );
		}

		$orderby = isset( $args['orderby'] ) ? $args['orderby'] : 'date';

		// Ensure we don't accidentally query all posts.
		if ( empty( $ids ) ) {
			$ids = array( 0 );
		}

		return new WP_Query( array(
			'ignore_sticky_posts' => true,
			'post__in'            => $ids,
			'orderby'             => $orderby,
		) );
	}

	/**
	 * Get the array of category slugs and their IDs.
	 *
	 * @since  0.3.0
	 *
	 * @author Jeremy Pry
	 * @return array The array of category slugs and their IDs.
	 */
	private function get_category_ids() {
		$ids = get_transient( 'jpry_cateogory_ids' );
		if ( false !== $ids ) {
			return $ids;
		}

		$ids       = array();
		$cat_slugs = array(
			'fantasy-sports',
			'gaming',
			'poker',
		);

		foreach ( $cat_slugs as $slug ) {
			$cat          = get_category_by_slug( $slug );
			$ids[ $slug ] = $cat->cat_ID;
		}

		set_transient( 'jpry_category_ids', $ids, YEAR_IN_SECONDS );

		return $ids;
	}

	/**
	 * Get the Post ID for the first featured post in a category.
	 *
	 * @since  0.3.0
	 * @author Jeremy Pry
	 *
	 * @param int   $category     The category ID.
	 * @param array $post__not_in Array of post IDs to exclude.
	 *
	 * @return int The featured post ID for the category.
	 */
	private function get_single_category_post_id( $category, $post__not_in = array() ) {
		$key  = "jpry_featured_category_on_home_{$category}";
		$args = array(
			'cat'            => $category,
			'meta_key'       => 'featured_on_category',
			'meta_value'     => 1,
			'nopaging'       => true,
			'post_type'      => 'post',
			'posts_per_page' => 1,
			'post__not_in'   => $post__not_in,
		);

		$query = $this->get_cached_query( $key, $args );
		if ( $query->have_posts() ) {
			return $query->posts[0]->ID;
		}

		return 0;
	}

	/**
	 * Clear transient data when a post is saved.
	 *
	 * ACF only has a filter to hook into instead of an action, so this is a bit hacky.
	 *
	 * @since  0.2.0
	 * @author Jeremy Pry
	 *
	 * @param mixed $value   The value of the ACF field.
	 * @param int   $post_id The post ID.
	 * @param array $field   Array of field data.
	 *
	 * @return mixed The value of the ACF field.
	 */
	public function clear_transients( $value, $post_id, $field ) {
		$our_fields = array(
			'featured_on_category' => true,
			'featured_on_home'     => true,
		);

		if ( isset( $our_fields[ $field['name'] ] ) ) {
			switch ( $field['name'] ) {
				case 'featured_on_category':
					delete_transient( "jpry_{$field['name']}" );

					$cat_ids = wp_get_post_categories( $post_id );
					foreach ( $cat_ids as $cat_id ) {
						delete_transient( "jpry_featured_on_category_{$cat_id}" );
					}

					break;

				case 'featured_on_home':
					delete_transient( "jpry_{$field['name']}" );
					delete_transient( 'jpry_featured_category_on_home' );
					break;
			}
		}

		return $value;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since 0.1.0
	 *
	 * @param string $field Field to get.
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}
}

// Kick it off.
add_action( 'plugins_loaded', array( JPry_Post_Metabox::get_instance(), 'hooks' ) );
