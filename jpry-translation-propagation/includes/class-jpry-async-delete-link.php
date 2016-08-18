<?php

if ( class_exists( 'JPry_Async_Abstract' ) ) {
	/**
	 * Class to set up Asynchronous tasks for deleting links.
	 */
	class JPry_Async_Delete_Link extends JPry_Async_Abstract {

		/**
		 * The action to hook into.
		 *
		 * @var string
		 */
		protected $action = 'delete_link';

		/**
		 * JPry_Async_Delete_Link constructor.
		 */
		public function __construct() {
			parent::__construct( WP_Async_Task::BOTH );
		}

		/**
		 * Prepare any data to be passed to the asynchronous postback
		 *
		 * The array this function receives will be a numerically keyed array from
		 * func_get_args(). It is expected that you will return an associative array
		 * so that the $_POST values used in the asynchronous call will make sense.
		 *
		 * The array you send back may or may not have anything to do with the data
		 * passed into this method. It all depends on the implementation details and
		 * what data is needed in the asynchronous postback.
		 *
		 * Do not set values for 'action' or '_nonce', as those will get overwritten
		 * later in launch().
		 *
		 * @throws Exception If the postback should not occur for any reason
		 *
		 * @param array $data The raw data received by the launch method
		 *
		 * @return array The prepared data
		 */
		protected function prepare_data( $data ) {
			return array(
				'link_data' => get_bookmark( $data[0], ARRAY_A ),
			);
		}

		/**
		 * Run the do_action function for the asynchronous postback.
		 *
		 * This method needs to fetch and sanitize any and all data from the $_POST
		 * superglobal and provide them to the do_action call.
		 *
		 * The action should be constructed as "wp_async_task_$this->action"
		 */
		protected function run_action() {
			$link_data = $this->from_post( 'link_data' );

			if ( ! empty( $link_data ) ) {
				do_action( "wds_async_{$this->action}", $link_data );
			}
		}
	}
}
