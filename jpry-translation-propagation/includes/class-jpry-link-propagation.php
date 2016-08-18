<?php

/**
 *
 */
class JPry_Link_Propagation extends JPry_Abstract_Translation_Propagation {

	/**
	 * The current link ID.
	 *
	 * @var int
	 */
	protected $link_id = null;

	/**
	 * The meta key for storing propagation IDs.
	 *
	 * @var string
	 */
	protected $propagation_meta_id = 'wds_propagation_ids';

	/**
	 * The ID for the translation post object.
	 *
	 * @var int
	 */
	protected $translation_id = null;

	/**
	 * JPry_Link_Propagation constructor.
	 */
	public function __construct() {
		$actions = array(
			'edit_link',
			'add_link',
		);

		foreach ( $actions as $action ) {
			add_action( "wds_async_{$action}", array( $this, 'propagate' ), 10, 2 );
			add_action( "wds_async_nopriv_{$action}", array( $this, 'propagate' ), 10, 2 );
		}

		// Hook deletion actions.
		add_action( 'wds_async_delete_link', array( $this, 'delete' ) );
		add_action( 'wds_async_nopriv_delete_link', array( $this, 'delete' ) );
	}

	/**
	 * Propagate each link to the other sites in the network.
	 *
	 * @param int      $link_id The link ID.
	 * @param stdClass $link    The link object.
	 */
	public function propagate( $link_id, $link ) {
		$this->link_id = $link_id;
		$this->data    = $this->map_link_data( $link );
		$results = $this->send_to_each();
		update_post_meta( $this->get_translation_post_id(), $this->propagation_meta_id, $results );
	}

	/**
	 * Propagate link deletion across the network.
	 *
	 * @param array $link_data
	 */
	public function delete( $link_data ) {
		$data = $this->map_link_data( (object) $link_data );
		$this->delete_from_each( $data );
	}

	/**
	 * Map link fields to API fields.
	 *
	 * @param stdClass $link The link data from the database.
	 *
	 * @return array Mapped link data ready to send through the API.
	 */
	protected function map_link_data( $link ) {
		$data = array(
			'url'         => $link->link_url,
			'name'        => $link->link_name,
			'image'       => $link->link_image,
			'description' => $link->link_description,
			'visible'     => $link->link_visible,
			'author'      => $link->link_owner,
			'rating'      => $link->link_rating,
			'rel'         => $link->link_rel,
			'notes'       => $link->link_notes,
			'rss'         => $link->link_rss,
		);

		if ( ! empty( $link->link_target ) ) {
			$data['target'] = $link->link_target;
		}

		// Handle the link categories
		$cats = wp_get_object_terms( $link->link_id, 'link_category' );
		$data['raw_categories'] = array_map( array( $this, 'convert_term_object_to_array' ), $cats );

		return $data;
	}

	/**
	 * Get translation data for a particular link.
	 *
	 * @return array Array of translation data.
	 */
	protected function get_translated_languages() {
		$meta = get_post_meta( $this->get_translation_post_id(), 'wds_translations_existing_posts', true );

		return ( ! empty( $meta ) ) ? (array) $meta : array();
	}

	/**
	 * Retrieve the post ID for the translation post.
	 *
	 * @author Jeremy Pry
	 *
	 * @return int The translated post ID.
	 */
	protected function get_translation_post_id() {
		if ( null !== $this->translation_id ) {
			return $this->translation_id;
		}

		$query = new WP_Query();
		$args = array(
			'meta_query'  => array(
				array(
					'key'   => '_jpry_translator_id',
					'value' => $this->link_id,
				),
				array(
					'key'   => '_jpry_translator_type',
					'value' => 'link',
				),
				'relation' => 'AND',
			),
			'post_status' => 'any',
			'post_type'   => 'wds-dyn-translation',
		);

		$posts = $query->query( $args );
		if ( empty( $posts ) ) {
			$this->translation_id = 0;
			return $this->translation_id;
		}
		$post = $posts[0];
		$this->translation_id = $post->ID;

		return $this->translation_id;
	}

	/**
	 * Convert a WP_Term or stdClass object to an array, and remove unnecessary keys.
	 *
	 * @param WP_Term|stdClass $object
	 *
	 * @return array
	 */
	public function convert_term_object_to_array( $object ) {

		// Convert to array
		$array = get_object_vars( $object );

		// Keep only what we need.
		$keep = array(
			'name'        => true,
			'slug'        => true,
			'description' => true,
		);
		$array = array_intersect_key( $array, $keep );

		return $array;
	}

	/**
	 * Send link data to each site in the network.
	 *
	 * @return array The array of results keyed to the path.
	 */
	protected function send_to_each() {
		$args       = $this->maybe_fix_iis( $this->get_default_api_args() );
		$limit_args = $this->translate_args( $args );
		$mapping    = $this->get_language_mapping();
		$results    = array();

		foreach ( $this->sites() as $this->site ) {
			$path = $this->site['path'];
			if ( isset( $mapping['translated'][ $path ] ) ) {
				$result = $this->post_to_site( $limit_args, $mapping['translated'][ $path ] );
			} else {
				$id     = isset( $mapping['propagated'][ $path ] ) ? $mapping['propagated'][ $path ] : 0;
				$result = $this->post_to_site( $args, $id );
			}

			$id = $this->get_id_from_response( $result );
			if ( 0 === $id ) {
				continue;
			}

			$results[ $path ] = $id;
		}

		return $results;
	}

	/**
	 * Get a mapping of translated and propagated IDs.
	 *
	 * This will return an array like this:
	 *
	 *   array(
	 *      'translated' => array(
	 *          'fr' => 123,
	 *          'et' => 456,
	 *      ),
	 *      'propagated' => array(
	 *          'fr' => 123,
	 *          'et' => 456,
	 *          'ar' => 124,
	 *          // etc...
	 *      ),
	 *   )
	 *
	 * @author Jeremy Pry
	 * @return array Multidimensional array of translated and propagated IDs.
	 */
	protected function get_language_mapping() {
		$languages      = $this->get_translated_languages();
		$propagated_ids = $this->get_propagated_ids();

		if ( empty( $languages ) && empty( $propagated_ids ) ) {
			return array(
				'translated' => array(),
				'propagated' => array(),
			);
		}

		// Get only the necessary language data
		$languages = wp_list_pluck( $languages, 'update_id' );

		// Return the combined arrays.
		return array(
			'translated' => $languages,
			'propagated' => $propagated_ids,
		);
	}

	/**
	 * Use the response from an API request to get an ID.
	 *
	 * @author Jeremy Pry
	 *
	 * @param array|WP_Error $response The API response.
	 *
	 * @return int The remote ID.
	 */
	protected function get_id_from_response( $response ) {
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( $this->maybe_undo_iis_response( wp_remote_retrieve_body( $response ) ), true );

		return ( isset( $body['id'] ) && $code >= 200 && $code < 300 ) ? (int) $body['id'] : 0;
	}

	/**
	 * IIS and location headers don't play nicely together. This helps a little.
	 *
	 * Removes the Document Moved HTML from a response if there is a valid JSON object behind it.
	 *
	 * @param mixed $response The response from the API call.
	 *
	 * @return mixed
	 */
	protected function maybe_undo_iis_response( $response ) {
		if ( ! is_string( $response ) || false === stripos( $response, '<title>Document Moved</title>' ) ) {
			return $response;
		}

		return preg_replace( '/(<head>)?<title>Document Moved.*<\/a>(<\/body>)?/s', '', $response );
	}

	/**
	 * Delete a link from each site.
	 *
	 * @param $data
	 */
	protected function delete_from_each( $data ) {
		$args = array(
			'body'    => json_encode( array(
				'force' => true,
				'url'   => $data['url'],
			) ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'method'  => 'DELETE',
		);

		$args = JPry_Utility::deep_parse_args( $args, $this->get_default_api_args() );
		$args = $this->maybe_fix_iis( $args );

		foreach ( $this->sites() as $this->site ) {
			$result = $this->post_to_site( $args );
			//error_log( 'Deletion result: ' . print_r( $result, true ) );
		}
	}

	/**
	 * Remove certain body args for translated posts.
	 *
	 * @param array $args The args to process.
	 *
	 * @return array The updated args.
	 */
	protected function translate_args( $args ) {
		unset( $args['body']['name'] );
		return $args;
	}

	/**
	 * Send a request through the API with wp_remote_request().
	 *
	 * @param array $args The arguments to use in the request.
	 * @param int $id
	 *
	 * @return array|WP_Error
	 */
	protected function post_to_site( $args, $id = 0 ) {
		$path = $this->get_api_path();
		if ( $id > 0 ) {
			$path .= "/{$id}";
		}
		$url  = get_rest_url( $this->site['blog_id'], $path );
		//error_log( 'Posting to URL ' . $url );
		return wp_remote_request( $url, $args );
	}

	/**
	 * Get the base API path.
	 *
	 * @return string
	 */
	protected function get_api_path() {
		return apply_filters( 'wds_api_link_path', '/wds/v1/links' );
	}

	/**
	 * Get a nonce to use with an API request.
	 *
	 * @return string
	 */
	protected function get_nonce_for_request() {
		return wp_create_nonce( 'wp_rest' );
	}

	/**
	 * Get default set of args for an API request.
	 *
	 * @return array
	 */
	protected function get_default_api_args() {
		$defaults = parent::get_default_api_args();
		$args     = array(
			'timeout'  => 30,
			'blocking' => true,
		);

		return JPry_Utility::deep_parse_args( $args, $defaults );
	}

	/**
	 * Get array of Link IDs that have been propagated.
	 *
	 * @author Jeremy Pry
	 * @return array
	 */
	protected function get_propagated_ids() {
		$meta = get_post_meta( $this->get_translation_post_id(), $this->propagation_meta_id, true );

		return ( ! empty( $meta ) ) ? (array) $meta : array();
	}
}
