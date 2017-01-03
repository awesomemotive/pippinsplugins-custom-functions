<?php
/**
 * Metaboxes
 * @since 1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add-on meta Box
 *
 * @since 1.0
 */
function pp_plugin_meta_box() {
	add_meta_box( 'pp_plugin_meta_box', esc_html__( 'Product Information', 'pp' ), 'pp_product_meta_box', 'download', 'side' );
	add_meta_box( 'pp_video_meta_box', esc_html__( 'Video', 'pp' ), 'pp_video_meta_box', 'post', 'side' );
}
add_action( 'add_meta_boxes', 'pp_plugin_meta_box' );



/**
 * Redirect if addon is accessed while coming soon
 * Admins can see addon just fine
 * @since 1.1.9
 */
function pp_custom_redirect_coming_soon() {
	global $post;

	if ( ! is_singular( 'download' ) || current_user_can( 'manage_options' ) )
		return;

	$do_redirect = pp_custom_product_is_coming_soon( get_the_ID() );

	if ( $do_redirect ) {
		$redirect_url = site_url( 'products' );

		if ( isset( $_REQUEST['HTTP_REFERER'] ) ) {
			$referer = esc_url( $_REQUEST['HTTP_REFERER '] );

			if ( strpos( $referer, $redirect_url ) !== false ) {
				$redirect_url = $referer;
			}
		}

		wp_redirect( $redirect_url, 301 ); exit;
	}

}
add_action( 'template_redirect', 'pp_custom_redirect_coming_soon' );

/**
 * Determine if the download is coming soon or not
 * @param  $download_id ID of download to check
 * @return boolean true if addon is coming soon, false otherwise
 * @since  1.0
 */
function pp_custom_product_is_coming_soon( $download_id ) {
	$coming_soon = get_post_meta( $download_id, '_pp_product_coming_soon', true );

	if ( $coming_soon ) {
		return (bool) true;
	}

	return (bool) false;
}

/**
 * Video Metabox callback
 * https://vimeo.com/78265050
*/
function pp_video_meta_box( $post ) {

	$is_video_premium = get_post_meta( $post->ID, 'pp_premium', true );
	$video_url        = get_post_meta( $post->ID, 'pp_mp4', true );
	?>
	<p>
		<label for="pp-premium-video">
			<input type="checkbox" name="pp_premium" id="pp-premium-video" value="1" <?php checked( true, $is_video_premium ); ?> />
			<?php _e( 'Premium Video', 'pp' ); ?>
		</label>
	</p>

	<p><strong><?php _e( 'Video URL', 'pp' ); ?></strong></p>
	<p>
		<label for="pp-video-url" class="screen-reader-text">
			<?php _e( 'Video URL', 'pp' ); ?>
		</label>
		<input class="widefat" type="text" name="pp_mp4" id="pp-video-url" value="<?php echo esc_url( $video_url ); ?>" size="30" />
	</p>


	<?php wp_nonce_field( 'pp_video_metaboxes', 'pp_video_metaboxes' ); ?>

<?php }


/**
 * Product Metabox callback
 * @since 1.0
*/
function pp_product_meta_box( $post ) {
	?>

	<p>
		<label for="pp-product-coming-soon">
			<input type="checkbox" name="pp_product_coming_soon" id="pp-product-coming-soon" value="1" <?php checked( true, pp_custom_product_is_coming_soon( $post->ID ) ); ?> />
			<?php _e( 'Product is coming soon', 'pp' ); ?>
		</label>
	</p>


	<p><strong><?php _e( 'External Download URL', 'pp' ); ?></strong></p>
	<p>
		<label for="pp-product-download-url" class="screen-reader-text">
			<?php _e( 'External Download URL', 'pp' ); ?>
		</label>
		<input class="widefat" type="text" name="pp_product_download_url" id="pp-product-download-url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_pp_product_download_url', true ) ); ?>" size="30" />
	</p>

	<p><strong><?php _e( 'External Support URL', 'pp' ); ?></strong></p>
	<p>
		<label for="pp-product-support-url" class="screen-reader-text">
			<?php _e( 'External Support URL', 'pp' ); ?>
		</label>
		<input class="widefat" type="text" name="pp_product_support_url" id="pp-product-support-url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_pp_product_support_url', true ) ); ?>" size="30" />
	</p>

	<p><strong><?php _e( 'External Documentation URL', 'pp' ); ?></strong></p>
	<p>
		<label for="pp-product-doc-url" class="screen-reader-text">
			<?php _e( 'External Documentation URL', 'pp' ); ?>
		</label>
		<input class="widefat" type="text" name="pp_product_doc_url" id="pp-product-doc-url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_pp_product_doc_url', true ) ); ?>" size="30" />
	</p>



	<?php
		$args = array(
			'hide_empty' => false,
			'parent' => 0
		);
		$terms = get_terms( 'doc_category', $args );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>

		<p><strong><?php _e( 'Documentation Category', 'pp' ); ?></strong></p>

	<p>
		<label for="pp-product-support-url" class="screen-reader-text">
			<?php _e( 'Documentation Category', 'pp' ); ?>
		</label>
		<?php
			$args = array(
				'hide_empty' => false,
				'parent' => 0
			);
			$terms = get_terms( 'doc_category', $args );

			$selected = (int) get_post_meta( $post->ID, '_pp_product_doc_term_id', true );

		?>
		<select class="widefat" name="pp_product_doc_term_id">
			<option value="0">Choose doc category</option>
			<?php foreach ( $terms as $term ) { ?>
				<option value="<?php echo $term->term_id; ?>" <?php selected( $selected, $term->term_id ); ?>><?php echo $term->name; ?></option>
			<?php } ?>
		</select>
	</p>

	<?php endif; ?>

	<?php wp_nonce_field( 'pp_product_metaboxes', 'pp_product_metaboxes' ); ?>

<?php }


/**
 * Save function
 *
 * @since 1.1.9
*/
function pp_product_save_post( $post_id ) {

	if ( ( isset( $_POST['post_type'] ) && 'download' == $_POST['post_type'] )  ) {
		if ( ! current_user_can( 'edit_page', $post_id ) )
	    	return;
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) )
	    	return;
	}

	if ( ! isset( $_POST['pp_product_metaboxes'] ) || ! wp_verify_nonce( $_POST['pp_product_metaboxes'], 'pp_product_metaboxes' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return;
	}

	if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}

	$fields = apply_filters( 'pp_product_metabox_fields_save', array(
			'pp_product_coming_soon',
			'pp_product_download_url',
			'pp_product_support_url',
			'pp_product_doc_url',
			'pp_product_release_date',
			'pp_product_requires',
			'pp_product_doc_term_id'
		)
	);

	$edd_sl_version = isset( $_POST['edd_sl_version'] ) ? $_POST['edd_sl_version'] : '';

	// software licensing version number
	if ( isset( $edd_sl_version ) ) {
		$current = get_post_meta( $post_id, '_edd_sl_version', true );

		if ( $edd_sl_version !== $current ) {
			update_post_meta( $post_id, '_pp_product_last_updated', current_time( 'timestamp' ) );
		}
	}

	foreach ( $fields as $field ) {

		$new = ( isset( $_POST[ $field ] ) ? esc_attr( $_POST[ $field ] ) : '' );

		// http
		if ( $field == 'pp_product_download_url' || $field == 'pp_product_support_url' || $field == 'pp_product_doc_url' ) {
			$new = esc_url_raw( $_POST[ $field ] );
		}

		// documentation term ID
		if ( $field == 'pp_product_doc_term_id' && ! empty( $_POST['pp_product_doc_term_id'] ) ) {
			$new = $_POST[ $field ] ? $_POST[ $field ] : '';
		}

		$new = apply_filters( 'pp_product_save_' . $field, $new );

		$meta_key = '_' . $field;

		// Get the meta value of the custom field key.
		$meta_value = get_post_meta( $post_id, $meta_key, true );

		// If a new meta value was added and there was no previous value, add it.
		if ( $new && '' == $meta_value ) {
			add_post_meta( $post_id, $meta_key, $new, true );
		}

		// If the new meta value does not match the old value, update it.
		elseif ( $new && $new != $meta_value ) {
			update_post_meta( $post_id, $meta_key, $new );
		}

		// If there is no new meta value but an old value exists, delete it.
		elseif ( '' == $new && $meta_value ) {
			delete_post_meta( $post_id, $meta_key, $meta_value );
		}

	}
}
add_action( 'save_post', 'pp_product_save_post', 1 );

/**
 * Saves a post
 * @todo merge with pp_product_save_post
*/
function pp_save_post( $post_id ) {

	if ( ( isset( $_POST['post_type'] ) && 'post' == $_POST['post_type'] )  ) {
		if ( ! current_user_can( 'edit_page', $post_id ) )
	    	return;
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) )
	    	return;
	}

	if ( ! isset( $_POST['pp_video_metaboxes'] ) || ! wp_verify_nonce( $_POST['pp_video_metaboxes'], 'pp_video_metaboxes' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return;
	}

	if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = apply_filters( 'pp_post_metabox_fields_save', array(
			'pp_mp4',
			'pp_premium'
		)
	);

	foreach ( $fields as $field ) {

		$new = ( isset( $_POST[ $field ] ) ? esc_attr( $_POST[ $field ] ) : '' );

		// http
		if ( $field == 'pp_mp4' ) {
			$new = esc_url_raw( $_POST[ $field ] );
		}

		$new = apply_filters( 'pp_post_save_' . $field, $new );

		$meta_key = $field;

		// Get the meta value of the custom field key.
		$meta_value = get_post_meta( $post_id, $meta_key, true );

		// If a new meta value was added and there was no previous value, add it.
		if ( $new && '' == $meta_value ) {
			add_post_meta( $post_id, $meta_key, $new, true );
		}

		// If the new meta value does not match the old value, update it.
		elseif ( $new && $new != $meta_value ) {
			update_post_meta( $post_id, $meta_key, $new );
		}

		// If there is no new meta value but an old value exists, delete it.
		elseif ( '' == $new && $meta_value ) {
			delete_post_meta( $post_id, $meta_key, $meta_value );
		}

	}
}
add_action( 'save_post', 'pp_save_post', 1 );
