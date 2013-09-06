<?php
// global styles for the meta boxes
if (is_admin()) add_action('admin_enqueue_scripts', 'picolev_metabox_style');

function picolev_metabox_style() {
	wp_enqueue_style('wpalchemy-metabox', get_stylesheet_directory_uri() . '/metaboxes/meta.css');
}

/* eof */