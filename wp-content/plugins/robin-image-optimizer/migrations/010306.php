<?php #comp-page builds: premium

/**
 * Updates for altering the table used to store statistics data.
 * Adds new columns and renames existing ones in order to add support for the new social buttons.
 */
class WIOUpdate010306 extends Wbcr_Factory414_Update {

	/**
	 * {inherit}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 */
	public function install() {

		$db_version             = RIO_Process_Queue::get_db_version();
		$plugin_version_in_db   = $this->get_plugin_version_in_db();
		$current_plugin_version = $this->plugin->getPluginVersion();

		$init_log_message = "Start plugin migration < %s.\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-DB Version: {$db_version}\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Plugin Version in DB: {$plugin_version_in_db}\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Current Plugin Version: {$current_plugin_version}";

		WRIO_Logger::info( sprintf( $init_log_message, '1.3.6' ) );

		$this->clear_webp_images();

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
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 * @see    RIO_Process_Queue::fix_table_collation
	 */
	public function fix_table_collation() {
		RIO_Process_Queue::fix_table_collation();
	}

	/**
	 * This removes webp queue items from in the database. For compatibility with the new
	 * version, it will be better to remove them, so that the user can start converting
	 * and have no compatibility problems.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 */
	public function clear_webp_queue_items() {
		global $wpdb;

		$table_name = RIO_Process_Queue::table_name();
		$wpdb->query( "DELETE FROM {$table_name} WHERE item_type='webp'" );
	}

	/**
	 * We are removing Webp dir, since the migration will be very difficult. The previous
	 * version of the plugin has serious problems in the design of webp image convertation.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 * @return bool
	 */
	public function clear_webp_images() {

		require_once( WRIO_PLUGIN_DIR . '/includes/functions.php' );

		$upload_dirs = wp_upload_dir();

		if ( isset( $upload_dirs['error'] ) && $upload_dirs['error'] !== false ) {
			WRIO_Logger::error( sprintf( 'Plugin migration error: %s', $upload_dirs['error'] ) );

			return false;
		}

		$content_path = $upload_dirs['basedir'];

		$dir_path = wp_normalize_path( trailingslashit( $content_path ) . 'wrio-webp-uploads' );

		if ( file_exists( $dir_path ) ) {
			$this->clear_webp_queue_items();
			$this->plugin->updatePopulateOption( 'cleared_webp_images', 1 );

			wrio_rmdir( $dir_path );

			return true;
		}

		return false;
	}
}
