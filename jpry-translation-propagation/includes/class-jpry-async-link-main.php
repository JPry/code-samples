<?php

/**
 *
 */
class JPry_Async_Link_Main extends JPry_Async_Abstract {

	/**
	 * Valid link actions this class can hook into.
	 *
	 * @var array
	 */
	protected $valid_actions = array(
		'edit_link' => 1,
		'add_link'  => 1,
	);

	/**
	 * JPry_Async_Link_Main constructor.
	 */
	public function __construct( $link_action ) {
		if ( ! isset( $this->valid_actions[ $link_action ] ) ) {
			throw new Exception( 'Invalid Link action.' );
		}

		$this->action = $link_action;
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
		// Only run this on the main site.
		if ( ! is_main_site() ) {
			throw new \Exception( 'This is not the main site!' );
		}

		// If we already have data for this request, don't start a new request.
		if ( ! empty( $this->_body_data ) ) {
			throw new \Exception( 'We already have data to send' );
		}

		return array(
			'link_id' => $data[0],
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
		$link_id = (int) $this->from_post( 'link_id' );
		$link    = get_bookmark( $link_id );

		if ( null !== $link && ! empty( $link ) ) {
			do_action( "wds_async_{$this->action}", $link_id, $link );
		}
	}
}
