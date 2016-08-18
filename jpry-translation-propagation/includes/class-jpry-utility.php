<?php

/**
 * A set of general utilities.
 */
class JPry_Utility {

	/**
	 * Gets a fallback user id in this order:
	 * * Checks for Tonya's user
	 * * Falls back to admin user
	 * * Runs the jpry_get_fallback_user filter for user override
	 *
	 * @return mixed Boolean false if no user found (should never happen) or a WP_User
	 */
	public static function get_fallback_user_id() {
		return self::get_fallback_user()->ID;
	}

	/**
	 * Gets a fallback user in this order:
	 * * Checks for Tonya's user
	 * * Falls back to admin user
	 * * Runs the jpry_get_fallback_user filter for user override
	 *
	 * @return mixed Boolean false if no user found (should never happen) or a WP_User
	 */
	public static function get_fallback_user() {
		$fallback_user = get_user_by( 'id', self::get_lowest_user_id() );

		/**
		 * Filter the fallback user.
		 *
		 * @param WP_User $fallback_user The user object to use as a fallback.
		 */
		$_fallback_user = apply_filters( 'jpry_get_fallback_user', $fallback_user );

		return is_a( $_fallback_user, 'WP_User' ) ? $_fallback_user : $fallback_user;
	}

	public static function get_lowest_user_id() {
		global $wpdb;

		return absint( $wpdb->get_var( "SELECT `ID` FROM {$wpdb->users} ORDER BY `ID` ASC LIMIT 0,1;" ) );
	}

	/**
	 * Add a user to a site.
	 *
	 * This method will look for the current site among the list of all sites for the given author. If not present,
	 * the user will be added to the current site with all roles from the authoring site.
	 *
	 * @param WP_User $author  The author object.
	 * @param array   $roles   The array of roles to add.
	 * @param int     $site_id The ID of the site to add the user to. Defaults to the current site.
	 */
	public static function add_user_to_site( $author, $roles, $site_id = null ) {
		if ( is_null( $site_id ) ) {
			$site_id = get_current_blog_id();
		}

		$sites = get_blogs_of_user( $author->ID );
		if ( ! isset( $sites[ $site_id ] ) ) {

			// Maybe set the site to operate on
			$switched = false;
			if ( (int) $site_id != get_current_blog_id() ) {
				$author->for_blog( $site_id );
				$switched = true;
			}

			// Add each role from authoring to this site
			foreach ( $roles as $role ) {
				$author->add_role( $role );
			}

			// Reset the site if needed
			if ( $switched ) {
				$author->for_blog( get_current_blog_id() );
			}
		}
	}

	/**
	 * Get all the sites for the current network.
	 *
	 * @param array $args               {
	 *                                  Args for retrieving the sites for the network.
	 *
	 * @type int    $main_site          The main site for the network.
	 * @type bool   $include_main_site  Whether to include the main site in the array of results.
	 * @type bool   $strip_path_slashes Whether to remove the slashes from sub-site paths.
	 * }
	 *
	 * @return array The array of sites for the network.
	 */
	public static function get_network_sites( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'main_site'          => get_main_site_for_network(),
			'include_main_site'  => false,
			'strip_path_slashes' => false,
		) );

		// Cache based on args.
		$args_key = json_encode( $args );
		$sites    = get_transient( $args_key );

		if ( false === $sites ) {
			$sites = wp_get_sites( array(
				'network_id' => $args['main_site'],
			) );

			// Maybe remove the main site from the list.
			if ( ! $args['include_main_site'] ) {
				$sites = self::remove_main_site( $sites );
			}

			// Maybe strip the slashes from the paths.
			if ( $args['strip_path_slashes'] ) {
				$sites = self::strip_path_slashes( $sites );
			}

			set_transient( $args_key, $sites, DAY_IN_SECONDS );
		}

		return $sites;
	}

	/**
	 * Remove the main site from an array of sites.
	 *
	 * @param array $sites The array of sites.
	 *
	 * @return array The updated array of sites.
	 */
	private static function remove_main_site( $sites ) {
		foreach ( $sites as $key => $site ) {
			if ( $site['blog_id'] == $site['site_id'] ) {
				unset( $sites[ $key ] );
				break;
			}
		}

		return $sites;
	}

	/**
	 * Strip slashes from beginning and end of site paths.
	 *
	 * @param array $sites The array of sites.
	 *
	 * @return array The updated array of sites.
	 */
	private static function strip_path_slashes( $sites ) {
		foreach ( $sites as $key => $site ) {
			$sites[ $key ]['path'] = trim( $site['path'], '/' );
		}

		return $sites;
	}

	/**
	 * Log an error, but only if WP_DEBUG_LOG is defined and truthy.
	 *
	 * @param string|array $args The message to log, or the arguments to pass to WDS_Log_Post.
	 *
	 * @return int|null The Log ID.
	 */
	public static function error_log( $args ) {
		if ( ! is_array( $args ) && is_string( $args ) ) {
			$message = $args;
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( $message );
			}
		} else {
			$message = isset( $args['message'] ) ? $args['message'] : '';
		}

		if ( class_exists( 'WDS_Log_Post' ) ) {
			$args = wp_parse_args( $args, array(
				'title'     => 'JPry Utility Log',
				'message'   => $message,
				'log_id'    => null,
				'progress'  => null,
				'completed' => false,
				'labels'    => array(),
			) );

			if ( is_null( $args['log_id'] ) ) {
				$log_id = WDS_Log_Post::log_message( $args['title'], $message, $args['labels'] );

				return $log_id;
			} else {
				WDS_Log_Post::log_message( $args['title'], $message, $args['labels'], $args['log_id'], $args['completed'] );

				if ( null !== $args['progress'] ) {
					WDS_Log_Post::log_progress( $args['log_id'], $args['progress'] );
				}
			}
		}
	}

	/**
	 * Handle parsing multidimensional arrays of args.
	 *
	 * @param array $args     The arguments to parse.
	 * @param array $defaults The defaults to combine with the regular arguments.
	 *
	 * @return array The parsed arguments.
	 */
	public static function deep_parse_args( $args, $defaults ) {
		foreach ( $args as $key => $value ) {
			// If we don't have a corresponding default, just continue.
			if ( ! isset( $defaults[ $key ] ) ) {
				continue;
			}

			// For arrays, do another round of parsing args.
			if ( is_array( $value ) ) {
				$args[ $key ] = self::deep_parse_args( $value, $defaults[ $key ] );
			}
		}

		// Now we're ready for the regular wp_parse_args() function
		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Check to see if a given post is translated.
	 * Much like checking to see if there is an EN equivelant to a post, there are a few ways to tell.
	 *
	 * @since 0.1.4
	 *
	 * @param int $post_id The post ID to check for.
	 *
	 * @return bool
	 */
	public static function is_post_translated( $post_id ) {
		// More advanced logic would be put in place for a real client site.
		return true;
	}
}
