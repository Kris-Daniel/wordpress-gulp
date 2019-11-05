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
class WRIO_StatisticNextgenPage extends WRIO_StatisticPage {

	/**
	 * {@inheritdoc}
	 */
	public $id = 'io_nextgen_gallery_statistic';

	/**
	 * {@inheritdoc}
	 */
	public $page_menu_dashicon = 'dashicons-images-alt';

	/**
	 * {@inheritdoc}
	 */
	public $internal = true;

	/**
	 * none - to hide page from plugin menu
	 * {@inheritdoc}
	 */
	public $page_parent_page = 'none';


	/**
	 * {@inheritdoc}
	 */
	public $add_link_to_plugin_actions = false;

	/**
	 * {@inheritdoc}
	 */
	protected $scope = 'nextgen-gallery';

	/**
	 * @param WRIO_Plugin $plugin
	 */
	public function __construct( WRIO_Plugin $plugin ) {
		$this->plugin = $plugin;

		parent::__construct( $plugin );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMenuTitle() {
		return __( 'NextGen Gallery', 'robin-image-optimizer' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPageTitle() {
		return __( 'NextGen Gallery', 'robin-image-optimizer' );
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
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @return object|\WRIO_Image_Statistic
	 */
	protected function get_statisctic_data() {
		return WRIO_Image_Statistic_Nextgen::get_instance();
	}
}
