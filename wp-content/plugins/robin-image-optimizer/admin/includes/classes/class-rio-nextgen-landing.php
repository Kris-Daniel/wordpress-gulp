<?php


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс используется для вывода страницы лендинга
 * @author Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version 1.0
 */
class WIO_NextgenLanding {

	/**
	 * Инициализация лендинга
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'removeSubMenu' ), 99999 );
		add_action( 'admin_menu', array( $this, 'addSubMenu' ), 20 );
	}

	/**
	 * Удаляет лендинг nextgen
	 */
	public function removeSubMenu() {
		remove_submenu_page( 'nextgen-gallery', 'ngg_imagify' );
	}

	/**
	 * Добавляем свою страницу в меню Тextgen
	 */
	public function addSubMenu() {
		add_submenu_page(
			'nextgen-gallery',
			__( 'Image optimizer', 'robin-image-optimizer' ),
			__( 'Image optimizer', 'robin-image-optimizer' ),
			'manage_options',
			'ngg_robin', // если взять старый слаг ngg_imagify, то на странице выведет оба лендинга
			array( $this, 'nngLandingPage' )
		);
	}

	/**
	 * Контент лендинга
	 */
	public function nngLandingPage() {
		// если активна премиум версия - делаем редирект на страницу статистики nextgen
		if ( defined( 'WRIOP_PLUGIN_ACTIVE' ) && WRIOP_PLUGIN_ACTIVE ) {
			wp_redirect( admin_url( 'admin.php?page=io_nextgen_gallery_statistic-wbcr_image_optimizer' ) );
			die();
		}
		?>
       рекламма установки премиум аддона
		<?php
	}
}

new WIO_NextgenLanding;
