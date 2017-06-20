<?php
namespace PF_Theme\Content\Widgets;


class WhereToBuy extends \WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pf_where_to_buy',
			esc_html__( 'Where To Buy', 'pf' ),
			array(
			'description' => esc_html__( 'Where To Buy Widget', 'pf' ),
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
			'title'       => __( 'Where To Buy', 'pf' ),
			'text'        => '',
			'button_text' => __( 'Product Locator', 'pf' ),
			'button_link' => '',
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
			<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php _e( 'Description:', 'pf' ); ?></label>
			<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"><?php echo esc_attr( $instance['text'] ); ?></textarea>
			<span class="description"><?php _e( 'This text is displayed under the title.', 'pf' ); ?></span>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>"><?php _e( 'Button Text:', 'pf' ); ?></label>
			<input type="text"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>"
			       value="<?php echo esc_attr( $instance['button_text'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'button_link' ) ); ?>"><?php _e( 'Button Link:', 'pf' ); ?></label>
			<input type="text"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'button_link' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'button_link' ) ); ?>"
			       value="<?php echo esc_attr( $instance['button_link'] ); ?>" />
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

		include PF_PATH . 'template-parts/widget-where-to-buy.php';

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
			'text'        => '',
			'button_text' => '',
			'button_link' => '',
			'image'       => '',
		);

		foreach ( $new_instance as $key => $value ) {
			if ( ! isset( $valid_keys[ $key ] ) ) {
				continue;
			}

			switch ( $key ) {
				case 'button_link':
					$return[ $key ] = esc_url_raw( $value );
					break;

				case 'image':
					$return[ $key ] = intval( $value );
					break;

				case 'title':
				case 'button_text':
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
