<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для оптимизации изображений через API сервиса Resmush.
 *
 * @see https://resmush.it/api
 * @author Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version 1.0
 */
class WIO_Image_Processor_Resmush extends WIO_Image_Processor_Abstract {
	
	/**
	 * @var string
	 */
	protected $api_url = 'http://api.resmush.it/ws.php';
	
	/**
	 * @var string Имя сервера
	 */
	protected $server_name = 'server_1';
	
	/**
	 * Оптимизация изображения
	 *
	 * @param array $params входные параметры оптимизации изображения
	 *
	 * @return array|WP_Error {
	 *      Результаты оптимизации
	 *
	 *      {type} string $optimized_img_url УРЛ оптимизированного изображения на сервере оптимизации
	 *      {type} int $src_size размер исходного изображения в байтах
	 *      {type} int $optimized_size размер оптимизированного изображения в байтах
	 *      {type} int $optimized_percent На сколько процентов уменьшилось изображение
	 * }
	 */
	public function process( $settings ) {
		
		$settings = wp_parse_args( $settings, array(
			'image_url' => '',
			'quality'   => 100,
			'save_exif' => false,
		) );
		
		$query_args = array(
			'qlty' => $settings['quality'],
		);
		
		if ( $settings['save_exif'] ) {
			$query_args['exif'] = true;
		}
		
		$file = wp_normalize_path( $settings['image_path'] );
		
		if ( ! file_exists( $file ) ) {
			return new WP_Error( 'http_request_failed', sprintf( "File %s isn't exists.", $file ) );
		}
		
		WRIO_Logger::info( sprintf( "Preparing to upload a file (%s) to a remote server (%s).", $settings['image_path'], $this->server_name ) );
		
		$boundary = wp_generate_password( 24 ); // Just a random string, use something better than wp_generate_password() though.
		$headers  = array(
			'content-type' => 'multipart/form-data; boundary=' . $boundary
		);
		
		$payload = '';
		
		// First, add the standard POST fields:
		foreach ( $query_args as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
			$payload .= $value;
			$payload .= "\r\n";
		}
		// Upload the file
		if ( $file ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="files"; filename="' . basename( $file ) . '"' . "\r\n";
			//$payload .= 'Content-Type: image/jpeg' . "\r\n"; // If you know the mime-type
			$payload .= "\r\n";
			$payload .= @file_get_contents( $file );
			$payload .= "\r\n";
		}
		
		$payload .= '--' . $boundary . '--';
		
		$response = $this->request( 'POST', $this->api_url, $payload, $headers );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$response = @json_decode( $response );
			
			if ( isset( $response->error ) ) {
				return new WP_Error( 'http_request_failed', $response->error_long );
			} else {
				$optimized_image_data = array(
					'optimized_img_url' => $response->dest,
					'src_size'          => $response->src_size,
					'optimized_size'    => $response->dest_size,
					'optimized_percent' => $response->percent
				);
			}
		}
		
		WRIO_Logger::info( sprintf( "File successfully uploaded to remote server (%s).", $this->server_name ) );
		
		return $optimized_image_data;
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
