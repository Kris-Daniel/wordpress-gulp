<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для оптимизации изображений через API сервиса webcraftic.com.
 *
 * @author Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version 1.0
 */
class WIO_Image_Processor_Webcraftic extends WIO_Image_Processor_Abstract {
	
	/**
	 * @var string
	 */
	protected $api_url;
	
	/**
	 * @var string Имя сервера
	 */
	protected $server_name = 'server_3';
	
	/**
	 * Инициализация
	 *
	 * @return void
	 */
	public function __construct() {
		// Получаем ссылку на сервер 3
		$this->api_url = wrio_get_server_url( 'server_3' );
	}
	
	/**
	 * Оптимизация изображения
	 *
	 * @param array $settings входные параметры оптимизации изображения
	 *
	 * @return array|WP_Error {
	 *      Результаты оптимизации
	 *
	 *      {type} string $optimized_img_url УРЛ оптимизированного изображения на сервере оптимизации
	 *      {type} int $src_size размер исходного изображения в байтах
	 *      {type} int $optimized_size размер оптимизированного изображения в байтах
	 *      {type} int $optimized_percent На сколько процентов уменьшилось изображение
	 *      {type} bool $not_need_download Изображение не надо скачивать
	 * }
	 */
	public function process( $settings ) {
		
		$default_params = array(
			'image_url' => '',
			'quality'   => 100,
			'save_exif' => false,
		);
		
		$settings = wp_parse_args( $settings, $default_params );
		
		$query_args = array(
			'quality' => $settings['quality'],
		);
		
		if ( ! $settings['save_exif'] ) {
			$query_args['strip'] = 'info';
		}
		
		// Create a temporary image with a unique name.
		$backup          = WIO_Backup::get_instance();
		$temp_attachment = $backup->createTempAttachment( $settings['image_path'] );
		
		if ( is_wp_error( $temp_attachment ) ) {
			return new WP_Error( 'create_temp_attachment_error', __( 'It is not possible to create a temporary file. Throw error ' . $temp_attachment->get_error_message(), 'robin-image-optimizer' ) );
		}
		
		WRIO_Logger::info( sprintf( "Preparing to upload a file (%s) to a remote server (%s).", $settings['image_path'], $this->server_name ) );
		
		$img_url = $temp_attachment['image_url'];
		
		$img_url = str_replace( array( 'http://', 'https://' ), '', $img_url );
		$img_url = add_query_arg( $query_args, $this->api_url . '/' . $img_url );
		
		$responce = $this->request( 'GET', $img_url );
		
		// Delete temporary image
		if ( file_exists( $temp_attachment['image_path'] ) && ! unlink( $temp_attachment['image_path'] ) ) {
			WRIO_Logger::error( sprintf( "Failed to delete temporary file %s", $temp_attachment['image_path'] ) );
		}
		
		if ( is_wp_error( $responce ) ) {
			return $responce;
		}
		
		WRIO_Logger::info( sprintf( "File successfully uploaded to remote server (%s).", $this->server_name ) );
		
		return array(
			'optimized_img_url' => $responce,
			'src_size'          => 0,
			'optimized_size'    => 0,
			'optimized_percent' => 0,
			'not_need_download' => true,
		);
	}
	
	/**
	 * Качество изображения
	 * Метод конвертирует качество из настроек плагина в формат сервиса resmush
	 *
	 * @param mixed $quality качество
	 *
	 * @return int
	 */
	public function quality( $quality = 100 ) {
		if ( is_numeric( $quality ) ) {
			if ( $quality >= 1 && $quality <= 100 ) {
				return $quality;
			}
		}
		if ( $quality == 'normal' ) {
			return 90;
		}
		if ( $quality == 'aggresive' ) {
			return 75;
		}
		if ( $quality == 'ultra' ) {
			return 50;
		}
		
		return 100;
	}
}
