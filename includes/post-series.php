<?php
/**
 * Custom tweaks for the post series plugin
 */

/**
 * Remove the post-series CSS
 */
function pp_remove_post_series_css() {
	$post_series = post_series();
	
	remove_action( 'wp_print_styles', array( $post_series, '_setup_styles' ) );
	remove_action( 'wp_print_scripts', array( $post_series, '_setup_scripts' ) );
}
//add_action( 'template_redirect', 'pp_remove_post_series_css' );


// filter series post content


/**
 * Remove the post-series content filter
 */
function pp_custom_remove_the_content_filter() {
	$post_series = post_series();
	remove_filter( 'the_content', array( $post_series, '_the_content' ), -1 );
	add_filter( 'the_content', 'pp_custom_the_content', -1 );
}
//add_action( 'template_redirect', 'pp_custom_remove_the_content_filter' );

/*
* Series content filter
*/
function pp_custom_the_content( $content ) {
	global $post;

	// when feed return content
	if ( is_feed() ) {
		return $content;
	}

	$series_id = get_post_meta( get_the_ID(), 'series_id', true );

	// post has a series ID
	if ( $series_id ) {
		// get series content
		$series_content = pp_custom_get_post_series( $series_id, $post->ID );

		// add to content
		if ( is_single() || is_page() ) {
			return $series_content . $content;
		} else {
			return $content . $series_content;
		}
	}

	return $content;
}

function pp_custom_get_post_series( $series_id, $post_id ) {

	if ( is_numeric( $series_id ) && get_post_status( $series_id ) == 'publish' ) {
	    $series_posts = get_post_meta( $series_id, 'series_posts', false );
	    $count = 0;
	    $part = 0;
	    $past = false;
	    $list = '';

	    // loop through the series_posts array
		foreach( $series_posts[0] as $series ) {
			$count ++;

			// setup current post info
			$series_permalink = get_permalink($series['id']);
			$series_title = get_the_title($series['id']);

			// setup next post using current post info when $past is true
			if ( $past ) {
				$next = $series_permalink;
				$next_title = $series_title;
				$past = false;
			}

			// current post
			if ( $series['id'] == $post_id ) {
				$list .= '<li>' . $series_title . '</li>';
				$part = $count;
				$past = true;
			}
			// prev & next posts
			else {
				$list .= '<li><a href="' . $series_permalink . '">' . $series_title . '</a></li>';
			}

			// use previous post info
			if ( $part == $count ) {
				$prev = ( isset( $last_url ) ) ? $last_url : '';
				$prev_title = ( isset( $last_title ) ) ? $last_title : '';
			}

			// setup previous post info
			$last_url = $series_permalink;
			$last_title = $series_title;
		}

	    // default content returned
	    $content = '<div id="series-meta" class="alert notice"><p>This entry is part ' . $part . ' of ' . $count . ' in the <a href="' . get_permalink( $series_id ) . '" class="series-title">' . get_the_title( $series_id ) . '</a> Series</p></div>';

		// setup up series navigation & post list
		if ( is_single() || is_page() ) {
			$prev = ( isset( $prev ) && $prev_title ) ? '<a href="' . $prev . '" class="prev-link"><span class="meta-nav">&larr;</span> ' . $prev_title . '</a>' : '';
			$next = ( isset( $next ) && $next_title ) ? '<a href="' . $next . '" class="next-link">' . $next_title . ' <span class="meta-nav">&rarr;</span></a>' : '';
			$content .= '<div id="series-nav">' . $prev . $next . '</div><ul id="series-list">' . $list . '</ul>';
		}

		return '<div id="series-content"' . ( ( is_single() || is_page() ) ? ' class="single-post"' : '' ) . '>' . $content . '</div>';
	}
}


/**
 * Add the contents of the series to the main series page
 */
function pippin_series_nav($content) {
	if ( is_singular('series') ) {
		$content .= series_posts();
	}
	return $content;
}	
add_filter('the_content', 'pippin_series_nav');