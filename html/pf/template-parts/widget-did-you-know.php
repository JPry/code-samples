<?php
if ( ! isset( $args ) || ! is_array( $args ) ) {
	return;
}

$posts = \PF_Theme\Helper\get_did_you_know_by_id( get_the_ID() );
if ( false === $posts ) {
	return;
}

// Grab a random one of the posts.
$index    = rand( 0, count( $posts ) - 1 );
$dyk_post = get_post( $posts[ $index ] );
$title    = esc_html__( 'Did you know?', 'pf' );

?>
<div class="widget widget-did-you-know">
	<h2 class="title-illustration">
		<?php
		PF_Theme\Core\get_icon( 'illustration-question' );
		esc_html_e( 'Did you know?', 'pf' );
		?>
	</h2>
	<div class="widget-image">
		<?php echo get_the_post_thumbnail( $dyk_post, 'full', array( 'class' => 'aspect-ratio-content' ) ); ?>
	</div>
	<div class="widget-content">
		<h2 class="title-illustration">
			<?php
			PF_Theme\Core\get_icon( 'illustration-question' );
			esc_html_e( 'Did you know?', 'pf' );
			?>
		</h2>
		<?php echo wpautop( wp_kses_post( $dyk_post->post_content ) ); ?>
	</div>
</div>

<?php
// Remove our variables.
unset( $index, $dyk_post, $title );

