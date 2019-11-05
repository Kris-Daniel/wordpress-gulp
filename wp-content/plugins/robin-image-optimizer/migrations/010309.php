<?php #comp-page builds: premium

/**
 * Updates for altering the table used to store statistics data.
 * Adds new columns and renames existing ones in order to add support for the new social buttons.
 */
class WIOUpdate010309 extends Wbcr_Factory414_Update {

	/**
	 * {inherit}
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 */
	public function install() {

		$db_version             = RIO_Process_Queue::get_db_version();
		$plugin_version_in_db   = $this->get_plugin_version_in_db();
		$current_plugin_version = $this->plugin->getPluginVersion();

		$init_log_message = "Start plugin migration < %s.\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-DB Version: {$db_version}\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Plugin Version in DB: {$plugin_version_in_db}\r\n";
		$init_log_message .= "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t-Current Plugin Version: {$current_plugin_version}";

		WRIO_Logger::info( sprintf( $init_log_message, '1.3.9' ) );

		$this->add_indexes_in_db();

		WbcrFactoryClearfy209_Helpers::flushPageCache();

		WRIO_Logger::info( 'Plugin migration was successfull!' );
	}

	/**
	 * Get previous plugin version
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 * @return number
	 */
	public function get_plugin_version_in_db() {
		if ( WRIO_Plugin::app()->isNetworkActive() ) {
			return get_site_option( WRIO_Plugin::app()->getOptionName( 'plugin_version' ), 0 );
		}

		return get_option( WRIO_Plugin::app()->getOptionName( 'plugin_version' ), 0 );
	}

	/**
	 * In older plugin relises, the option 'plugin_activated' did not exist. Therefore,
	 * if during migration it still does not exist, you need to add option with the current timestamp.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 */
	public function update_plugin_activation_time() {
		if ( $this->plugin->isNetworkActive() ) {
			$activated = get_site_option( $this->plugin->getOptionName( 'plugin_activated' ), 0 );
		} else {
			$activated = get_option( $this->plugin->getOptionName( 'plugin_activated' ), 0 );
		}

		if ( ! $activated ) {
			if ( $this->plugin->isNetworkActive() ) {
				update_site_option( $this->plugin->getOptionName( 'plugin_activated' ), time() );
				update_site_option( $this->plugin->getOptionName( 'plugin_version' ), $this->plugin->getPluginVersion() );
			} else {
				update_option( $this->plugin->getOptionName( 'plugin_activated' ), time() );
				update_option( $this->plugin->getOptionName( 'plugin_version' ), $this->plugin->getPluginVersion() );
			}
		}
	}

	/**
	 * Get previous plugin version
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 */
	public function add_indexes_in_db() {
		global $wpdb;

		$table_name = RIO_Process_Queue::table_name();
		$wpdb->query( "ALTER TABLE {$table_name} ADD INDEX `index-type-attachments` (`object_id`, `item_type`);" );
	}
}
