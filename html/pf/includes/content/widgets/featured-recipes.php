<?php
/**
 *
 */

namespace PF_Theme\Content\Widgets;


class FeaturedRecipes extends \WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pf_featured_recipes',
			esc_html__( 'Featured Recipes', 'pf' ),
			array(
				'description' => esc_html__( 'Featured Recipes Widget', 'pf' ),
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
		$product_id = 0;

		if ( is_singular( 'product' ) ) {
			$product_id = get_the_ID();
		} elseif ( is_tax( 'product-categories' ) ) {
			// Use the queried object to find the product.
			$queried_object = get_queried_object();
			$product_id = $this->id_from_term( $queried_object );
		}

		// If we still don't have a product ID, just return without displaying anything
		if ( empty ( $product_id ) || 0 === $product_id ) {
			return;
		}

		echo $args['before_widget'];
		\PF_Theme\Template_Tags\display_featured_recipes( $product_id );
		echo $args['after_widget'];
	}

	/**
	 * Based on a given term, retrieve a product ID.
	 *
	 * If needed this will find the top level term and query its term meta.
	 *
	 * @author Jeremy Pry
	 *
	 * @param \WP_Term $term The currently-queried term.
	 *
	 * @return int
	 */
	protected function id_from_term( $term ) {
		$prefix   = PF_PREFIX;
		$top_term = \PF_Theme\Helper\get_highest_term( $term );

		if ( false === $top_term ) {
			return 0;
		}

		// Look first fo the primary product ID based on meta.
		$term_meta = get_term_meta( $term->term_id, "{$prefix}primary_product_term_meta", true );
		if ( ! empty( $term_meta ) && isset( $term_meta[0][ $prefix . 'primary_product_id' ][0] ) ) {
			return $term_meta[0][ $prefix . 'primary_product_id' ][0];
		}

		// Try looking for the first product ID instead.
		$product_list = \PF_Theme\Cache\get_products_by_term( $top_term->term_id );
		if ( isset( $product_list[0]['postId'] ) ) {
			return $product_list[0]['postId'];
		}

		return 0;
	}
}
