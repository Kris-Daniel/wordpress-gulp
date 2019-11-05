<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для оптимизации изображений через API сервиса smushpro.wpmudev.org.
 *
 * @see           https://smushpro.wpmudev.org/
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WIO_Image_Processor_Smushpro extends WIO_Image_Processor_Abstract {

	/**
	 * @var string
	 */
	protected $api_url = 'smushpro.wpmudev.org/1.0/';

	/**
	 * @var string Имя сервера
	 */
	protected $server_name = 'server_2';

	/**
	 * Оптимизация изображения
	 *
	 * @param array $settings   входные параметры оптимизации изображения
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

		$default_params = [
			'image_url' => '',
			'save_exif' => false,
		];

		$settings = wp_parse_args( $settings, $default_params );

		$headers = [
			'accept'       => 'application/json',   // The API returns JSON.
			'content-type' => 'application/binary', // Set content type to binary.
		];

		if ( $settings['save_exif'] ) {
			$headers['exif'] = 'true';
		}

		$file = wp_normalize_path( $settings['image_path'] );

		if ( ! file_exists( $file ) ) {
			return new WP_Error( 'http_request_failed', sprintf( "File %s isn't exists.", $file ) );
		}

		WRIO_Logger::info( sprintf( "Preparing to upload a file (%s) to a remote server (%s).", $settings['image_path'], $this->server_name ) );

		$use_http = WRIO_Plugin::app()->getPopulateOption( 'use_http' );

		$api_url = ( $use_http ? 'http://' : 'https://' ) . $this->api_url;

		$file_data = file_get_contents( $file );

		$response = $this->request( 'POST', $api_url, $file_data, $headers );

		unset( $file );

		if ( is_wp_error( $response ) ) {
			$er_msg = $response->get_error_message();

			// Hostgator Issue.
			if ( ! empty( $er_msg ) && strpos( $er_msg, 'SSL CA cert' ) !== false ) {
				// Update DB for using http protocol.
				WRIO_Plugin::app()->updatePopulateOption( 'use_http', 1 );
			}

			unset( $response ); // Free memory.

			// Check for timeout error and suggest to filter timeout.
			if ( strpos( $er_msg, 'timed out' ) ) {
				return new WP_Error( 'api_error', __( "Skipped due to a timeout error. You can increase the request timeout to make sure Smush has enough time to process larger files.", 'robin-image-optimizer' ) );
			}

			// Handle error.
			/* translators: %s error message */

			return new WP_Error( 'api_error', sprintf( __( 'Error posting to API: %s', 'robin-image-optimizer' ), $er_msg ) );
		}

		$response = @json_decode( $response );

		if ( $response && true === $response->success ) {

			$image_data = isset( $response->data->image ) ? base64_decode( $response->data->image ) : false;

			$optimized_image_data = [
				'optimized_img_url' => $image_data,
				'src_size'          => $response->data->before_size,
				'optimized_size'    => $response->data->after_size,
				'optimized_percent' => $response->data->compression,
				'not_need_download' => true,
			];

			if ( ! $image_data ) {
				$optimized_image_data['not_need_replace'] = true;
			}

			WRIO_Logger::info( sprintf( "File successfully uploaded to remote server (%s).", $this->server_name ) );

			unset( $response ); // Free memory.

			return $optimized_image_data;
		}

		unset( $response ); // Free memory.

		return new WP_Error( 'api_error', __( "Image couldn't be smushed", 'robin-image-optimizer' ) );
	}

	/**
	 * Качество изображения
	 * Для этого провайдера оно не применяется
	 *
	 * @param mixed $quality   качество
	 *
	 * @return int
	 */
	public function quality( $quality = 100 ) {
		return 100;
	}
}
