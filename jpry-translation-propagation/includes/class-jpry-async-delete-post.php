<?php

if ( class_exists( 'JPry_Async_Abstract' ) ) {
	/**
	 * Class to set up Asynchronous tasks for deleting posts.
	 *
	 * @since 0.2.0
	 */
	class JPry_Async_Delete_Post extends JPry_Async_Abstract {

		/**
		 * The action to hook into.
		 *
		 * We use 'before_delete_post' instead of 'delete_post' to ensure that we still have access to the metadata.
		 *
		 * @since 0.2.0
		 *
		 * @var string
		 */
		protected $action = 'before_delete_post';

		/**
		 * The post type to hook into.
		 *
		 * @since 0.2.0
		 *
		 * @var array
		 */
		protected $post_types;

		/**
		 * Constructor.
		 *
		 * Ensure that this is only available when logged in.
		 *
		 * @since 0.2.0
		 *
		 * @param array $post_types Array of post types to hook into. The post types should be keys in the array,
		 *                          with the values as any data (int 1 recommended).
		 */
		public function __construct( array $post_types ) {
			$this->post_types = $post_types;
			parent::__construct( WP_Async_Task::LOGGED_IN );
		}

		/**
		 * Prepare any data to be passed to the asynchronous postback.
		 *
		 * @since 0.2.0
		 *
		 * @throws Exception If the postback should not occur for any reason.
		 *
		 * @param array $data The raw data received by the launch method.
		 *
		 * @return array The prepared data.
		 */
		protected function prepare_data( $data ) {
			$post_id = $data[0];
			$post    = get_post( $post_id );

			if ( ! isset( $this->post_types[ $post->post_type ] ) ) {
				throw new \Exception( 'Incorrect post type.' );
			}

			// Pass on the raw metadata, as it will likely be deleted before the postback can run.
			$meta = get_post_meta( $post_id );

			return array(
				'post_id'   => $post_id,
				'raw_meta'  => $meta,
				'post_type' => $post->post_type,
			);
		}

		/**
		 * Run the do_action function for the asynchronous postback.
		 *
		 * @since 0.2.0
		 */
		protected function run_action() {
			$meta      = $this->from_post( 'raw_meta' );
			$post_id   = $this->from_post( 'post_id' );
			$post_type = $this->from_post( 'post_type' );

			if ( ! empty( $meta ) ) {
				do_action( "wds_async_{$this->action}", $meta, $post_type, $post_id );
			}
		}
	}
}
