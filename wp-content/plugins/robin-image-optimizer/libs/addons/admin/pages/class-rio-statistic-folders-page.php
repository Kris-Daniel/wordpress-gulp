<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс отвечает за работу страницы статистики
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_StatisticFolders extends WRIO_StatisticPage {

	/**
	 * {@inheritdoc}
	 */
	public $id = 'io_folders_statistic';

	/**
	 * {@inheritdoc}
	 */
	public $internal = true;


	/**
	 * {@inheritdoc}
	 */
	public $page_parent_page = 'none';

	/**
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-images-alt';

	/**
	 * {@inheritdoc}
	 */
	public $add_link_to_plugin_actions = false;

	/**
	 * {@inheritdoc}
	 */
	protected $scope = 'custom-folders';


	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0
	 * @var WRIO_Views
	 */
	private $parent_view;

	/**
	 * @param WRIO_Plugin $plugin
	 */
	public function __construct( WRIO_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->parent_view = $this->view;
		$this->view        = new WRIO_Views( WRIOP_PLUGIN_DIR );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMenuTitle() {
		return __( 'Custom Folders', 'robin-image-optimizer' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPageTitle() {
		return __( 'Custom Folders', 'robin-image-optimizer' );
	}

	/**
	 * Подменяем простраинство имен для меню плагина, если активирован плагин Clearfy
	 * Меню текущего плагина будет добавлено в общее меню Clearfy
	 *
	 * @return string
	 */
	public function getMenuScope() {
		if ( $this->clearfy_collaboration ) {
			$this->page_parent_page = 'rio_general';

			return 'wbcr_clearfy';
		}

		return $this->plugin->getPluginName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->add( WRIOP_PLUGIN_URL . '/admin/assets/css/jquery-file-tree.css' );
		$this->scripts->add( WRIOP_PLUGIN_URL . '/admin/assets/js/jquery-file-tree.js' );
		$this->scripts->add( WRIOP_PLUGIN_URL . '/admin/assets/css/custom-folders.css' );
		$this->scripts->add( WRIOP_PLUGIN_URL . '/admin/assets/js/custom-folders.js' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function showPageContent() {
		$is_premium = wrio_is_license_activate();
		$statistics = WRIO_Image_Statistic_Folders::get_instance();

		$template_data = [
			'is_premium' => $is_premium,
			'scope'      => $this->scope
		];

		//do_action( 'wbcr/rio/multisite_current_blog' );

		// Page header
		$this->parent_view->print_template( 'part-page-header', [
			'title'       => __( 'Image optimization dashboard', 'robin-image-optimizer' ),
			'description' => __( 'Monitor image optimization statistics and run on demand or scheduled optimization.', 'robin-image-optimizer' )
		], $this );

		// Page tabs
		$this->parent_view->print_template( 'part-bulk-optimization-tabs', $template_data, $this );

		?>
        <div class="wbcr-factory-page-group-body" style="padding:0; border-top: 1px solid #d4d4d4;">
			<?php
			// Servers
			$this->parent_view->print_template( 'part-bulk-optimization-servers', $template_data, $this );

			// Statistic
			$this->parent_view->print_template( 'part-bulk-optimization-statistic', array_merge( $template_data, [
				'stats' => $statistics->get()
			] ), $this );

			// Folders table
			$this->view->print_template( 'part-bulk-optimization-table-folders', $template_data, $this );

			// Optimization log
			$this->parent_view->print_template( 'part-bulk-optimization-log', array_merge( $template_data, [
				'process_log' => $statistics->get_last_optimized_images()
			] ), $this );
			?>
        </div>
        <script type="text/html" id="wrio-tmpl-bulk-optimization">
			<?php $this->parent_view->print_template( 'modal-bulk-optimization' ); ?>
        </script>
        <script type="text/html" id="wrio-tmpl-select-custom-folders">
			<?php $this->view->print_template( 'modal-select-custom-folders' ); ?>
        </script>
		<?php
		//do_action( 'wbcr/rio/multisite_restore_blog' );
	}

	protected function get_i18n() {
		$i18n = parent::get_i18n();

		$i18n['modal_cf_title'] = __( 'Select custom folder', 'robin-image-optimizer' );
		//$i18n['modal_cf_description']   = __( 'Select a directory for optimization. All nested images and folders will be optimized recursively.', 'robin-image-optimizer' );
		$i18n['button_select']         = __( 'Select', 'robin-image-optimizer' );
		$i18n['button_cancel']         = __( 'Cancel', 'robin-image-optimizer' );
		$i18n['button_remove']         = __( 'Remove', 'robin-image-optimizer' );
		$i18n['alert_remove_folder']   = __( 'Exclude directory from optimization?', 'robin-image-optimizer' );
		$i18n['found_images']          = __( 'Selected directory is being indexed. Found %d images.', 'robin-image-optimizer' );
		$i18n['scan_complete']         = __( 'Indexing complete. Directory successfully added and ready for optimization.', 'robin-image-optimizer' );
		$i18n['compressed_in_folder']  = __( 'Compressed %d of %s<br>images', 'robin-image-optimizer' );
		$i18n['optimization_complete'] = __( 'All images from custom folders are optimized.', 'robin-image-optimizer' );

		return $i18n;
	}
}
