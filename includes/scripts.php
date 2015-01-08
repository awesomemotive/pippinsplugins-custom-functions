<?php

function pw_pp_custom_header_script() {
	echo '<script type="text/javascript" src="//cdn.sublimevideo.net/js/x2ywb1gi.js"></script>';
}
add_action( 'wp_head', 'pw_pp_custom_header_script' );