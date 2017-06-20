<?php
/**
 *
 */

namespace PF_Theme\Content\Widgets;


class FromOurFans extends \WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pf_from_our_fans',
			esc_html__( 'From Our Fans', 'pf' ),
			array(
				'description' => esc_html__( 'From Our Fans Widget', 'pf' ),
			)
		);
	}

	/**
	 * Echoes the widget content.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		$template = locate_template( 'template-parts/widget-from-our-fans.php' );
		if ( ! empty( $template ) ) {
			require( $template );
		}
	}
}
