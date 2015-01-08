<?php

/**
 * Add video to series single page
 */
function pp_add_series_video() {
        $user_id = get_current_user_id();

        $is_series     = get_post_meta( get_the_ID(), 'series_id', true );
        $premium_video = get_post_meta( get_the_ID(), 'pp_premium', true );
        $video         = get_post_meta( get_the_ID(), 'pp_mp4', true );
        
        /**
         * Premium video
         */
        if ( has_post_format( 'video' ) && ( ( $premium_video && rcp_is_active() || current_user_can( 'edit_posts' ) ) || ! $premium_video  )  ) : ?>
        <div id="post-video">
                <?php
                if( strpos( $video, home_url() ) === false ) {
                    echo wp_oembed_get( $video, array( 'width' => 720 ) ); 
                } else {
                    echo do_shortcode( '[video width="720" src="' . $video . '"]' ); 
                }
                ?>
        </div>
        
        <?php elseif( has_post_format( 'video' ) ) : ?>

        <div id="post-video">
                <img src="<?php echo get_stylesheet_directory_uri() . '/images/restricted-video.png'; ?>" />
        </div>

        <?php endif;   
}
add_action( 'pp_content_single_start', 'pp_add_series_video' );