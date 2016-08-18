<?php
/**
 * A collection of template tags to use in conjunction with this plugin.
 */


/**
 * Get a query object for posts featured on the home page.
 *
 * @author Jeremy Pry
 * @return WP_Query
 */
function jpry_get_home_featured_query() {
	// If we're missing our plugin class, return an empty query.
	if ( ! class_exists( 'JPry_Post_Metabox' ) ) {
		return new WP_Query( array(
			'ignore_sticky_posts' => true,
			'post__in'            => array( 0 ),
		) );
	}

	return JPry_Post_Metabox::get_instance()->get_home_featured_posts();
}

/**
 * Get a query object for posts featured on a category archive page.
 *
 * @author Jeremy Pry
 *
 * @param int $category_id The category ID for the posts.
 *
 * @return WP_Query
 */
function jpry_get_archive_featured_query( $category_id ) {
	// If we're missing our plugin class, return an empty query.
	if ( ! class_exists( 'JPry_Post_Metabox' ) ) {
		return new WP_Query( array(
			'ignore_sticky_posts' => true,
			'post__in'            => array( 0 ),
		) );
	}

	return JPry_Post_Metabox::get_instance()->get_archive_featured_posts( $category_id );
}

/**
 * Get the first featured post for each of the 3 categories.
 *
 * The posts will be in this order:
 * - Fantasy Sports
 * - Gaming
 * - Poker
 *
 * @author Jeremy Pry
 * @return WP_Query
 */
function jpry_get_category_featured_posts() {
	// If we're missing our plugin class, return an empty query.
	if ( ! class_exists( 'JPry_Post_Metabox' ) ) {
		return new WP_Query( array(
			'ignore_sticky_posts' => true,
			'post__in'            => array( 0 ),
		) );
	}

	return JPry_Post_Metabox::get_instance()->get_home_category_featured_posts();
}

/**
 * Get a WP_Query object for related posts.
 *
 * @author Jeremy Pry
 *
 * @param int $num_posts Number of posts to retrieve.
 * @param int $post_id   The ID of the current post. If omitted, it will use the ID of the current post.
 *
 * @return false|WP_Query False when the current post has no tags or categories, or a WP_Query object on success.
 */
function jpry_get_related_posts( $num_posts = 3, $post_id = null ) {
	$post_id = $post_id ?: (int) get_the_ID();

	if ( ! $post_id ) {
		return false;
	}

	$term_type = 'tag';
	$term_ids  = wp_get_object_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );

	// If there are no tags, use categories.
	if ( empty( $term_ids ) ) {
		$term_ids  = wp_get_object_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
		$term_type = 'category';

		// If no categories either, then just return nothing.
		if ( empty( $term_ids ) ) {
			return false;
		}
	}

	// Handle caching for the post IDs in the query.
	$key      = "jpry_related_posts_{$post_id}_{$num_posts}";
	$post_ids = get_transient( $key );
	if ( false === $post_ids ) {
		$query_args = array(
			'post_type'           => 'post',
			'posts_per_page'      => $num_posts,
			"{$term_type}__in"    => $term_ids,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'post__not_in'        => array( $post_id ),
		);

		$query    = new WP_Query( $query_args );
		$post_ids = $query->posts;
		set_transient( $key, $post_ids, DAY_IN_SECONDS );
	}

	return new WP_Query( array(
		'post_type'           => 'post',
		'post__in'            => $post_ids,
		'ignore_sticky_posts' => true,
	) );
}
