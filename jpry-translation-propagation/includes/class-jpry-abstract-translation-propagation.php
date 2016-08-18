<?php

/**
 * Abstract class for sending Translation data to each sub-site.
 *
 * @since 0.1.0
 */
abstract class JPry_Abstract_Translation_Propagation {

	/**
	 * The data to be sent with each request.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The current site when iterating through each site.
	 *
	 * @var array
	 */
	protected $site;

	/**
	 * The sites to send data to.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	protected $sites;

	/**
	 * Initialize the sites array if needed.
	 *
	 * @since 0.1.0
	 */
	protected function initialize_sites() {
		if ( empty( $this->sites ) ) {
			$this->sites = JPry_Utility::get_network_sites( array( 'strip_path_slashes' => true ) );
		}
	}

	/**
	 * Get the array of sites.
	 *
	 * @return array
	 */
	public function sites() {
		$this->initialize_sites();
		return $this->sites;
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
				'X-WP-Nonce' => $this->get_nonce_for_request(),
			),
			'method'   => 'POST',
			'timeout'  => 1,
		);
	}

	/**
	 * Build an array of cookies that can be passed via a POST request.
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
	 * Get a nonce to use with an API request.
	 *
	 * @return string
	 */
	abstract protected function get_nonce_for_request();
}
