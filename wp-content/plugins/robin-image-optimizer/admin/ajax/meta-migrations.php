<?php
/**
 * Ajax action to migrate old architecture based on post meta into new table.
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @author        Alexander Teshabaev <sasha.tesh@gmail.com>
 * @see           RIO_Process_Queue for further information.
 *
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_wrio_meta_migrations', 'wbcr_rio_migrate_postmeta_to_process_queue' );

/**
 * Migrating postmeta to newly created table.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @since  1.3.0
 * @see    RIO_Process_Queue as referce for new table.
 */
function wbcr_rio_migrate_postmeta_to_process_queue() {
	global $wpdb;

	check_admin_referer( 'wrio-meta-migrations' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$error = (int) WRIO_Plugin::app()->request->post( 'error', 0 );

	if ( $error ) {
		WRIO_Logger::error( 'Previous migration was not completed due to an error.' );
	}

	$limit = (int) WRIO_Plugin::app()->request->post( 'limit', 150 );

	$processed_items = 0;

	WRIO_Logger::info( 'Start meta migration. Limit ' . $limit );
	WRIO_Logger::memory_usage();

	$attachments = wbcr_rio_get_meta_to_migrate();

	if ( isset( $attachments->posts ) && ( $attachments_total = count( $attachments->posts ) ) > 0 ) {

		if ( $attachments_total < $limit ) {
			$limit = $attachments_total;
		}

		WRIO_Logger::info( 'Finded ' . $attachments_total . ' attachments for migration.' );

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'image' );
		}

		WRIO_Logger::memory_usage();

		/**
		 * @var WP_Post $attachment
		 */
		for ( $i = 0; $i < $limit; $i ++ ) {
			$attachment = $attachments->posts[ $i ];
			$post_meta  = get_post_custom( $attachment->ID );

			$extra_data = new RIO_Attachment_Extra_Data();

			$is_backed_up  = false;
			$original_size = 0;
			$final_size    = 0;

			if ( isset( $post_meta['wio_backuped'][0] ) && $post_meta['wio_backuped'][0] ) {
				$is_backed_up = true;
			}

			if ( isset( $post_meta['wio_thumbnails_count'][0] ) ) {
				$extra_data->set_thumbnails_count( intval( $post_meta['wio_thumbnails_count'][0] ) );
			}

			if ( isset( $post_meta['wio_original_size'][0] ) ) {
				$original_size = intval( $post_meta['wio_original_size'][0] );
			}

			if ( isset( $post_meta['wio_optimized_size'][0] ) ) {
				$final_size = intval( $post_meta['wio_optimized_size'][0] );
			}

			if ( isset( $post_meta['wio_original_main_size'][0] ) ) {
				$extra_data->set_original_main_size( intval( $post_meta['wio_original_main_size'][0] ) );
			}

			if ( isset( $post_meta['wio_error'][0] ) ) {
				$extra_data->set_error( 'optimization' );
				$extra_data->set_error_msg( $post_meta['wio_error'][0] );
			}

			$level = 'normal';

			if ( isset( $post_meta['wio_optimization_level'][0] ) && ! empty( $post_meta['wio_optimization_level'][0] ) ) {
				$level = $post_meta['wio_optimization_level'][0];
			}

			$data = [
				'server_id'          => null,
				'object_id'          => $attachment->ID,
				'object_name'        => $wpdb->posts,
				'item_type'          => 'attachment',
				'result_status'      => ! $final_size ? 'error' : 'success',
				'processing_level'   => $level,
				'is_backed_up'       => $is_backed_up,
				'original_size'      => $original_size,
				'final_size'         => $final_size,
				'original_mime_type' => $attachment->post_mime_type,
				'final_mime_type'    => $attachment->post_mime_type,
				'extra_data'         => (string) $extra_data,
				'created_at'         => time(),
			];

			$format = [
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
			];

			$rows_inserted = $wpdb->insert( RIO_Process_Queue::table_name(), $data, $format );

			if ( $rows_inserted > 0 ) {
				$processed_items ++;

				$attachment_id = absint( $attachment->ID );
				$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id='{$attachment_id}' AND meta_key LIKE 'wio_%'" );
			}
		}

		$left_items     = $attachments_total - $processed_items;
		$message        = sprintf( __( 'left to migrate: %s items', 'robin-image-optimizer' ), $left_items );
		$need_more_time = true;

		WRIO_Logger::info( 'Succefull migrated ' . $processed_items . ' items.' );
	} else {
		WRIO_Logger::info( 'Succefull migrated all items. Finishing-up...' );

		// Assumed to be 2 after 010105.php migration
		RIO_Process_Queue::update_db_version(2);

		$need_more_time = false;
		$message        = __( 'Finishing-up...', 'robin-image-optimizer' );
	}

	WRIO_Logger::memory_usage();

	wp_send_json_success( [
		'need_more_time' => $need_more_time,
		'message'        => $message,
	] );
}


