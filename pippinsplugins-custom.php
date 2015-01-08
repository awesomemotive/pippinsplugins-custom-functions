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
include(dirname(__FILE__) . '/includes/notices.php');
include(dirname(__FILE__) . '/includes/series.php');
include(dirname(__FILE__) . '/includes/facebook-previews.php');
include(dirname(__FILE__) . '/includes/edd-custom.php');
include(dirname(__FILE__) . '/includes/metaboxes.php');
include(dirname(__FILE__) . '/includes/show-plugin-info.php');
include(dirname(__FILE__) . '/includes/post-series.php');





add_filter( 'gform_enable_shortcode_notification_message', '__return_false' );
add_filter( 'edd_api_log_requests', '__return_false' );

function pw_edd_searchwp_indexed_types( $types ) {

	return array( 'post', 'page' );

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



function pw_analytics() {
?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-23172567-1', 'pippinsplugins.com');
  ga('send', 'pageview');

</script>
<?php
}
add_action( 'wp_footer', 'pw_analytics' );


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
