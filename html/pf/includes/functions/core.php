<?php
namespace PF_Theme\Core;

/**
 * Set up theme defaults and register supported WordPress features.
 *
 * @since 0.1.0
 *
 * @uses add_action()
 *
 * @return void
 */
function setup() {
	add_action( 'widgets_init', __NAMESPACE__ . 'register_widgets' );
}

/**
 * Register Sidebars and Widgets for the theme.
 *
 * @return void
 */
function register_widgets() {
	register_sidebar( array(
		'name'          => 'Newsroom Sidebar',
		'id'            => 'newsroom_sidebar',
		'before_widget' => '<section class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => 'Products Page Sidebar',
		'id'            => 'product_page_sidebar',
		'before_widget' => '',
		'after_widget'  => '',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Product Group Sidebar' ),
		'id'            => 'product_group_sidebar',
		'before_widget' => '',
		'after_widget'  => '',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Product Post Sidebar' ),
		'id'            => 'product_post_sidebar',
		'before_widget' => '',
		'after_widget'  => '',
	) );

	register_widget( '\\PF_Theme\\Content\\Widgets\\YearArchive' );
	register_widget( '\\PF_Theme\\Content\\Widgets\\WhereToBuy' );
	register_widget( '\\PF_Theme\\Content\\Widgets\\WhyWeBake' );
	register_widget( '\\PF_Theme\\Content\\Widgets\\FromOurFans' );
	register_widget( '\\PF_Theme\\Content\\Widgets\\DidYouKnow' );
	register_widget( '\\PF_Theme\\Content\\Widgets\\FeaturedRecipes' );
}
