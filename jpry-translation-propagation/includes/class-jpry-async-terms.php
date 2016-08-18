<?php

if ( class_exists( 'JPry_Async_Abstract' ) ) {
	/**
	 * Class to handle Asynchronous create_term action.
	 *
	 * @since 0.1.0
	 */
	class JPry_Async_Terms extends JPry_Async_Abstract {

		/**
		 * Constructor to wire up the necessary actions
		 *
		 * Which hooks the asynchronous postback happens on can be set by the
		 * $auth_level parameter. There are essentially three options: logged in users
		 * only, logged out users only, or both. Set this when you instantiate an
		 * object by using one of the three class constants to do so:
		 *  - LOGGED_IN
		 *  - LOGGED_OUT
		 *  - BOTH
		 * $auth_level defaults to BOTH
		 *
		 * @since 0.1.0
		 *
		 * @throws Exception If the class' $action value hasn't been set
		 *
		 * @param int $auth_level The authentication level to use (see above)
		 */
		public function __construct( $auth_level = WP_Async_Task::LOGGED_IN ) {
			parent::__construct( $auth_level );
		}

		/**
		 * The action to hook into.
		 *
		 * @since 0.1.0
		 *
		 * @var string
		 */
		protected $action = 'create_term';

		/**
		 * Prepare any data to be passed to the asynchronous postback.
		 *
		 * @since 0.1.0
		 *
		 * @param array $data The raw data received by the launch method
		 *
		 * @return array The prepared data.
		 * @throws Exception If the postback should not occur for any reason.
		 */
		protected function prepare_data( $data ) {
			return array(
				'term_id'  => $data[0],
				'tt_id'    => $data[1],
				'taxonomy' => $data[2],
			);
		}

		/**
		 * Run the do_action function for the asynchronous postback.
		 *
		 * This method needs to fetch and sanitize any and all data from the $_POST
		 * superglobal and provide them to the do_action call.
		 *
		 * The action should be constructed as "wp_async_task_$this->action"
		 *
		 * @since 0.1.0
		 */
		protected function run_action() {
			$tt_id    = (int) $this->from_post( 'tt_id' );
			$taxonomy = $this->from_post( 'taxonomy' );
			$term     = get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy, ARRAY_A );

			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				do_action( "wds_async_{$this->action}", $term );
			}
		}
	}
}
