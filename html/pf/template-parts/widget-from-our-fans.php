<?php
if ( ! isset( $args ) || ! is_array( $args ) ) {
	return;
}

$from_fans = \PF_Theme\Helper\get_from_our_fans_by_id( get_the_ID() );
shuffle( $from_fans );
$images = '';
$total  = 0; ?>

<div class="widget widget-instagram">
	<h2 class="title-illustration">
		<?php echo esc_html__( 'From Our Fans', 'pf' ); ?>
	</h2>
	<?php
	foreach ( $from_fans as $id => $data ) {
		// Once we have our two images, stop the loop.
		if ( 2 === $total ) {
			break;
		}

		$image_id = $data['_pf_image_id'];
		if ( ! isset( $image_id ) ) {
			continue;
		}

		$link           = '';
		$social_network = ( isset( $data['_pf_social_network'] ) ) ? $data['_pf_social_network'] : '';
		$handle         = ( isset( $data['_pf_user_handle'] ) ) ? $data['_pf_user_handle'] : '';
		if ( ! empty( $social_network ) ) {
			$link = 'https://' . $social_network . '.com/' . $handle;
		}

		printf( '<a href="%s">%s<span class="aspect-ratio-content">%s %s</span></a>',
			esc_url( $link ),
			wp_get_attachment_image( $image_id, 'medium', false, array( 'class' => 'aspect-ratio-content' ) ),
			PF_Theme\Core\get_icon( $social_network, '', '', 'pf-icon-', false ),
			esc_html( $social_network )
		);
		$total ++;
	}
	?>
</div>
