<?php

/**
 * Activator for the Robin image optimizer
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 09.09.2017, Webcraftic
 * @see           Factory414_Activator
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WIO_Activation extends Wbcr_Factory414_Activator {

	/**
	 * Runs activation actions.
	 *
	 * @since 1.0.0
	 * @throws \Exception
	 */
	public function activate() {
		WRIO_Logger::info( 'Parent plugin start installation!' );

		WRIO_Plugin::app()->updatePopulateOption( 'image_optimization_server', 'server_1' );
		WRIO_Plugin::app()->updatePopulateOption( 'backup_origin_images', 1 );
		WRIO_Plugin::app()->updatePopulateOption( 'save_exif_data', 1 );

		if ( function_exists( 'wrio_is_license_activate' ) && wrio_is_license_activate() ) {
			WRIO_Logger::info( 'Premium plugin start installation!' );
			require_once( WRIO_PLUGIN_DIR . '/libs/addons/robin-image-optimizer-premium.php' );
			wrio_premium_activate();
			WRIO_Logger::info( 'Premium plugin installation complete!' );
		}

		$db_version             = RIO_Process_Queue::get_db_version();
		$plugin_version_in_db   = $this->get_plugin_version_in_db();
		$current_plugin_version = $this->plugin->getPluginVersion();

		$create_table_log_message = "Plugin installation: try create plugin tables.\r\n";
		$create_table_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-DB Version: {$db_version}\r\n";
		$create_table_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Plugin Version in DB: {$plugin_version_in_db}\r\n";
		$create_table_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Current Plugin Version: {$current_plugin_version}";

		WRIO_Logger::info( $create_table_log_message );

		RIO_Process_Queue::try_create_plugin_tables();

		WbcrFactoryClearfy209_Helpers::flushPageCache();

		WRIO_Logger::info( 'Parent plugin installation complete!' );
	}

	/**
	 * Get previous plugin version
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.8
	 * @return number
	 */
	public function get_plugin_version_in_db() {
		if ( WRIO_Plugin::app()->isNetworkActive() ) {
			return get_site_option( WRIO_Plugin::app()->getOptionName( 'plugin_version' ), 0 );
		}

		return get_option( WRIO_Plugin::app()->getOptionName( 'plugin_version' ), 0 );
	}

	/**
	 * Runs activation actions.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		WRIO_Logger::info( 'Parent plugin start deactivation!' );

		if ( class_exists( 'WRIO_Cron' ) ) {
			WRIO_Cron::stop();
		}

		if ( function_exists( 'wrio_is_license_activate' ) && wrio_is_license_activate() ) {
			WRIO_Logger::info( 'Premium plugin start deactivation!' );
			require_once( WRIO_PLUGIN_DIR . '/libs/addons/robin-image-optimizer-premium.php' );
			wrio_premium_deactivate();
			WRIO_Logger::info( 'Premium plugin deactivation complete!' );
		}

		WRIO_Logger::info( 'Parent plugin deactivation complete!' );
	}
}
