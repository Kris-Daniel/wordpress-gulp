<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Основной класс плагина
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 19.02.2018, Webcraftic
 * @version       1.0
 */
class WRIO_Plugin extends Wbcr_Factory414_Plugin {

	/**
	 * @var Wbcr_Factory414_Plugin
	 */
	private static $app;

	/**
	 * @param string $plugin_path
	 * @param array  $data
	 *
	 * @throws Exception
	 */
	public function __construct( $plugin_path, $data ) {
		self::$app = $this;
		parent::__construct( $plugin_path, $data );

		$this->includes();

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Ajax files
			require_once( WRIO_PLUGIN_DIR . '/admin/ajax/backup.php' );
			require_once( WRIO_PLUGIN_DIR . '/admin/ajax/bulk-optimization.php' );
			require_once( WRIO_PLUGIN_DIR . '/admin/ajax/logs.php' );
			// Not under AJAX logical operator above on purpose to have helpers available to find out whether
			// metas were migrated or not
			require_once( WRIO_PLUGIN_DIR . '/admin/ajax/meta-migrations.php' );
		}

		if ( is_admin() ) {
			$this->initActivation();
		}

		add_action( 'plugins_loaded', [ $this, 'pluginsLoaded' ] );
	}

	/**
	 * Подключаем модули классы и функции
	 */
	protected function includes() {

		require_once( WRIO_PLUGIN_DIR . '/includes/functions.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/class-rio-views.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/class-rio-attachment.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/class-rio-media-library.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/processors/class-rio-server-abstract.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/class-rio-image-statistic.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/class-rio-backup.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/class-rio-optimization-tools.php' );

		require_once( WRIO_PLUGIN_DIR . '/includes/classes/models/class-rio-base-helper.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/models/class-rio-base-object.php' ); // Base object

		// Database related models
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/models/class-rio-base-active-record.php' );
		// Base class
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/models/class-rio-base-extra-data.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/models/class-rio-attachment-extra-data.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/models/class-rio-server-smushit-extra-data.php' );

		require_once( WRIO_PLUGIN_DIR . '/includes/classes/models/class-rio-process-queue-table.php' ); // Processing queue model

		// Cron
		// ----------------
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/class-rio-cron.php' );
		new WRIO_Cron();

		// Logger
		// ----------------
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/logger/class-rio-logger.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/logger/class-rio-log-export.php' );
		require_once( WRIO_PLUGIN_DIR . '/includes/classes/logger/class-rio-log-reader.php' );
		new WRIO_Logger();
	}

	/**
	 * Статический метод для быстрого доступа к информации о плагине, а также часто использумых методах.
	 *
	 * @return Wbcr_Factory414_Plugin
	 */
	public static function app() {
		return self::$app;
	}

	/**
	 * Инициализируем активацию плагина
	 */
	protected function initActivation() {
		include_once( WRIO_PLUGIN_DIR . '/admin/activation.php' );
		self::app()->registerActivation( 'WIO_Activation' );
	}

	/**
	 * Регистрируем страницы плагина
	 *
	 * @throws Exception
	 */
	private function registerPages() {
		$admin_path = WRIO_PLUGIN_DIR . '/admin/pages/';

		// Parent page class
		require_once( $admin_path . '/class-rio-page.php' );

		if ( ! wrio_is_clearfy_license_activate() ) {
			self::app()->registerPage( 'WRIO_License_Page', $admin_path . '/class-rio-license.php' );
		}

		self::app()->registerPage( 'WRIO_SettingsPage', $admin_path . '/class-rio-settings.php' );
		self::app()->registerPage( 'WRIO_StatisticPage', $admin_path . '/class-rio-statistic.php' );

		if ( self::app()->getPopulateOption( 'error_log', false ) ) {
			self::app()->registerPage( 'WRIO_LogPage', $admin_path . '/class-rio-log.php' );
		}
	}

	/**
	 * Подключаем функции бекенда
	 *
	 * @throws Exception
	 */
	public function pluginsLoaded() {
		if ( is_admin() || wrio_doing_cron() || wrio_doing_rest_api() ) {
			$media_library = WRIO_Media_Library::get_instance();
			$media_library->initHooks();
		}

		if ( is_admin() ) {
			require_once( WRIO_PLUGIN_DIR . '/admin/boot.php' );
			//require_once( WRIO_PLUGIN_DIR . '/admin/includes/classes/class-rio-nextgen-landing.php' );

			$this->registerPages();
		}

		if ( wrio_doing_cron() || wrio_doing_rest_api() ) {
			$media_library = WRIO_Media_Library::get_instance();
			$media_library->initHooks();
		}

		if ( wrio_is_license_activate() ) {
			require_once( WRIO_PLUGIN_DIR . '/libs/addons/robin-image-optimizer-premium.php' );
			wrio_premium_load();
		}
	}
}

