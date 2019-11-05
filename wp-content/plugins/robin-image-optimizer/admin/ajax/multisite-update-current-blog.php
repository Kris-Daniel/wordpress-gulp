<?php
/**
 * Ajax действие, которое выполняется для смены текущего multisite блога
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 2018 Webraftic Ltd
 * @version 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*add_action( 'wp_ajax_wbcr_rio_update_current_blog', function () {
	check_ajax_referer( 'update_blog_id', 'wpnonce' );
	$blog_id = (int) WRIO_Plugin::app()->request->post( 'current_blog_id' );
	$context = sanitize_text_field( WRIO_Plugin::app()->request->post( 'context' ) );
	WRIO_Plugin::app()->updatePopulateOption( 'current_blog', $blog_id );
	$image_statistics = WIO_OptimizationTools::getImageStatistics( $context );

	switch_to_blog( $blog_id );
	$statistic_data = $image_statistics->load();
	restore_current_blog();

	wp_send_json_success( array(
		'statistic' => $statistic_data,
	) );
} );*/
