<?php
namespace PF_Theme\Content\Widgets;


class WhyWeBake extends \WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pf_why_we_bake',
			esc_html__( 'Why We Bake', 'pf' ),
			array(
			'description' => esc_html__( 'Why We Bake Widget', 'pf' ),
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
		$this->enqueue_script();
		$defaults  = array(
			'title'       => __( 'Why We Bake', 'pf' ),
			'quote'       => '',
			'quote_from'  => '',
			'image'       => '',
		);
		$instance  = wp_parse_args( $instance, $defaults );
		$image_url = ! empty( $instance['image'] ) ? wp_get_attachment_image_src( $instance['image'], 'thumbnail', false ) : '';
		$hidden    = ( empty( $image_url ) ) ? 'hidden' : ''; ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'pf' ); ?></label>
			<input type="text"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'quote' ) ); ?>"><?php _e( 'Quote:', 'pf' ); ?></label>
			<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'quote' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quote' ) ); ?>"><?php echo esc_attr( $instance['quote'] ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'quote_from' ) ); ?>"><?php _e( 'Who is the Quote from:', 'pf' ); ?></label>
			<input type="text"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'quote_from' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'quote_from' ) ); ?>"
			       value="<?php echo esc_attr( $instance['quote_from'] ); ?>" />
		</p>
		<p>
			<input type="hidden"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'image' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'image' ) ); ?>"
			       value="<?php echo esc_attr( $instance['image'] ); ?>" />
			<button class="button select_image_button"><?php _e( 'Select Image', 'pf' ); ?></button>
			<button class="button delete_image_button"><?php _e( 'Delete Image', 'pf' ); ?></button>
			<br><br>
			<span class="widget-image <?php echo esc_attr( $hidden ); ?>">
				<?php if ( ! empty( $image_url ) ) { ?>
					<img src="<?php echo esc_url( $image_url[0] ); ?>" alt="" />
				<?php } ?>
			</span>
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

		include PF_PATH . 'template-parts/widget-why-we-bake.php';

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
			'title'       => '',
			'quote'       => '',
			'quote_from'  => '',
			'image'       => '',
		);

		foreach ( $new_instance as $key => $value ) {
			if ( ! isset( $valid_keys[ $key ] ) ) {
				continue;
			}

			switch ( $key ) {
				case 'image':
					$return[ $key ] = intval( $value );
					break;

				case 'title':
				case 'quote':
				case 'quote_from':
				default:
					$return[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $return;
	}

	/**
	 * Enqueue scripts to use with the widget.
	 *
	 * @param bool $minify
	 */
	public function enqueue_script( $minify = true ) {
		// Ensure the script is only enqueued once.
		static $already_enqueued = false;
		if ( $already_enqueued ) {
			return;
		}

		wp_enqueue_script( 'media-widget', PF_TEMPLATE_URL . '/assets/js/admin/media-widget.js', array( 'jquery' ), PF_VERSION, true );

		$l10n = array(
			'mediaTitle'  => __( 'Select or Upload Image', 'pf' ),
			'mediaButton' => __( 'Select', 'pf' ),
		);
		wp_localize_script( 'media-widget', 'mediaObject', $l10n );

		$already_enqueued = true;
	}
}
