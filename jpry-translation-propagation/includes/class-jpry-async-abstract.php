<?php

if ( class_exists( 'WP_Async_Task' ) ) {
	/**
	 *
	 */
	abstract class JPry_Async_Abstract extends WP_Async_Task {

		/**
		 * Retrieve a value from $_POST.
		 *
		 * @since 0.1.0
		 *
		 * @param string $key     The key to retrieve.
		 * @param null   $default The default if the key doesn't exist.
		 *
		 * @return mixed The value from the $_POST array, or else the default.
		 */
		protected function from_post( $key, $default = null ) {
			if ( isset( $_POST[ $key ] ) ) {
				return $_POST[ $key ];
			} else {
				return $default;
			}
		}
	}
}
