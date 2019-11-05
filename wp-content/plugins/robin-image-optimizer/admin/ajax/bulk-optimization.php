<?php
/**
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron start
 */
add_action( 'wp_ajax_wrio-cron-start', function () {
	check_ajax_referer( 'bulk_optimization' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$scope = WRIO_Plugin::app()->request->request( 'scope', null, true );

	if ( empty( $scope ) ) {
		wp_die( - 1 );
	}

	// where was runned cron
	$cron_running_place = WRIO_Plugin::app()->getPopulateOption( 'cron_running', false );

	if ( $scope == $cron_running_place ) {
		wp_send_json_success();
	}

	WRIO_Plugin::app()->updatePopulateOption( 'cron_running', $scope );
	WRIO_Cron::start();

	wp_send_json_success();
} );

/**
 * Cron stop
 */
add_action( 'wp_ajax_wrio-cron-stop', function () {
	check_ajax_referer( 'bulk_optimization' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	WRIO_Plugin::app()->updatePopulateOption( 'cron_running', false );
	WRIO_Cron::stop();

	wp_send_json_success();
} );

/**
 * AJAX обработчик массовой оптимизации изображений со страницы статистики
 */
add_action( 'wp_ajax_wrio-bulk-optimization-process', function () {
	check_admin_referer( 'bulk_optimization' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$reset_current_error = (bool) WRIO_Plugin::app()->request->request( 'reset_current_errors' );
	$scope               = WRIO_Plugin::app()->request->request( 'scope', null, true );

	WRIO_Logger::info( sprintf( 'Start bulk optimization process! Scope: %s', $scope ) );

	if ( empty( $scope ) ) {
		wp_die( - 1 );
	}

	// Context class name. If plugin expands with add-ons
	$class_name = 'WRIO_' . wrio_dashes_to_camel_case( $scope, true );

	if ( ! class_exists( $class_name ) ) {
		WRIO_Logger::error( sprintf( 'Bulk optimization error: Context class (%s) not found.', $class_name ) );

		//todo: Temporary bug fix.
		if ( 'media-library' === $scope ) {
			$class_name = 'WRIO_Media_Library';
		} else if ( 'custom-folders' === $scope ) {
			$class_name = 'WRIO_Custom_Folders';
		} else if ( 'nextgen-gallery' == $scope ) {
			$class_name = 'WRIO_Nextgen_Gallery';
		}

		if ( ! class_exists( $class_name ) ) {
			wp_send_json_error( [ 'error_message' => 'Context class not found.' ] );
		}
	}

	/**
	 * Create an instance of the class depending on the context in which scope user
	 * has runned optimization.
	 *
	 * @see WRIO_Media_Library
	 * @see WRIO_Custom_Folders
	 * @see WRIO_Nextgen_Gallery
	 */
	$optimizer = new $class_name();

	// в ajax запросе мы не знаем, получен ли он из мультиадминки или из обычной. Поэтому проверяем параметр, полученный из frontend
	/*if ( isset( $_POST['multisite'] ) && (bool) $_POST['multisite'] ) {
		$multisite = new WIO_Multisite;
		$multisite->initHooks();
	}*/

	if ( $reset_current_error ) {
		$optimizer->resetCurrentErrors(); // сбрасываем текущие ошибки оптимизации
	}

	$result = $optimizer->processUnoptimizedImages( 1 );

	if ( is_wp_error( $result ) ) {
		$error_massage = $result->get_error_message();

		if ( empty( $error ) ) {
			$error_massage = __( "Unknown error. Enable error log on the plugin's settings page, then check the error report on the Error Log page. You can export the error report and send it to the support service of the plugin.", "robin-image-optimizer" );
		}

		WRIO_Logger::error( sprintf( 'Bulk optimization error: %s.', $result->get_error_message() ) );

		wp_send_json_error( [ 'error_message' => $error_massage ] );
	}

	// если изображения закончились - посылаем команду завершения
	if ( $result['remain'] <= 0 ) {
		$result['end'] = true;
	}

	WRIO_Logger::info( sprintf( 'End bulk optimization process! Scope: %s. Remain: %d', $scope, $result['remain'] ) );

	wp_send_json_success( $result );
} );

/**
 * Переоптимизация аттачмента
 */
add_action( 'wp_ajax_wio_reoptimize_image', function () {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$default_level = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level', 'normal' );

	$attachment_id = (int) WRIO_Plugin::app()->request->post( 'id' );
	$level         = WRIO_Plugin::app()->request->post( 'level', $default_level, true );

	$backup               = WIO_Backup::get_instance();
	$media_library        = WRIO_Media_Library::get_instance();
	$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );

	if ( $backup_origin_images && ! $backup->isBackupWritable() ) {
		echo $media_library->getMediaColumnContent( $attachment_id );
		die();
	}

	$optimized_data = $media_library->optimizeAttachment( $attachment_id, $level );

	if ( $optimized_data && isset( $optimized_data['processing'] ) ) {
		echo 'processing';
		die();
	}

	echo $media_library->getMediaColumnContent( $attachment_id );
	die();
} );

/**
 * Восстановление аттачмента из резервной копии
 */
add_action( 'wp_ajax_wio_restore_image', function () {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$attachment_id = (int) WRIO_Plugin::app()->request->post( 'id' );

	$media_library  = WRIO_Media_Library::get_instance();
	$wio_attachment = $media_library->getAttachment( $attachment_id );

	if ( $wio_attachment->isOptimized() ) {
		$media_library->restoreAttachment( $attachment_id );
	}

	echo $media_library->getMediaColumnContent( $attachment_id );
	die();
} );

/**
 * На странице массовой оптмизации есть поле для выбора сервера. Когда пользователь
 * выберет какой-то сервер, выполняется этот ajax обработчик. Обработчик пингует выбранный
 * пользователем сервер и возвращает статус пинга (если пинг успешен, то сервер переход в
 * статус выбранный).
 */
add_action( 'wp_ajax_wbcr-rio-check-servers-status', function () {

	check_ajax_referer( 'bulk_optimization' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$server_name = WRIO_Plugin::app()->request->post( 'server_name' );

	if ( empty( $server_name ) || ! in_array( $server_name, [
			'server_1',
			'server_2',
			'server_3',
			'server_4'
		] ) ) {
		wp_send_json_error( [ 'error' => __( 'Server name is empty!', 'robin-image-optimizer' ) ] );
	}

	// Позволяем выбрать сервер, даже если он недоступен.
	WRIO_Plugin::app()->updatePopulateOption( 'image_optimization_server', $server_name );

	// Проверяем доступность сервер
	// --------------------------------------------------------------------
	$return_data = [ 'server_name' => $server_name ];

	$server_url = wrio_get_server_url( $server_name );

	$method = 'POST';
	if ( $server_name == 'server_4' ) {
		$api_url = $server_url . '/upload/' . wrio_generate_random_string( 16 ) . '/';
	} else if ( $server_name == 'server_3' ) {
		$api_url = $server_url . '/s.w.org/images/home/screen-themes.png';
		$method  = 'GET';
	} else {
		$api_url = $server_url;
	}

	$request = wp_remote_request( $api_url, [
		'method' => $method
	] );

	if ( is_wp_error( $request ) ) {
		$er_msg = $request->get_error_message();

		if ( "server_2" == $server_name ) {
			// Hostgator Issue.
			if ( ! empty( $er_msg ) && strpos( $er_msg, 'SSL CA cert' ) !== false ) {
				// Update DB for using http protocol.
				WRIO_Plugin::app()->updatePopulateOption( 'use_http', 1 );
			}
		}

		$return_data['error'] = $er_msg;
		wp_send_json_error( $return_data );
	}

	$response_code = wp_remote_retrieve_response_code( $request );

	if ( $response_code != 200 ) {
		$return_data['error'] = 'Server response ' . $response_code;
		wp_send_json_error( $return_data );
	}

	wp_send_json_success( $return_data );
} );
