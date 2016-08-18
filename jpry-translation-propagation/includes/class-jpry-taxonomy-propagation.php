<?php

/**
 * Class to handle propagation of taxonomy terms to all language sites.
 *
 * @since 0.1.0
 */
class JPry_Taxonomy_Propagation extends JPry_Abstract_Translation_Propagation {

	/**
	 * Whether we're using termmeta.
	 *
	 * @since 0.1.0
	 *
	 * @var bool
	 */
	private $using_termmeta;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->using_termmeta = function_exists( 'add_term_meta' );
		add_action( 'wds_async_create_term', array( $this, 'handle_data' ), 10 );
	}

	/**
	 * Handle distributing the data to all of the child sites.
	 *
	 * @since 0.1.0
	 *
	 * @param array $term The term data to synchronize.
	 */
	public function handle_data( $term ) {
		$meta = null;
		if ( $this->using_termmeta ) {
			$meta = get_term_meta( $term['term_id'], '' );
		}

		$this->send_to_each( $term['term_id'], $term, $meta );
	}

	/**
	 * Send data to each sub-site.
	 *
	 * @since 0.1.0
	 *
	 * @param int   $object_id The Object ID.
	 * @param array $data      The array of data to send.
	 * @param array $meta      The object meta to attach to each object.
	 */
	protected function send_to_each( $object_id, $data, $meta ) {
		$this->initialize_sites();

		// See if we already have language site meta.
		if ( count( $this->sites ) > 1 && isset( $meta[ "language_site_{$this->sites[1]['path']}" ] ) ) {
			$this->update_each( $object_id, $data, $meta );
			return;
		}

		$parents = $this->map_term_parents( $data, $meta );
		$args    = $this->parse_term_args( $data );
		$meta    = array();
		foreach ( $this->sites as $site ) {
			switch_to_blog( $site['blog_id'] );

			// Set the correct parent ID for the site.
			$args = $this->update_parent( $site, $parents, $args );

			$term = wp_insert_term( $data['name'], $data['taxonomy'], $args );
			if ( is_wp_error( $term ) ) {
				// If the term exists, still store the term ID in meta.
				if ( 'term_exists' == $term->get_error_code() ) {
					$meta[ $site['path'] ] = $term->get_error_data();
				}
				continue;
			}

			$meta[ $site['path'] ] = $term['term_id'];
			// TODO: Maybe add term meta?
			restore_current_blog();
		}

		if ( $this->using_termmeta ) {
			foreach ( $meta as $path => $term_id ) {
				add_term_meta( $object_id, "language_site_{$path}", $term_id );
			}
		}
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
		$this->initialize_sites();

		// Compile the post IDs for language sites into separate array.
		$language_sites = array();
		foreach ( $this->sites as $site ) {
			$meta_key   = "language_site_{$site['path']}";
			$meta_value = $meta[ $meta_key ];

			// Try to remove language meta.
			if ( isset( $meta[ $meta_key ] ) ) {
				$language_sites[ $site['path'] ] = $meta_value[0];
				unset( $meta[ $meta_key ] );
			}
		}

		$parents      = $this->map_term_parents( $data, $meta );
		$failed_sites = array();
		$args         = $this->parse_term_args( $data );
		$taxonomy     = $data['taxonomy'];
		foreach ( $this->sites as $site ) {
			$blog_id = $site['blog_id'];
			if ( ! isset( $language_sites[ $site['path'] ] ) ) {
				$failed_sites[ $blog_id ] = new WP_Error(
					'term_update_failure',
					__( 'No language meta', 'jpry-translation-propagation' )
				);
				continue;
			}

			$term_id = $language_sites[ $site['path'] ];
			switch_to_blog( $blog_id );

			// Verify that we still have the original term.
			$term = get_term( $term_id, $taxonomy );
			if ( ! $term ) {
				$failed_sites[ $blog_id ] = new WP_Error(
					'term_update_failure',
					__( 'The term to update no longer exists.' )
				);
				continue;
			}

			// Set the correct parent ID for the site.
			$args = $this->update_parent( $site, $parents, $args );

			$result = wp_update_term( $term_id, $taxonomy, $args );
			if ( is_wp_error( $result ) ) {
				$failed_sites[ $blog_id ] = $result;
				continue;
			}

			restore_current_blog();
		}

		if ( ! empty( $failed_sites ) ) {
			// TODO: Do something with the failure.
			error_log( __( 'Failed to send term to site. Data: ', 'jpry-translation-propagation' ) . print_r( $failed_sites, true ) );
		}
	}

	/**
	 * Update the term meta with the correct parent ID for each subsite.
	 *
	 * @since 0.1.0
	 *
	 * @param array $data The term data.
	 * @param array $meta The term meta.
	 *
	 * @return array Term parents.
	 */
	private function map_term_parents( $data, $meta ) {
		$parents = array();
		if ( 0 === (int) $data['parent'] ) {
			return $parents;
		}

		$parent_meta = get_term_meta( $data['term_id'], '', true );
		foreach ( $parent_meta as $meta_key => $meta_value ) {
			if ( 0 === strpos( $meta_key, 'language_site_' ) && isset( $meta[ $meta_key ] ) ) {
				$key             = substr( $meta_key, 14 );
				$parents[ $key ] = $meta_value;
			}
		}

		return $parents;
	}

	/**
	 * Update the parent ID for the language site.
	 *
	 * @since 0.1.0
	 *
	 * @param array $site    The site array.
	 * @param array $parents The array of parents.
	 * @param array $args    The array of term args.
	 *
	 * @return array The updated term args.
	 */
	private function update_parent( $site, $parents, $args ) {
		if ( isset( $parents[ $site['path'] ] ) ) {
			$args['parent'] = $parents[ $site['path'] ];
		}

		return $args;
	}

	/**
	 * Parse term data into args used to insert a new term.
	 *
	 * This isn't for a shortcode, but we use the shortcode_atts() function because ensures only the
	 * array keys we specify are defined, and removes the extras.
	 *
	 * @since 0.1.0
	 *
	 * @param array $term The term data.
	 *
	 * @return array The parsed term data.
	 */
	private function parse_term_args( $term ) {
		$args = shortcode_atts(
			array(
				'description' => '',
				'parent'      => 0,
				'slug'        => '',
			),
			$term
		);

		return $args;
	}

	/**
	 * Get a nonce to use with an API request.
	 *
	 * @return string
	 */
	protected function get_nonce_for_request() {
		return '';
	}
}
