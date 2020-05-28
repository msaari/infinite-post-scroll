<?php
/*
Plugin Name: Infinite post scroll
Description: Infinite scrolling feature for single post WordPress pages.
Version: 1
Author: Mikko Saari
Author URI: https://www.mikkosaari.fi/
*/

add_action( 'wp_ajax_infinite_post_scroll_generate_post', 'infinite_post_scroll_generate_post' );
add_action( 'wp_ajax_nopriv_infinite_post_scroll_generate_post', 'infinite_post_scroll_generate_post' );

add_action( 'wp_enqueue_scripts', 'infinite_post_scroll_scripts' );

/**
 * Enqueues the infinite post scroll scripts on single post pages.
 */
function infinite_post_scroll_scripts() {
	if ( ! is_single() ) {
		return;
	}

	global $post;

	wp_enqueue_script(
		'infinite_post_scroll',
		plugin_dir_url( __FILE__ ) . 'js/infinite-post-scroll.js',
		array( 'jquery' ),
		1,
		true
	);

	// Adds the admin ajax URL and the current post id for the initial post.
	wp_localize_script(
		'infinite_post_scroll',
		'infinite_post_scroll',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'post_ID'  => $post->ID,
		)
	);
}

/**
 * Generates post HTML for the previous post.
 *
 * Gets the current post ID as a parameter, fetches the previous post from
 * the database and generates HTML element from the post.
 */
function infinite_post_scroll_generate_post() {
	if ( empty( $_POST ) ) {
		wp_send_json_error( "No parameters", 401 );
	}
	if ( empty( $_POST['ID'] ) ) {
		wp_send_json_error( "No post ID defined", 401 );
	}

	$post_object = get_post( $_POST['ID'] );
	if ( ! $post_object ) {
		wp_send_json_error( "No post found", 404 );
	}

	$previous_post_id = infinite_post_scroll_get_previous_post( $post_object );
	$content          = infinite_post_scroll_get_rendered_post( $previous_post_id );

	wp_send_json_success( array( 'html' => $content ) );
}

/**
 * Fetches the previous post ID.
 *
 * It would be nice to be able to use get_adjacent_post(), but that
 * requires a post loop. Instead a direct database query is used, fetching
 * the previous published post in the order of post_date.
 *
 * @param WP_Post The post object for the current post
 *
 * @return string|null The previous post ID, null if nothing found.
 */
function infinite_post_scroll_get_previus_post( $post_object ) {
	/**
	 * I want posts from only particular category. Instead of using the 
	 * category ID, I use the term_taxonomy_id, to avoid joining an extra
	 * database table to the query.
	 */
	$term_tax_id = 5235;

	global $wpdb;
	$previous_post_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT p.ID FROM $wpdb->posts AS p, $wpdb->term_relationships AS tr
		WHERE p.ID = tr.object_id
		AND tr.term_taxonomy_id = %d
		AND p.post_status = %s
		AND p.post_date < %s
		ORDER BY p.post_date DESC LIMIT 1",
		$term_tax_id, 'publish', $post_object->post_date )
	);

	/**
	 * If you don't need a category restriction, the query becomes cleaner:
	 
	$previous_post_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM $wpdb->posts
		WHERE post_status = %s
		AND post_date < %s
		ORDER BY post_date DESC LIMIT 1",
		'publish', $post_object->post_date )
	);
	*/

	return $previous_post_id;
}

/**
 * Renders the post in HTML.
 *
 * This function will require most customization to suit your personal needs.
 * This is an example of how it works. Note that many template functions echo
 * out their results, so look for the versions that don't echo but instead
 * return.
 *
 * Key thing is to wrap the individual post in an article tag with two data
 * attributes: url that has the permalink to the page and id that has the
 * post ID. These are required for the infinite post scroll javascript and
 * if they're missing, things won't work.
 *
 * @param string $post_id The post ID to render.
 *
 * @return string The post rendered in HTML.
 */
function infinite_post_scroll_get_rendered_post( $post_id ) {
	$title        = get_the_title( $post_id ), true, $post_id;
	$entry_meta   = ''; // Add a function here that renders the post meta.
	$content      = wpautop( get_the_content( null, false, $post_id ) );
	$url  	 	  = get_permalink( $post_id );

	// Let's get a list of tags.
	$post_tags = get_the_term_list( $post_id, 'post_tag', '<p>Tags: ', ', ', '</p>' );

	// Some Relevanssi Premium related posts would be nice as well:
	$related_posts = '';
	if ( function_exists( 'relevanssi_related_posts' ) ) {
		$related_posts = relevanssi_related_posts();
	}

	// Let's build it all here:
	$rendered_post = <<<EOHTML
<hr />

<article id="post-$post_id" class="article-$post_id" data-url="$url" data-id="$post_id">
	<header class="entry-header">
		<h1 class="entry-title">$title</h1>

		<div class="entry-meta">
			$entry_meta
		</div>
	</header>

	<div class="entry-content">
		$content
	</div><!-- .entry-content -->
		
	<footer class="entry-footer clear">
		$post_tags

		$related_posts
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->
EOHTML;

	return $rendered_post;
}
