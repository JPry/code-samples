<?php
namespace PF_Theme\Content\Widgets;


class YearArchive extends \WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'ppf_year_archive',
			esc_html__( 'Year Archive', 'pf' ),
			array(
			'description' => esc_html__( 'Newsroom Archive by Year Widget', 'pf' ),
			)
		);
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return null
	 */
	public function form( $instance ) {
		$defaults = array(
			'title' => '',
		);
		$instance = wp_parse_args( $instance, $defaults ); ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'pf' ); ?></label>
			<input type="text"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<?php
	}

	/**
	 * Echoes the widget content.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		printf( '<h2 class="widget-title">%s</h2>', esc_html( $instance['title'] ) );
		echo '<ul>';
		$archive_args = array(
			'type'            => 'yearly',
			'limit'           => 10,
			'format'          => 'html',
			'show_post_count' => true,
		    'post_type'       => 'post',
		);
		wp_get_archives( $archive_args );
		echo '</ul>';
		echo $args['after_widget'];
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * This function should check that `$new_instance` is set correctly. The newly-calculated
	 * value of `$instance` should be returned. If false is returned, the instance won't be
	 * saved/updated.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$return     = array();
		$valid_keys = array(
			'title' => '',
		);

		foreach ( $new_instance as $key => $value ) {
			if ( ! isset( $valid_keys[ $key ] ) ) {
				continue;
			}

			switch ( $key ) {
				case 'title':
					$return[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $return;
	}
}
