<?php
/**
 * Plugin Name: Webcraftic Robin image optimizer premium
 * Plugin URI: https://robin-image-optimizer.webcraftic.com
 * Description: This is an extension for the plugin Robin image optimizer. Adds additional functions: Converting images to Webp, optimization of arbitrary directories, optimization of the Nextgen gallery.
 * Author: Webcraftic <wordpress.webraftic@gmail.com>
 * Version: 1.0.4
 * Text Domain: robin-image-optimizer
 * Domain Path: /languages/ Author
 * URI: https://robin-image-optimizer.webcraftic.com
 */

// Выход при непосредственном доступе

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wrio_premium_load' ) ) {

	function wrio_premium_load() {

		if ( ! defined( 'WRIO_PLUGIN_ACTIVE' ) || defined( 'WRIO_PLUGIN_THROW_ERROR' ) ) {
			return;
		}

		// Устанавливаем контстанту, что плагин уже используется
		define( 'WRIOP_PLUGIN_ACTIVE', true );

		// Устанавливаем контстанту, что плагин уже используется
		define( 'WRIOP_PLUGIN_VERSION', '1.0.4' );

		// Директория плагина
		define( 'WRIOP_PLUGIN_DIR', dirname( __FILE__ ) );

		// Относительный путь к плагину
		define( 'WRIOP_PLUGIN_BASE', plugin_basename( __FILE__ ) );

		// Ссылка к директории плагина
		define( 'WRIOP_PLUGIN_URL', plugins_url( null, __FILE__ ) );

		// Global scripts
		// ---------------------------------------------------------
		require_once( WRIOP_PLUGIN_DIR . '/includes/functions.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.backup.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.image-statistic-folders.php' );

		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.folder.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/models/class.folders-extra-data.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.folder-image.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.custom-folders.php' );

		if ( wrio_is_active_nextgen_gallery() ) {
			require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.gallery-nextgen.php' );
			require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.image-nextgen.php' );
			require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.image-statistic-nextgen.php' );
		}

		// Utils
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/helpers/class.url.php' ); // URL helper

		// WebP format
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/webp/vendor/autoload.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/webp/class-webp-api.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/webp/class-webp-listener.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/webp/class-webp-delivery.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/models/class.webp-extra-data.php' );

		new WRIO\WEBP\HTML\Delivery();
		new WRIO\WEBP\Listener();

		// Admin scripts
		// ---------------------------------------------------------
		if ( is_admin() ) {
			WRIO_Custom_Folders::get_instance();

			// Register hooks
			require_once( WRIOP_PLUGIN_DIR . '/admin/filters/backup.php' );
			require_once( WRIOP_PLUGIN_DIR . '/admin/filters/settings-page.php' );

			require_once( WRIOP_PLUGIN_DIR . '/admin/ajax/folders.php' );
			require_once( WRIOP_PLUGIN_DIR . '/includes/classes/webp/class-webp-server.php' );
			require_once( WRIOP_PLUGIN_DIR . '/admin/boot.php' );

			try {
				$admin_path = WRIOP_PLUGIN_DIR . '/admin/pages';
				WRIO_Plugin::app()->registerPage( 'WRIO_StatisticFolders', $admin_path . '/class-rio-statistic-folders-page.php' );

				if ( wrio_is_active_nextgen_gallery() ) {
					WRIO_Plugin::app()->registerPage( 'WRIO_StatisticNextgenPage', $admin_path . '/class-rio-statistic-nextgen-page.php' );
				}
			} catch( Exception $e ) {
				//nothing
			}
		}
	}
	//add_action( 'plugins_loaded', 'wrio_premium_load', 20 );
}

/**
 * Function is performed when the parent plugin Robin image optimizer is activated.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.0.4
 */
function wrio_premium_activate() {
	require_once( dirname( __FILE__ ) . '/includes/classes/webp/class-webp-server.php' );
	require_once( dirname( __FILE__ ) . '/includes/classes/webp/class-webp-delivery.php' );

	$is_apache       = WRIO\WEBP\Server::is_apache();
	$is_use_htaccess = WRIO\WEBP\Server::server_use_htaccess();

	if ( WRIO\WEBP\HTML\Delivery::is_webp_enabled() && WRIO\WEBP\HTML\Delivery::is_redirect_delivery_mode() ) {
		if ( $is_apache && $is_use_htaccess ) {

			WRIO\WEBP\Server::htaccess_update_webp_rules();
		}
	}
}

/**
 * Function is performed when the parent plugin Robin image optimizer is deactivated.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.0.4
 */
function wrio_premium_deactivate() {
	require_once( dirname( __FILE__ ) . '/includes/classes/webp/class-webp-server.php' );

	$is_apache       = WRIO\WEBP\Server::is_apache();
	$is_use_htaccess = WRIO\WEBP\Server::server_use_htaccess();

	if ( $is_apache && $is_use_htaccess ) {
		WRIO\WEBP\Server::htaccess_clear_webp_rules();
	}
}