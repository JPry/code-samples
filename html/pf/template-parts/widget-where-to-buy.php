<?php
if ( ! isset( $instance ) || ! is_array( $instance ) ) {
	return;
}
$title       = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
$text        = ( isset( $instance['text'] ) ) ? $instance['text'] : '';
$button_link = ( isset( $instance['button_link'] ) ) ? $instance['button_link'] : '';
$button_text = ( isset( $instance['button_text'] ) ) ? $instance['button_text'] : '';
$image_id    = ( isset( $instance['image'] ) ) ? $instance['image'] : '';;
$image_src   = wp_get_attachment_image_src( $image_id, 'full' ); ?>
<div class="widget widget-where-to-buy">
	<div class="widget-image">
		<?php
		if ( $image_src && is_array( $image_src ) ) {
			$url = $image_src[0];
			$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true ); ?>
			<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" class="aspect-ratio-content" />
			<?php
		} ?>
	</div>
	<div class="widget-content">
		<h2 class="title-illustration">
			<?php echo PF_Theme\Core\get_icon( 'illustration-location' ); ?>
			<?php echo esc_html( $title ); ?>
		</h2>
		<p><?php echo esc_html( $text ); ?></p>
		<a href="<?php echo esc_url( $button_link ); ?>" class="button-regular"><?php echo esc_html( $button_text ); ?></a>
	</div>
</div>
