<?php

add_image_size('fb-preview', 90, 90, true);

// Get featured image
function pippin_get_FB_image($post_ID) {
	$post_thumbnail_id = get_post_thumbnail_id( $post_ID );
	if ($post_thumbnail_id) {
		$post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, 'fb-preview');
		return $post_thumbnail_img[0];
	}
}

// Get post excerpt
function pippin_get_FB_description($post) {
	if ($post->post_excerpt) {
		return $post->post_excerpt;
	} else {
		// Post excerpt is not set, so we take first 55 words from post content
		$excerpt_length = 55;
		// Clean post content
		$text = str_replace("\r\n"," ", strip_tags(strip_shortcodes(htmlentities($post->post_content))));
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			$excerpt = implode(' ', $words);
			return $excerpt;
		}
	}
}

function pippin_FB_header() {
	global $post;
	if ( is_singular()) { 
		$post_description = pippin_get_FB_description($post);
		$post_featured_image = pippin_get_FB_image($post->ID);		
		?>
		<meta name="title" content="<?php echo $post->post_title; ?>" />
		<meta name="description" content="<?php echo $post_description; ?>" />
		<?php if(has_post_thumbnail()) { ?>
			<link rel="image_src" href="<?php echo $post_featured_image; ?>" />
		<?php
		}
	}
}
add_action('wp_head', 'pippin_FB_header');
