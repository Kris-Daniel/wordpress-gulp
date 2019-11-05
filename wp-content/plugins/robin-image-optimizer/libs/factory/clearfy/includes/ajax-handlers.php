<?php
/**
 * Ajax handlers
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 2017 Webraftic Ltd
 * @version 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Обработчик ajax запросов для проверки, активации, деактивации лицензионного ключа
 *
 * @param Wbcr_Factory414_Plugin $plugin_instance
 *
 * @since 2.0.7
 *
 */
function wbcr_factory_clearfy_209_check_license( $plugin_instance ) {
	check_admin_referer( 'license' );
	
	$action      = $plugin_instance->request->post( 'license_action', false, true );
	$license_key = $plugin_instance->request->post( 'licensekey', null );
	
	if ( empty( $action ) || ! in_array( $action, array( 'activate', 'deactivate', 'sync', 'unsubscribe' ) ) ) {
		wp_send_json_error( array( 'error_message' => __( 'Licensing action not passed or this action is prohibited!', 'wbcr_factory_clearfy_209' ) ) );
		die();
	}
	
	$result          = null;
	$success_message = '';
	
	try {
		switch ( $action ) {
			case 'activate':
				if ( empty( $license_key ) || strlen( $license_key ) > 32 ) {
					wp_send_json_error( array( 'error_message' => __( 'License key is empty or license key too long (license key is 32 characters long)', 'wbcr_factory_clearfy_209' ) ) );
				} else {
					$plugin_instance->premium->activate( $license_key );
					$success_message = __( 'Your license has been successfully activated', 'wbcr_factory_clearfy_209' );
				}
				break;
			case 'deactivate':
				$plugin_instance->premium->deactivate();
				$success_message = __( 'The license is deactivated', 'wbcr_factory_clearfy_209' );
				break;
			case 'sync':
				$plugin_instance->premium->sync();
				$success_message = __( 'The license has been updated', 'wbcr_factory_clearfy_209' );
				break;
			case 'unsubscribe':
				$plugin_instance->premium->cancel_paid_subscription();
				$success_message = __( 'Subscription success cancelled', 'wbcr_factory_clearfy_209' );
				break;
		}
	} catch( Exception $e ) {
		
		/**
		 * Экшен выполняетяс, когда проверка лицензии вернула ошибку
		 *
		 * @param string $action
		 * @param string $license_key
		 * @param string $error_message
		 *
		 * @since 2.0.7
		 */
		do_action( 'wbcr/clearfy/check_license_error', $action, $license_key, $e->getMessage() );
		
		wp_send_json_error( array( 'error_message' => $e->getMessage() ) );
		die();
	}
	
	/**
	 * Экшен выполняет, когда проверка лицензии успешно завершена
	 *
	 * @param string $action
	 * @param string $license_key
	 *
	 * @since 2.0.7
	 */
	do_action( 'wbcr/clearfy/check_license_success', $action, $license_key );
	
	wp_send_json_success( array( 'message' => $success_message ) );
	
	die();
}