<?php

/**********************************************
* This file takes care of marking notices
* as read for users.
* Notice IDs are checked against the ID of the
* broadcasted post on CGC, then added to
* the user's meta.
**********************************************/

/////////////////////////////////////////////////
// notice custom post type
/////////////////////////////////////////////////
function pippin_create_notices() {

	$labels = array(
		'name' => _x( 'Notices', 'post type general name' ), // Tip: _x('') is used for localization
		'singular_name' => _x( 'Notice', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'Notice' ),
		'add_new_item' => __( 'Add New Notice' ),
		'edit_item' => __( 'Edit Notice' ),
		'new_item' => __( 'New Notice' ),
		'view_item' => __( 'View Notice' ),
		'search_items' => __( 'Search Notices' ),
		'not_found' =>  __( 'No Notices found' ),
		'not_found_in_trash' => __( 'No Notices found in Trash' ),
		'parent_item_colon' => ''
	);

 	$annoucement_args = array(
     	'labels' =>$labels,
     	'singular_label' => __('Notice'),
     	'public' => true,
     	'show_ui' => true,
	  	'capability_type' => 'post',
     	'hierarchical' => false,
     	'rewrite' => false,
     	'supports' => array('title', 'editor'),
     );
 	register_post_type('notices', $annoucement_args);
}
add_action('init', 'pippin_create_notices');

function pippin_display_announcement() {

	/// this displays the notification area if the user has not read it before
	global $current_user;
	$notices = get_transient('pippin_notices');
	if($notices === false) {
		$notice_args = array('post_type' => 'notices', 'posts_per_page' => 1);
		$notices = get_posts($notice_args);
		set_transient('pippin_notices', $notices, 7200);
	}
	if($notices) :
		foreach ($notices as $notice) { ?>
			<?php if(pippin_check_notice_is_read($notice->ID, $current_user->ID) != true) { ?>
				<div id="notification-area">
					<div class="wrapper box">

						<?php /*
						<h4><?php echo get_the_title( $notice->ID ); ?></h4>
						*/ ?>


						<?php echo wpautop( $notice->post_content ); ?>

						<?php if( is_user_logged_in() ) { ?>
							<a class="remove-notice" href="#" id="remove-notice" rel="<?php echo $notice->ID; ?>">
								<svg width="24px" height="25px">
								   <use xlink:href="<?php echo get_stylesheet_directory_uri() . '/images/svg-defs.svg#icon-remove'; ?>"></use>
								</svg>
							</a>

						<?php } ?>

					</div>
				</div>
			<?php } ?>
		<?php }
	endif;
}

function pp_show_notices() {

	if ( is_front_page() ) {
		add_action( 'pp_site_before', 'pippin_display_announcement', 0 );
	} else {
		add_action( 'pp_content_start', 'pippin_display_announcement', 0 );
	}
}
add_action( 'template_redirect', 'pp_show_notices' );


// function used to clear transients when saving posts
function pippin_clear_caches_on_save($post_id) {
	if(get_post_type($post_id) == 'notices') {
		delete_transient('pippin_notices');
	}
}
add_action('save_post', 'pippin_clear_caches_on_save');


function pippin_check_notice_is_read($post_id, $user_id) {
	// this line was just for testing purposes
	//delete_user_meta(pippin_notice_get_user_id(), 'pippin_notice_posts');
	$user_meta = pippin_notice_get_user_meta($user_id);
	if($user_meta) :
		$read_post_ids = explode(',', $user_meta);
		foreach ($read_post_ids as $read_post) {
            if ($read_post == $post_id) {
				return true;
			}
		}
	endif;
	return false;
}

function pippin_notice_get_user_id() {
    global $current_user;
    get_currentuserinfo();
    return $current_user->ID;
}

function pippin_notice_add_to_usermeta($post_id) {
	$user_id = pippin_notice_get_user_id();
    $cgcn_read = pippin_notice_get_user_meta($user_id);
    $cgcn_read .= ',' . intval($post_id);
	if(substr($cgcn_read, 0, 1) == ',') {
		$cgcn_read = substr($cgcn_read, 1,strlen($cgcn_read)-1);
	}
 	pippin_notice_update_user_meta($cgcn_read, $user_id);
}

function pippin_notice_update_user_meta($arr, $user_id) {
    return update_user_meta($user_id,'pippin_notice_posts',$arr);
}
function pippin_notice_get_user_meta($user_id) {
	return get_user_meta($user_id, 'pippin_notice_posts', true);
}

function pippin_notice_mark_as_read() {
  	if ( isset( $_POST["notice_read"] ) ) {
		$notice_id = intval($_POST["notice_read"]);
    	$marked_as_read = pippin_notice_add_to_usermeta($notice_id);
		die();
	}
}
add_action('wp_ajax_mark_as_read', 'pippin_notice_mark_as_read');

function pippin_notice_js() {
	wp_enqueue_script( "notifications", PIPPIN_PLUGIN_DIR . 'js/notifications.js', array( 'jquery' ) );
	wp_localize_script( 'notifications', 'notices_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )) );
}

add_action('wp_print_scripts', 'pippin_notice_js');
