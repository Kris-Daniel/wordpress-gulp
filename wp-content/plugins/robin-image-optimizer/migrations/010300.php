<?php #comp-page builds: premium

/**
 * Updates for altering the table used to store statistics data.
 * Adds new columns and renames existing ones in order to add support for the new social buttons.
 */
class WIOUpdate010300 extends Wbcr_Factory414_Update {

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 * @throws \Exception
	 */
	public function install() {

		$db_version             = RIO_Process_Queue::get_db_version();
		$plugin_version_in_db   = $this->get_plugin_version_in_db();
		$current_plugin_version = $this->plugin->getPluginVersion();

		$init_log_message = "Start plugin migration < %s.\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-DB Version: {$db_version}\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Plugin Version in DB: {$plugin_version_in_db}\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Current Plugin Version: {$current_plugin_version}";

		WRIO_Logger::info( sprintf( $init_log_message, '1.3.0' ) );

		RIO_Process_Queue::try_create_plugin_tables();

		$this->clear_log();

		WRIO_Plugin::app()->deleteOption( 'cron_running' );

		if ( class_exists( 'WRIO_Cron' ) ) {
			WRIO_Cron::stop();
		}

		WbcrFactoryClearfy209_Helpers::flushPageCache();

		WRIO_Logger::info( 'Plugin migration was successfull!' );
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
	 * Since version 1.3.0, we use a different path and a different algorithm for
	 * accumulating log files. Therefore, if there is an old log file, you need to clear it.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 */
	public function clear_log() {
		$wp_upload_dir = wp_upload_dir();

		if ( isset( $wp_upload_dir['error'] ) && $wp_upload_dir['error'] !== false ) {
			WRIO_Logger::error( sprintf( 'Plugin migration error: %s', $wp_upload_dir['error'] ) );

			return;
		}

		$file_path = wp_normalize_path( trailingslashit( $wp_upload_dir['basedir'] ) . 'wio.log' );

		if ( file_exists( $file_path ) ) {
			if ( @unlink( $file_path ) ) {
				WRIO_Logger::info( 'Plugin migration: The old error log was successfully deleted!' );
			}
		}
	}
}
