<?php

function pippin_remove_actions() {
	remove_action('wp_head', 'wp_generator');
}
add_action('init', 'pippin_remove_actions', 999);