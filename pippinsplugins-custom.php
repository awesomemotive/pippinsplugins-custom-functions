<?php

/*
Plugin Name: PippinsPlugins.com Custom
Plugin URI: http://pippinsplugins.com/
Description: Custom functionality plugin for pippinsplugins.com
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Version: 1.0
*/


if ( !defined('PIPPIN_PLUGIN_DIR')) {
	define('PIPPIN_PLUGIN_DIR', plugin_dir_url( __FILE__ ));
}


include(dirname(__FILE__) . '/includes/actions.php');
include(dirname(__FILE__) . '/includes/series.php');
include(dirname(__FILE__) . '/includes/edd-custom.php');
include(dirname(__FILE__) . '/includes/metaboxes.php');
include(dirname(__FILE__) . '/includes/show-plugin-info.php');
include(dirname(__FILE__) . '/includes/post-series.php');

// Handles card update processing for Stripe and Dunning from Baremetrics
function shd_maybe_redirect() {

	if( ! is_page( 'update' ) ) {
		return;
	}

	if( ! class_exists( '\Stripe\Stripe' ) ) {
		return;
	}

	if( empty( $_GET['customer_id'] ) || empty( $_GET['email'] ) ) {
		wp_redirect( home_url() ); exit; // Get out of here if loading page from anywhere but Baremetrics email
	}

	$email       = sanitize_text_field( $_GET['email'] );
	$customer_id = sanitize_text_field( $_GET['customer_id'] );

	/*
	 * Check if this is an RCP customer
	 */

	\Stripe\Stripe::setApiKey( RCP_STRIPE_KEY );

	try {

		$customer = \Stripe\Customer::retrieve( $customer_id );

		if( ! empty( $customer->subscriptions->data ) ) {

			foreach( $customer->subscriptions->data as $sub ) {
				if( false !== strpos( $sub->plan->name, 'restrict-content-pro' ) ) {
					wp_redirect( 'https://restrictcontentpro.com/account/?sh_action=update#tabs=1' ); exit;
				}
			}

		}

		wp_redirect( 'https://pippinsplugins.com/account/update-billing-card/?sh_action=update' ); exit;


	} catch ( Exception $e ) {


		/*
		 * Not and RCP customer, check if it's an AffiliateWP customer
		 */

		\Stripe\Stripe::setApiKey( AFFWP_STRIPE_KEY );

		try {

			$customer = \Stripe\Customer::retrieve( $customer_id );
			wp_redirect( 'https://affiliatewp.com/account/?sh_action=update#tabs=1' ); exit;

		} catch ( Exception $e ) {

			/*
			 * Not and RCP or AffWP customer, check if it's an EDD customer
			 */

			\Stripe\Stripe::setApiKey( EDD_STRIPE_KEY );

			try {

				$customer = \Stripe\Customer::retrieve( $customer_id );
				wp_redirect( 'https://easydigitaldownloads.com/your-account/?sh_action=update#tab-subscriptions' ); exit;

			} catch ( Exception $e ) {

				$message = 'Customer ' . $customer_id . ' was not found. Email address was ' . $email;

				wp_mail( 'pippin@pippinsplugins.com', 'Dunning Email Failed for ' . $email, $message );

				// Still no customer found, bail
				wp_redirect( home_url() ); exit;

			}

		}

	}

}
add_action( 'template_redirect', 'shd_maybe_redirect' );

add_filter( 'gform_enable_shortcode_notification_message', '__return_false' );
function pw_edd_disable_api_logging() {
	add_filter( 'edd_api_log_requests', '__return_false' );
}
add_action( 'plugins_loaded', 'pw_edd_disable_api_logging' );

function pw_edd_searchwp_indexed_types( $types ) {

	return array( 'post', 'page', 'download' );

}
add_filter( 'searchwp_indexed_post_types', 'pw_edd_searchwp_indexed_types' );

function yst_ssl_template_redirect() {
/*
	if ( is_page( 'join-the-site' ) && ! is_ssl() ) {
		if ( 0 === strpos($_SERVER['REQUEST_URI'], 'http') ) {
			wp_redirect(preg_replace('|^https://|', 'http://', $_SERVER['REQUEST_URI']), 301 );
			exit();
		} else {
			wp_redirect('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
			exit();
		}
	}
*/
}
//add_action( 'template_redirect', 'yst_ssl_template_redirect', -999 );

function yst_checkout_page_ssl( $permalink, $post, $leavename ) {
	if ( 4175 == $post->ID || 672 == $post->ID || 1643 == $post->ID )
		return preg_replace( '|^http://|', 'https://', $permalink );
	return $permalink;
}
add_filter( 'pre_post_link', 'yst_checkout_page_ssl', 10, 3 );

/**
 * @todo  fix: creates whole bunch of notices on site
 */
function pippin_add_cpts_to_archives($query) {
	if ( !is_preview() && !is_admin() && !is_singular() && !is_404() ) {

		$post_types = array('series');
		$post_types = array_merge( $post_types , array( 'post' ) );

		if ( empty( $query->is_feed ) && ! empty( $query->is_main_query ) ) {
			$my_post_type = get_query_var( 'post_type' );
			if ( empty( $my_post_type ) ) {
			  $query->set( 'post_type' , $post_types );
			}
		}
    }
    return $query;
}
//add_action('pre_get_posts', 'pippin_add_cpts_to_archives');


function pippin_query_posts_shortcode($atts, $content = null ) {

	extract( shortcode_atts( array(
      'category' => 'true',
	  'number' => 5
      ), $atts )
	);

	$post_args = array('cat' => $category, 'numberposts' => $number);
	$posts = get_posts($post_args);
	if($posts) {
		$display = '<ul>';
		foreach ($posts as $p) {
			$display .= '<li><a href="' . get_permalink($p->ID) . '">' . get_the_title($p->ID) . '</a></li>';
		}
		$display .= '</ul>';
	}
	return $display;
}
add_shortcode('query_posts', 'pippin_query_posts_shortcode');



function pippin_random_add_rewrite() {
	global $wp;
	$wp->add_query_var('random');
	add_rewrite_rule('random/?$', 'index.php?random=1', 'top');
}
add_action('init','pippin_random_add_rewrite');

function pippin_random_template() {
    if (get_query_var('random') == 1) {
		$posts = get_posts('post_type=post&orderby=rand&numberposts=1');
		foreach($posts as $post) {
			   $link = get_permalink($post);
		}
		wp_redirect($link,307);
		exit;
    }
}
add_action('template_redirect','pippin_random_template', 999);



function pw_connection_types() {
    // Make sure the Posts 2 Posts plugin is active.
    if ( ! function_exists( 'p2p_register_connection_type' ) )
        return;

    p2p_register_connection_type( array(
        'name' => 'posts_to_posts',
        'from' => 'post',
        'to' => 'post',
        'reciprocal' => true,
        'sortable' => 'any'
    ) );

    p2p_register_connection_type( array(
        'name' => 'extensions',
        'from' => 'post',
        'to' => 'post',
        'reciprocal' => true,
        'sortable' => 'any',
        'title' => 'Extensions'
    ) );

    // add-ons
    p2p_register_connection_type(
    	array(
	        'name'       => 'downloads_to_add-ons',
	        'from'       => 'download',
	        'to'         => array( 'download', 'post' ),
	        'reciprocal' => true,
	        'sortable'   => 'any',
	        'title'      => 'Add-ons'
    	)
    );

    // related blog psots
    p2p_register_connection_type(
      array(
          'name'       => 'downloads_to_posts',
          'from'       => 'download',
          'to'         => 'post',
          'reciprocal' => true,
          'sortable'   => 'any',
          'title'      => 'Related Posts
          '
      )
    );

    // documentation
    p2p_register_connection_type(
    	array(
	        'name'       => 'docs_to_downloads',
	        'from'       => 'documentation',
	        'to'         => 'download',
	        'reciprocal' => true,
	        'sortable'   => 'any',
	        'title'      => 'Related Download'
    	)
    );

}
add_action( 'wp_loaded', 'pw_connection_types' );


function pw_extensions_shortcode( $atts, $content = null ) {
  // plugin extensions
  $connected = new WP_Query( array(
    'connected_type' => 'extensions',
    'connected_items' => get_the_ID(),
    'nopaging' => true,
    'suppress_filters' => true,
    'posts_per_page' => -1
  ) );
  $html = '';
  // get the connected ID
  if ( $connected->have_posts() ) :
    $html .= '<div id="plugin-extensions" class="clearfix">';
    $html .= '<h3>Extensions for this Plugin</h3>';
    while ( $connected->have_posts() ) : $connected->the_post();
      $html .= '<div class="plugin-extension">';
        $html .= '<div><a href="' . get_permalink( get_the_ID() ) . '">' . get_the_post_thumbnail( get_the_ID(), 'grid-image' ) . '</a></div>';
        $html .= '<strong><a href="' . get_permalink( get_the_ID() ) . '">' . get_the_title( get_the_ID() ) . '</a></strong>';
      $html .= '</div>';
    endwhile;
    $html .= '</div>';
    wp_reset_postdata();
  endif;
  return $html;
}
add_shortcode( 'extensions', 'pw_extensions_shortcode' );


function pw_flush() {
  if( isset( $_GET['flush'] ) && isset( $_GET['pw11'] ) )
    flush_rewrite_rules(false);
}
add_action( 'init', 'pw_flush' );

function pw_allowed_mime_types( $existing_mimes ) {
  $existing_mimes['mp4']  = 'video/mp4';
  $existing_mimes['ogg']  = 'video/ogg';
  $existing_mimes['ogv']  = 'video/ogv';

  return $existing_mimes;
}
add_filter( 'upload_mimes', 'pw_allowed_mime_types' );

// Disable heartbeat in dashboard
remove_action( 'plugins_loaded', array( 'EDD_Heartbeat', 'init' ) );


// We need to register the EDD post types now b/c they
// are registered on the init hook in EDD core.
// The EDD post types needs to be registered now
// order for get_permalink() to return the correct URL/s.
function pw_edd_sl_remote_actions_short_init() {

	$remote_actions = array( 'activate_license', 'deactivate_license', 'check_license', 'get_version' );

	if ( isset( $_REQUEST['edd_action'] ) && in_array( $_REQUEST['edd_action'], $remote_actions ) ) {

		edd_setup_edd_post_types();

		do_action( 'edd_' . $_REQUEST['edd_action'], $_REQUEST );
	}

}
add_action( 'setup_theme', 'pw_edd_sl_remote_actions_short_init', -9999 );

function pw_button_shortcode( $atts, $content = null ) {

	$atts = shortcode_atts( array( 'link' => '' ), $atts );

	return '<a href="' . esc_url( $atts['link'] ) . '" class="edd-submit button blue">' . $content . '</a>';
}
add_shortcode( 'button', 'pw_button_shortcode' );


/**
 * Add rss image
 */
function pw_rss_featured_image() {
    global $post;

    if ( has_post_thumbnail( $post->ID ) ) {
      $thumbnail = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
      $mime_type = get_post_mime_type( get_post_thumbnail_id( $post->ID ) );
      ?>
      <media:content url="<?php echo $thumbnail; ?>" type="<?php echo $mime_type; ?>" medium="image" width="600" height="300"></media:content>
    <?php }
}
add_filter( 'rss2_item', 'pw_rss_featured_image' );

/**
 * Add rss namespaces
 */
function pp_rss_namespace() {
    echo 'xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:georss="http://www.georss.org/georss"';
}
add_filter( 'rss2_ns', 'pp_rss_namespace' );

/**
 * Removes styling from Better Click To Tweet plugin
 */
function affwp_remove_bctt_styling() {
  remove_action('wp_enqueue_scripts', 'bctt_scripts');
}
add_action( 'template_redirect', 'affwp_remove_bctt_styling' );

/**
 * Removes styling from EDD Software licensing
 */
remove_action( 'wp_enqueue_scripts', 'edd_sl_scripts' );


/**
 * Detect if the user came from the restrict content pro site
 */
function pp_came_from_rcp() {

	if ( isset( $_GET['referrer'] ) && 'rcp_site' === $_GET['referrer'] ) {
		return true;
	}

	return false;
}

/**
 * Set session variable in case they move around the site
 */
function pp_rcp_referrer_listener() {

	if ( pp_came_from_rcp() ) {
		// store variable into session
		EDD()->session->set( 'came_from_rcp', true );
	}
}
add_action( 'template_redirect', 'pp_rcp_referrer_listener' );

/**
 * Store meta key with payment for future use
 * Will eventually be used to track how many purchases come from the RCP site
 */
function pp_rcp_store_payment_meta( $payment, $payment_data ) {

	// store meta
	if ( EDD()->session->get( 'came_from_rcp' ) ) {
		update_post_meta( $payment, '_edd_payment_from_rcp', true );
	}

}
add_action( 'edd_insert_payment', 'pp_rcp_store_payment_meta', 10, 2 );

// Redirect Easy Content Types get_version requests to Theme Isle
function pw_redirect_ecpt_version_check() {

	if( empty( $_REQUEST['edd_action'] ) ) {
		return;
	}

	if( 'get_version' !== $_REQUEST['edd_action'] ) {
		return;
	}

	if( empty( $_REQUEST['item_name'] ) ) {
		return;
	}

	if( 'Easy Content Types' !== urldecode( $_REQUEST['item_name'] ) ) {
		return;
	}

	$license = ! empty( $_REQUEST['license'] ) ? $_REQUEST['license'] : '';

	wp_redirect( 'http://themeisle.com/?edd_action=get_version&item_name=Easy+Content+Types&license=' . $license ); exit;

}
add_action( 'setup_theme', 'pw_redirect_ecpt_version_check', -999999 );

// Filter out private licnese keys (RCP Migration)
function pp_filter_license_keys( $license_keys, $user_id ) {

	foreach ( $license_keys as $index => $license_key ) {
		if ( 'private' === $license_key->post_status ) {
			unset( $license_keys[ $index ] );
		}
	}

	return $license_keys;

}
add_filter( 'edd_sl_get_License_keys_of_user', 'pp_filter_license_keys', 10, 2 );


// Redirect Restrict Content Pro get_version requests to restrictcontentpro.com
function pw_redirect_rcp_version_check() {

	if( empty( $_REQUEST['edd_action'] ) ) {
		return;
	}

	$actions = array(
		'get_version',
		'check_license',
		'package_download',
		'activate_license',
		'deactivate_license',
	);

	if( ! in_array( $_REQUEST['edd_action'], $actions ) ) {
		return;
	}

	if( empty( $_REQUEST['item_name'] ) && empty( $_REQUEST['item_id'] ) ) {
		return;
	}

	$item_id   = isset( $_REQUEST['item_id'] )   ? (int) $_REQUEST['item_id']          : 0;
	$item_name = isset( $_REQUEST['item_name'] ) ? urldecode( $_REQUEST['item_name'] ) : '';

	if( 'Restrict Content Pro' !== $item_name && 7460 !== $item_id ) {
		return;
	}

	$license = ! empty( $_REQUEST['license'] ) ? $_REQUEST['license'] : '';
	$url     = ! empty( $_REQUEST['url    '] ) ? $_REQUEST['url    '] : '';

	wp_redirect( 'https://restrictcontentpro.com/?edd_action=' . $_REQUEST['edd_action'] . '&item_id=479&license=' . $license . '&url=' . $url ); exit;

}
add_action( 'setup_theme', 'pw_redirect_rcp_version_check', -999999 );

function pw_listen_for_rcp_renewal_checkout() {

	if( ! function_exists( 'edd_is_checkout' ) || ! edd_is_checkout() ) {
		return;
	}

	if( empty( $_GET['edd_license_key'] ) ) {
		return;
	}

	$license_key = sanitize_text_field( $_GET['edd_license_key'] );
	$license_id  = edd_software_licensing()->get_license_by_key( $license_key );
	$download_id = edd_software_licensing()->get_download_id( $license_id );
	if( 7460 == $download_id ) {
		wp_redirect( 'https://restrictcontentpro.com/checkout/?edd_license_key=' . $license_key ); exit;
	}

}
add_action( 'template_redirect', 'pw_listen_for_rcp_renewal_checkout' );

/**
 * Auto apply BFCM discount
 */
function pp_edd_auto_apply_discount() {

	if ( function_exists( 'edd_is_checkout' ) && edd_is_checkout() ) {

		if ( ! edd_cart_has_discounts() && edd_is_discount_valid( 'BFCM2016', '', false ) ) {
			edd_set_cart_discount( 'BFCM2016' );
		}

	}

}
//add_action( 'template_redirect', 'pp_edd_auto_apply_discount' );
