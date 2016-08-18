<?php

if ( class_exists( 'JPry_Async_Abstract' ) ) {
	/**
	 * Class to set up Asynchronous tasks for saving posts.
	 *
	 * @since 0.1.0
	 */
	class JPry_Async_Post extends JPry_Async_Abstract {

		/**
		 * The post type to hook into.
		 *
		 * @since 0.1.0
		 *
		 * @var array
		 */
		protected $post_types;

		/**
		 * The action to hook into.
		 *
		 * @since 0.1.0
		 *
		 * @var string
		 */
		protected $action = 'save_post';

		/**
		 * Constructor.
		 *
		 * @since 0.1.0
		 *
		 * @param array $post_types Array of post types to hook into. The post types should be keys in the array,
		 *                          with the values as any data (int 1 recommended).
		 */
		public function __construct( array $post_types ) {
			$this->post_types = $post_types;
			parent::__construct( WP_Async_Task::BOTH );
		}

		/**
		 * Prepare any data to be passed to the asynchronous postback.
		 *
		 * No translation for thrown Exceptions because they are not displayed to end users.
		 *
		 * @since 0.1.0
		 *
		 * @param array $data The raw data received by the launch method.
		 *
		 * @return array The prepared data.
		 */
		protected function prepare_data( $data ) {
			$this->maybe_skip_post( $data );

			$return = array(
				'post_id' => $data[0],
				'update'  => $data[2] ? 'yes' : 'no',
			);

			return $return;
		}

		/**
		 * Run the do_action function for the asynchronous postback.
		 *
		 * @since 0.1.0
		 */
		protected function run_action() {
			$post_id = $this->from_post( 'post_id' );
			$update  = 'yes' === strtolower( $this->from_post( 'update' ) );
			$post    = get_post( $post_id );

			if ( $post ) {
				do_action( "wds_async_{$this->action}", $post_id, $post, $update );
			}
		}

		/**
		 * Possibly skip propagating a post.
		 *
		 * @param array $data The raw data from the save_post hook.
		 *
		 * @throws \Exception If the postback should not occur for any reason.
		 */
		protected function maybe_skip_post( $data ) {
			// Only run this on the main site.
			if ( ! is_main_site() ) {
				throw new \Exception( 'This is not the main site!' );
			}

			// If we already have data for this request, don't start a new request.
			if ( ! empty( $this->_body_data ) ) {
				throw new \Exception( 'We already have data to send' );
			}

			$post = $data[1];
			if ( ! $post instanceof WP_Post ) {
				throw new \Exception( '$post is not a WP_Post object.' );
			}

			// Check for our meta override value; once we propagate, we need to continue propagating.
			if ( get_post_meta( $post->ID, '_jpry_propagation', true ) ) {
				return;
			}

			if ( ! isset( $this->post_types[ $post->post_type ] ) ) {
				throw new \Exception( 'Incorrect post type.' );
			}

			// Only propagate when it's a published post.
			if ( 'publish' !== $post->post_status ) {
				throw new \Exception( 'Incorrect post status.' );
			}

			// Only propagate when a MS author is making the updates, or if "show in help pane".
			if ( ! current_user_can( 'publish_posts' ) && 'on' !== get_post_meta( $post->ID, 'incsub_wiki_show_in_help_pane', true ) ) {
				throw new \Exception( 'Must be Author or above to propagate posts' );
			}
		}
	}
}
