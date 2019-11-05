<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WRIO_StatisticPage
 * Класс отвечает за работу страницы статистики
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 */
class WRIO_StatisticPage extends WRIO_Page {

	/**
	 * {@inheritdoc}
	 */
	public $id = 'rio_general';

	/**
	 * {@inheritdoc}
	 */
	public $type = 'page';

	/**
	 * {@inheritdoc}
	 */
	public $plugin;

	/**
	 * {@inheritdoc}
	 */
	public $page_menu_position = 20;

	/**
	 * {@inheritdoc}
	 */
	public $page_menu_dashicon = 'dashicons-chart-line';

	/**
	 * @var string
	 */
	public $menu_target = 'options-general.php';

	/**
	 * @var bool
	 */
	public $internal = false;

	/**
	 * @var bool
	 */
	public $add_link_to_plugin_actions = true;

	/**
	 * Page type
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @var string
	 */
	protected $scope = 'media-library';


	/**
	 * @param WRIO_Plugin $plugin
	 */
	public function __construct( WRIO_Plugin $plugin ) {
		$this->menu_title                  = __( 'Robin image optimizer', 'robin-image-optimizer' );
		$this->page_menu_short_description = __( 'Compress bulk of images', 'robin-image-optimizer' );
		$this->plugin                      = $plugin;

		parent::__construct( $plugin );

		add_action( 'admin_enqueue_scripts', [ $this, 'print_i18n' ] );
	}

	/**
	 * Подменяем простраинство имен для меню плагина, если активирован плагин Clearfy
	 * Меню текущего плагина будет добавлено в общее меню Clearfy
	 *
	 * @return string
	 */
	public function getMenuScope() {
		if ( $this->clearfy_collaboration ) {
			$this->internal = true;

			return 'wbcr_clearfy';
		}

		return $this->plugin->getPluginName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMenuTitle() {
		return $this->clearfy_collaboration ? __( 'Robin Image Optimizer', 'robin-image-optimizer' ) : __( 'Robin image optimizer', 'robin-image-optimizer' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPageTitle() {
		return $this->clearfy_collaboration ? __( 'Image optimizer', 'robin-image-optimizer' ) : __( 'Bulk optimization', 'robin-image-optimizer' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->add( WRIO_PLUGIN_URL . '/admin/assets/css/base-statistic.css' );

		$this->scripts->add( WRIO_PLUGIN_URL . '/admin/assets/js/sweetalert2.js' );
		$this->styles->add( WRIO_PLUGIN_URL . '/admin/assets/css/sweetalert2.css' );
		$this->styles->add( WRIO_PLUGIN_URL . '/admin/assets/css/sweetalert-custom.css' );

		$this->scripts->add( WRIO_PLUGIN_URL . '/admin/assets/js/Chart.min.js' );
		$this->scripts->add( WRIO_PLUGIN_URL . '/admin/assets/js/statistic.js' );

		$this->scripts->add( WRIO_PLUGIN_URL . '/admin/assets/js/modals.js', [ 'jquery' ], 'wrio-modals' );
		$this->scripts->add( WRIO_PLUGIN_URL . '/admin/assets/js/bulk-optimization.js', [
			'jquery',
			'wrio-modals'
		] );

		// Add Clearfy styles for HMWP pages
		if ( defined( 'WBCR_CLEARFY_PLUGIN_ACTIVE' ) ) {
			$this->styles->add( WCL_PLUGIN_URL . '/admin/assets/css/general.css' );
		}
	}

	/**
	 * Print localization only current page
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @throws \Exception
	 */
	public function print_i18n() {
		$page = $this->plugin->request->get( 'page', null );

		if ( $page != $this->getResultId() ) {
			return;
		}

		$backup = new WIO_Backup();

		wp_localize_script( 'jquery', 'wrio_l18n_bulk_page', $this->get_i18n() );

		wp_localize_script( 'jquery', 'wrio_settings_bulk_page', [
			'is_premium'             => wrio_is_license_activate(),
			'is_network_admin'       => WRIO_Plugin::app()->isNetworkAdmin() ? 1 : 0,
			'is_writable_backup_dir' => $backup->isBackupWritable() ? 1 : 0,
			'images_backup'          => WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false ) ? 1 : 0,
			'need_migration'         => wbcr_rio_has_meta_to_migrate() ? 1 : 0,
			'scope'                  => $this->scope,
			'nonce'                  => wp_create_nonce( 'bulk_optimization' )
		] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function showPageContent() {
		$is_premium = wrio_is_license_activate();
		$statistics = $this->get_statisctic_data();

		$template_data = [
			'is_premium' => $is_premium,
			'scope'      => $this->scope
		];

		//do_action( 'wbcr/rio/multisite_current_blog' );

		// Page header
		$this->view->print_template( 'part-page-header', [
			'title'       => __( 'Image optimization dashboard', 'robin-image-optimizer' ),
			'description' => __( 'Monitor image optimization statistics and run on demand or scheduled optimization.', 'robin-image-optimizer' )
		], $this );

		// Page tabs
		$this->view->print_template( 'part-bulk-optimization-tabs', $template_data, $this );

		?>
        <div class="wbcr-factory-page-group-body" style="padding:0; border-top: 1px solid #d4d4d4;">
			<?php
			// Servers
			$this->view->print_template( 'part-bulk-optimization-servers', $template_data, $this );

			// Statistic
			$this->view->print_template( 'part-bulk-optimization-statistic', array_merge( $template_data, [
				'stats' => $statistics->get()
			] ), $this );

			// Optimization log
			$this->view->print_template( 'part-bulk-optimization-log', array_merge( $template_data, [
				'process_log' => $statistics->get_last_optimized_images()
			] ), $this );
			?>
        </div>
        <script type="text/html" id="wrio-tmpl-bulk-optimization">
			<?php $this->view->print_template( 'modal-bulk-optimization' ); ?>
        </script>
		<?php

		//do_action( 'wbcr/rio/multisite_restore_blog' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @return object|\WRIO_Image_Statistic
	 */
	protected function get_statisctic_data() {
		return WRIO_Image_Statistic::get_instance();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @return array
	 */
	protected function get_i18n() {
		return [
			'server_down_warning'              => __( 'Your selected optimization server is down. This means that you cannot optimize images through this server. Try selecting another optimization server.', 'robin-image-optimizer' ),
			'server_status_down'               => __( 'down', 'robin-image-optimizer' ),
			'server_status_stable'             => __( 'stable', 'robin-image-optimizer' ),
			'modal_error'                      => __( 'Error', 'robin-image-optimizer' ),
			'modal_cancel'                     => __( 'Cancel', 'robin-image-optimizer' ),
			'modal_confirm'                    => __( 'Confirm', 'robin-image-optimizer' ),
			'modal_optimization_title'         => __( 'Select optimization way', 'robin-image-optimizer' ),
			'modal_optimization_monual_button' => __( 'Optimize now', 'robin-image-optimizer' ),
			'modal_optimization_cron_button'   => __( 'Scheduled optimization', 'robin-image-optimizer' ),
			'need_migrations'                  => __( 'To start optimizing, you must complete migration from old plugin version.', 'robin-image-optimizer' ),
			'optimization_complete'            => __( 'All images from the media library are optimized.', 'robin-image-optimizer' ),
			'optimization_inprogress'          => __( 'Optimization in progress. Remained <span id="wio-total-unoptimized">%s</span> images.', 'robin-image-optimizer' ),
			'leave_page_warning'               => __( 'Are you sure that you want to leave the page? The optimization process is not over yet, stay on the page until the end of the optimization process.', 'robin-image-optimizer' ),
			'process_without_backup'           => __( 'Do you want to start optimization without backup?', 'robin-image-optimizer' ),
			'button_resume'                    => __( 'Resume', 'robin-image-optimizer' ),
			'button_completed'                 => __( 'Completed', 'robin-image-optimizer' ),
			'buttom_start'                     => __( 'Run', 'robin-image-optimizer' ),
			'button_stop'                      => __( 'Stop', 'robin-image-optimizer' ),
			'button_stop_cron'                 => __( 'Stop shedule optimization', 'robin-image-optimizer' )
			//Don't Need a Parachute?
			//If you keep this option deactivated, you won't be able to re-optimize your images to another compression level and restore your original images in case of need.
		];
	}
}
