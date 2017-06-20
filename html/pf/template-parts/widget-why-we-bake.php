<?php
if ( ! isset( $instance ) || ! is_array( $instance ) ) {
	return;
}
$title       = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
$quote       = ( isset( $instance['quote'] ) ) ? $instance['quote'] : '';
$quote_from  = ( isset( $instance['quote_from'] ) ) ? $instance['quote_from'] : '';
$image_id    = ( isset( $instance['image'] ) ) ? $instance['image'] : '';;
$image_src   = wp_get_attachment_image_src( $image_id, 'full' ); ?>
<div class="widget widget-why-we-bake">
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
			<?php echo PF_Theme\Core\get_icon( 'illustration-pan' ); ?>
			<?php echo esc_html( $title ); ?>
		</h2>
		<blockquote>
			<p>“<?php echo esc_html( $quote ); ?>”</p>
			<cite>–<?php echo esc_html( $quote_from ); ?></cite>
		</blockquote>
	</div>
</div>
