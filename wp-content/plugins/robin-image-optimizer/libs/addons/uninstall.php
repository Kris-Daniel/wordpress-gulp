<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// remove plugin options
global $wpdb;

// Main plugin file, to have files included file deleting WebP folder
/*require_once( dirname( __FILE__ ) . '/robin-image-optimizer.php' );

if ( class_exists( 'WRIO_WebP_Api' ) ) {
	// Unlink WebP dir
	$path = WRIO_WebP_Api::get_base_dir_path();
	
	if ( file_exists( $path ) ) {
		@unlink( $path );
	}
}*/