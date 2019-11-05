<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// remove plugin options
global $wpdb;

if ( is_multisite() ) {
	$wpdb->query( "DELETE FROM {$wpdb->sitemeta}options WHERE option_name LIKE 'wbcr_io_%';" );

	$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	if ( ! empty( $blogs ) ) {
		foreach ( $blogs as $id ) {

			switch_to_blog( $id );

			$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'wio_%';" );
			$io_db_table = $wpdb->prefix . 'rio_process_queue';
			$wpdb->query( "DROP TABLE IF EXISTS {$io_db_table};" );
			restore_current_blog();
		}
	}
} else {
	$io_db_table = $wpdb->prefix . 'rio_process_queue';
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wbcr_io_%';" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'wio_%';" );
	$wpdb->query( "DROP TABLE IF EXISTS {$io_db_table};" );
}

// REMOVE Backup dir
// --------------------------------------------------------------------------
require_once( dirname( __FILE__ ) . '/includes/functions.php' );

$wp_upload_dir = wp_upload_dir();

if ( isset( $wp_upload_dir['error'] ) && $wp_upload_dir['error'] !== false ) {
	return;
}

$wp_upload_dir_path = wp_normalize_path( trailingslashit( $wp_upload_dir['basedir'] ) );

// REMOVE BACKUP DIR
// --------------------------------------------------------------------------
$backup_dir = $wp_upload_dir_path . 'wio_backup';

if ( file_exists( $backup_dir ) ) {
	wrio_rmdir( $backup_dir );
}

// --------------------------------------------------------------------------

// REMOVE WebP DIR
// This directory is left over from old plugin version. From version 1.3.6,
// webp images are saved to same directory in which the image was stored,
// from which a copy was made in webp format.
// --------------------------------------------------------------------------
$webp_dir_path = $wp_upload_dir_path . 'wrio-webp-uploads';

if ( file_exists( $webp_dir_path ) ) {
	wrio_rmdir( $webp_dir_path );

	return true;
}

// REMOVE LOG DIR
// --------------------------------------------------------------------------
$log_dir_path = $wp_upload_dir_path . 'wrio';

if ( file_exists( $log_dir_path ) ) {
	wrio_rmdir( $log_dir_path );
}
// --------------------------------------------------------------------------

// REMOVE OLD LOG FILE
// This file was used up to version 1.3.3. Another error logging structure is being used.
$old_log_file_path = $wp_upload_dir_path . 'wio.log';

if ( file_exists( $old_log_file_path ) ) {
	@unlink( $old_log_file_path );
}
// --------------------------------------------------------------------------