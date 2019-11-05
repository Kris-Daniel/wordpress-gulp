<?php
/**
 * Back-up related filters.
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX обработчик восстановления из резервной копии
 */
add_action( 'wp_ajax_wio_restore_backup', function () {
	check_admin_referer( 'wio-iph' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$max_process_per_request = 25;

	//$blog_id = WRIO_Plugin::app()->request->post( 'blog_id', null, true );

	/*if ( $blog_id !== null ) {
		switch_to_blog( $blog_id );
	}*/

	// Total number of remained images to restore
	$remane_count = 0;

	$total = 0;

	$filter_results = apply_filters( 'wbcr/rio/backup/restore_filter', $max_process_per_request );

	if ( isset( $filter_results['remane'] ) ) {
		$remane_count += $filter_results['remane'];
	}

	if ( isset( $filter_results['total'] ) ) {
		$total += $filter_results['total'];
	}

	$media_library = WRIO_Media_Library::get_instance();

	$total += $media_library->getOptimizedCount();

	$restored_data = $media_library->restoreAllFromBackup( $max_process_per_request );

	if ( isset( $restored_data['remain'] ) ) {
		$remane_count += $restored_data['remain'];
	}

	/*if ( $blog_id !== null ) {
		restore_current_blog();
	}*/

	$restored_data['total'] = $total;

	if ( $total > 0 ) {
		$restored_data['percent'] = 100 - ( $remane_count * 100 / $total );
	} else {
		$restored_data['percent'] = 0;
	}

	// если изображения закончились - посылаем команду завершения
	if ( $remane_count <= 0 ) {
		$restored_data['end'] = true;
	}

	wp_send_json( $restored_data );
} );

/**
 * AJAX обработчик очистки папки с бекапами
 */
add_action( 'wp_ajax_wio_clear_backup', function () {
	check_admin_referer( 'wio-iph' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$backup = WIO_Backup::get_instance();
	$blogs  = WRIO_Plugin::app()->request->post( 'blogs', [], true );

	if ( ! empty( $blogs ) ) {
		foreach ( $blogs as $blog_id ) {
			switch_to_blog( intval( $blog_id ) );
			$backup->removeBlogBackupDir();
			restore_current_blog();
		}
	} else {
		$backup->removeBackupDir();
	}

	wp_send_json( true );
} );
	
