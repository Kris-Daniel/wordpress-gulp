<?php

/**
 * The page Settings.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс отвечает за работу страницы настроек
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Page extends Wbcr_FactoryClearfy209_PageBase {

	/**
	 * {@inheritdoc}
	 */
	public $page_parent_page = null;

	/**
	 * {@inheritdoc}
	 */
	public $available_for_multisite = false;

	/**
	 * {@inheritdoc}
	 */
	public $clearfy_collaboration = false;

	/**
	 * {@inheritdoc}
	 */
	public $show_right_sidebar_in_options = true;


	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @var WRIO_Views
	 */
	protected $view;

	/**
	 * @param WRIO_Plugin $plugin
	 */
	public function __construct( WRIO_Plugin $plugin ) {
		$this->view = WRIO_Views::get_instance( WRIO_PLUGIN_DIR );

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
			$this->page_parent_page = 'rio_general';

			return 'wbcr_clearfy';
		}

		return $this->plugin->getPluginName();
	}
}
