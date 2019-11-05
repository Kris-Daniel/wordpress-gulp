<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WRIO_Nextgen_Extra_Data is a  DTO model for `nextgen` post type used for `extra_data`
 * property in RIO_Process_Queue.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @see    RIO_Process_Queue::$extra_data for further information
 *
 */
class WRIO_Nextgen_Extra_Data extends RIO_Base_Extra_Data {

	/**
	 * @var string тип ошибки
	 */
	protected $error = null;

	/**
	 * @var string текст сообщения об ошибке
	 */
	protected $error_msg = null;

	/**
	 * @var int оригинальный размер основного файла
	 */
	protected $original_main_size = null;

	/**
	 * @var int оптимизированный размер основного файла
	 */
	protected $optimized_main_size = null;

	/**
	 * @var array ответ от сервера оптимизации по основному файлу
	 */
	protected $main_optimized_data = null;

	/**
	 * @var array ответ от сервера оптимизации по превьюшке
	 */
	protected $thumbnails_optimized_data = null;

	/**
	 * @var string относительный путь к изображению
	 */
	protected $image_relative_path = null;

	/**
	 * @var int размер основного изображения
	 */
	protected $webp_main_size = null;

	/**
	 * get_error
	 *
	 * @return string
	 */
	public function get_error() {
		return $this->error;
	}

	/**
	 * set_error
	 *
	 * @param string $error
	 *
	 * @return void
	 */
	public function set_error( $error ) {
		$this->error = $error;
	}

	/**
	 * get_error_msg
	 *
	 * @return string
	 */
	public function get_error_msg() {
		return $this->error_msg;
	}

	/**
	 * set_error_msg
	 *
	 * @param string $error_msg
	 *
	 * @return void
	 */
	public function set_error_msg( $error_msg ) {
		$this->error_msg = $error_msg;
	}

	/**
	 * get_original_main_size
	 *
	 * @return int
	 */
	public function get_original_main_size() {
		return $this->original_main_size;
	}

	/**
	 * set_original_main_size
	 *
	 * @param int $original_main_size
	 *
	 * @return void
	 */
	public function set_original_main_size( $original_main_size ) {
		$this->original_main_size = $original_main_size;
	}

	/**
	 * get_optimized_main_size
	 *
	 * @return int
	 */
	public function get_optimized_main_size() {
		return $this->optimized_main_size;
	}

	/**
	 * set_optimized_main_size
	 *
	 * @param int $optimized_main_size
	 *
	 * @return void
	 */
	public function set_optimized_main_size( $optimized_main_size ) {
		$this->optimized_main_size = $optimized_main_size;
	}

	/**
	 * get_main_optimized_data
	 *
	 * @return array
	 */
	public function get_main_optimized_data() {
		return (array) $this->main_optimized_data;
	}

	/**
	 * set_main_optimized_data
	 *
	 * @param array $main_optimized_data
	 *
	 * @return void
	 */
	public function set_main_optimized_data( $main_optimized_data ) {
		$this->main_optimized_data = $main_optimized_data;
	}

	/**
	 * get_thumbnails_optimized_data
	 *
	 * @return array
	 */
	public function get_thumbnails_optimized_data() {
		return (array) $this->thumbnails_optimized_data;
	}

	/**
	 * set_thumbnails_optimized_data
	 *
	 * @param array $thumbnails_optimized_data
	 *
	 * @return void
	 */
	public function set_thumbnails_optimized_data( $thumbnails_optimized_data ) {
		$this->thumbnails_optimized_data = $thumbnails_optimized_data;
	}

	/**
	 * get_image_relative_path
	 *
	 * @return string
	 */
	public function get_image_relative_path() {
		return $this->image_relative_path;
	}

	/**
	 * set_image_relative_path
	 *
	 * @param string $image_relative_path
	 *
	 * @return void
	 */
	public function set_image_relative_path( $image_relative_path ) {
		$this->image_relative_path = $image_relative_path;
	}

	/**
	 * Возвращает путь к превьюшке относительно корня WP
	 *
	 * @return string
	 */
	public function get_image_thumbnail_relative_path() {
		$basename = wp_basename( $this->image_relative_path );
		$dir      = dirname( $this->image_relative_path );

		return $dir . '/thumbs/thumbs_' . $basename;
	}

	/**
	 * Возвращает абсолютный путь к изображению
	 *
	 * @return string
	 */
	public function get_image_absolute_path() {
		$relative_path = $this->get_image_relative_path();

		return wp_normalize_path( untrailingslashit( ABSPATH ) . $relative_path );
	}

	/**
	 * Возвращает абсолютный путь к превьюшке
	 *
	 * @return string
	 */
	public function get_image_thumbnail_absolute_path() {
		$relative_path = $this->get_image_thumbnail_relative_path();

		return wp_normalize_path( untrailingslashit( ABSPATH ) . $relative_path );
	}

	/**
	 * Возвращает URL изображения
	 *
	 * @return string
	 */
	public function get_image_url() {
		$relative_path = $this->get_image_relative_path();

		return home_url( $relative_path );
	}

	/**
	 * Возвращает URL изображения-превьюшки
	 *
	 * @return string
	 */
	public function get_image_thumbnail_url() {
		$relative_path = $this->get_image_thumbnail_relative_path();

		return home_url( $relative_path );
	}

	/**
	 * Возвращает WebP размер основного изображения
	 *
	 * @return int
	 */
	public function get_webp_main_size() {
		return $this->webp_main_size;
	}

	/**
	 * Устанавливает WebP размер основного изображения
	 *
	 * @param int $webp_main_size
	 *
	 * @return void
	 */
	public function set_webp_main_size( $webp_main_size ) {
		$this->webp_main_size = $webp_main_size;
	}
}
