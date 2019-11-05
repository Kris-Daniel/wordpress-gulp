<?php
/**
 * Ajax действие, которое выполняется при сохранении настроек
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
 * AJAX обработчик массовой сохранения уровня сжатия
 */
add_action( 'wp_ajax_wio_settings_update_level', function () {
	check_admin_referer( 'wio-iph' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$level = sanitize_text_field( $_POST['level'] );

	if ( ! $level ) {
		die();
	}

	if ( ! in_array( $level, [ 'normal', 'aggresive', 'ultra' ] ) ) {
		die();
	}

	WRIO_Plugin::app()->updatePopulateOption( 'image_optimization_level', $level );
	die();
} );
