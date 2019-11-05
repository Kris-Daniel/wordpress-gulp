<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы в multisite режиме.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WIO_Multisite {

	/**
	 * Инициализация хуков
	 */
	public function initHooks() {
		add_action( 'wbcr/rio/multisite_current_blog', [ $this, 'setCurrentBlog' ] );
		add_action( 'wbcr/rio/multisite_restore_blog', [ $this, 'restoreBlog' ] );
	}

	/**
	 * Устанавливает текущий блог в соответствии с выбором пользователя
	 */
	public function setCurrentBlog() {
		$current_blog_id = WRIO_Plugin::app()->getPopulateOption( 'current_blog', 1 );
		switch_to_blog( $current_blog_id );
	}

	/**
	 * Сбрасывает текущий блог
	 */
	public function restoreBlog() {
		restore_current_blog();
	}

	/**
	 * Получает список блогов в зависимости от контекста
	 *
	 * @param string $context   контекст. Например media-library или nextgen
	 *
	 * @return array $blogs
	 */
	public static function getBlogs( $context = 'media-library' ) {
		global $wpdb;
		$blogs = $wpdb->get_results( "SELECT blog_id, domain, path FROM {$wpdb->blogs}" );
		$blogs = apply_filters( 'wbcr/rio/multisite_blogs', $blogs, $context );

		return $blogs;
	}
}
