<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WRIOP_License
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class WRIO_License_Page extends Wbcr_FactoryClearfy209_LicensePage {

	/**
	 * {@inheritdoc}
	 */
	public $id = 'rio_license';

	/**
	 * {@inheritdoc}
	 */
	public $page_parent_page = null;

	/**
	 * {@inheritdoc}
	 */
	public $available_for_multisite = true;

	/**
	 * {@inheritdoc}
	 */
	public $clearfy_collaboration = false;

	/**
	 * {@inheritdoc}
	 */
	public $show_right_sidebar_in_options = true;

	/**
	 * {@inheritdoc}
	 */
	public $page_menu_position = 0;

	/**
	 * {@inheritdoc}
	 * @param Wbcr_Factory414_Plugin $plugin
	 */
	public function __construct( Wbcr_Factory414_Plugin $plugin ) {
		$this->menu_title                  = __( 'License', 'robin-image-optimizer' );
		$this->page_menu_short_description = __( 'Product activation', 'robin-image-optimizer' );

		$this->plan_name = __( 'Robin image optimizer Premium', 'robin-image-optimizer' );

		if ( is_multisite() && defined( 'WBCR_CLEARFY_PLUGIN_ACTIVE' ) ) {
			$clearfy_is_active_for_network = is_plugin_active_for_network( Wbcr_FactoryClearfy_Compatibility::getClearfyBasePath() );

			if ( WRIO_Plugin::app()->isNetworkActive() && $clearfy_is_active_for_network ) {
				$this->clearfy_collaboration = true;
			}
		} else if ( defined( 'WBCR_CLEARFY_PLUGIN_ACTIVE' ) ) {
			$this->clearfy_collaboration = true;
		}

		parent::__construct( $plugin );
	}

	/**
	 * Подменяем простраинство имен для меню плагина, если активирован плагин Clearfy
	 * Меню текущего плагина будет добавлено в общее меню Clearfy
	 *
	 * @return string
	 */
	public function getMenuScope() {
		if ( $this->clearfy_collaboration ) {
			//$this->page_parent_page = 'rio_general';
			$this->page_parent_page = 'none';

			return 'wbcr_clearfy';
		}

		return $this->plugin->getPluginName();
	}

	public function get_plan_description() {
		//$paragraf1 = sprintf( __( '<b>%s</b> is a premium image optimization plugin for WordPress. ', 'robin-image-optimizer' ), $this->plan_name ) . '</p>';
		//$paragraf2 = '<p style="font-size: 16px;">' . __( 'Paid license guarantees that you can optimize images under better conditions and WebP support.', 'robin-image-optimizer' );
		//return '<p style="font-size: 16px;">' . $paragraf1 . '</p><p style="font-size: 16px;">' . $paragraf2 . '</p>';

		//return '<p style="font-size: 16px;">' . $paragraf1 . '</p>';
	}
}