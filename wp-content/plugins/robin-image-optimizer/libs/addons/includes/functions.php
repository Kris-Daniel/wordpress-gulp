<?php
/**
 * Supporting functions for premium plugin
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>, Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 19.04.2019, Webcraftic
 * @version       1.0
 */

/**
 * Get the path to NextGen galleries on monosites.
 *
 * @since  1.0.4
 * @return string|bool An absolute path. False if it can't be retrieved.
 */
function wrio_get_ngg_galleries_path() {
	$galleries_path = get_site_option( 'ngg_options' );

	if ( empty( $galleries_path['gallerypath'] ) ) {
		return false;
	}

	$galleries_path = wp_normalize_path( $galleries_path['gallerypath'] );
	$galleries_path = trim( $galleries_path, '/' ); // Something like `wp-content/gallery`.

	$ngg_root = defined( 'NGG_GALLERY_ROOT_TYPE' ) ? NGG_GALLERY_ROOT_TYPE : 'site';

	if ( $galleries_path && 'content' === $ngg_root ) {
		$ngg_root = wp_normalize_path( WP_CONTENT_DIR );
		$ngg_root = trim( $ngg_root, '/' ); // Something like `abs-path/to/wp-content`.

		$exploded_root         = explode( '/', $ngg_root );
		$exploded_galleries    = explode( '/', $galleries_path );
		$first_gallery_dirname = reset( $exploded_galleries );
		$last_root_dirname     = end( $exploded_root );

		if ( $last_root_dirname === $first_gallery_dirname ) {
			array_shift( $exploded_galleries );
			$galleries_path = implode( '/', $exploded_galleries );
		}
	}

	if ( 'content' === $ngg_root ) {
		$ngg_root = wp_normalize_path( WP_CONTENT_DIR );
	} else {
		$ngg_root = wp_normalize_path( ABSPATH );
	}

	if ( strpos( $galleries_path, $ngg_root ) !== 0 ) {
		$galleries_path = $ngg_root . $galleries_path;
	}

	return $galleries_path;
}

/**
 * Get the path to WooCommerce logs on monosites.
 *
 * @since  1.0.4
 * @access public
 * @return string An absolute path.
 */

function wrio_get_wc_logs_path() {
	if ( defined( 'WC_LOG_DIR' ) ) {
		return WC_LOG_DIR;
	}

	$wp_upload_dir = wp_upload_dir();

	if ( isset( $wp_upload_dir['error'] ) && $wp_upload_dir['error'] !== false ) {
		return null;
	}

	$wp_upload_dir_path = wp_normalize_path( trailingslashit( $wp_upload_dir['basedir'] ) );

	return $wp_upload_dir_path . 'wc-logs';
}

/**
 * Get the path to EWWW optimization tools.
 * It is the same for all sites on multisite.
 *
 * @since  1.0.4
 * @return string An absolute path.
 */
function wrio_get_ewww_tools_path() {
	if ( defined( 'EWWW_IMAGE_OPTIMIZER_TOOL_PATH' ) ) {
		return wp_normalize_path( EWWW_IMAGE_OPTIMIZER_TOOL_PATH );
	}

	return trailingslashit( wp_normalize_path( WP_CONTENT_DIR ) ) . 'ewww';
}

/**
 * Get the path to ShortPixel backup folder.
 * It is the same for all sites on multisite (and yes, you'll get a surprise if your upload base dir -aka uploads/sites/12/- is not 2 folders deeper than theuploads folder).
 *
 * @since  1.0.4
 * @access public
 * @return string An absolute path.
 */

function wrio_get_shortpixel_path() {
	if ( defined( 'SHORTPIXEL_BACKUP_FOLDER' ) ) {
		return trailingslashit( SHORTPIXEL_BACKUP_FOLDER );
	}

	$wp_upload_dir = wp_upload_dir();

	if ( isset( $wp_upload_dir['error'] ) && $wp_upload_dir['error'] !== false ) {
		return null;
	}

	$wp_upload_dir_path = wp_normalize_path( trailingslashit( $wp_upload_dir['basedir'] ) );

	return $wp_upload_dir_path . 'ShortpixelBackups';
}
