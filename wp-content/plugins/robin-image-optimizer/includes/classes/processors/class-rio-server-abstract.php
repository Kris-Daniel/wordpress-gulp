<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Базовый класс для обработки изображений через API сторонних сервисов.
 *
 * todo: add usage example
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
abstract class WIO_Image_Processor_Abstract {

	/**
	 * Оптимизация изображения
	 *
	 * @param array $params   {
	 *                        Параметры оптимизации изображения. Разные сервера могут принимать разные наборы параметров. Ниже список всех возможных.
	 *
	 *      {type} string $image_url УРЛ изображения
	 *      {type} string $image_path Путь к файлу изображения
	 *      {type} string $quality Качество
	 *      {type} string $save_exif Сохранять ли EXIF данные
	 * }
	 *
	 * @return array|WP_Error {
	 *      Результаты оптимизации. Основные параметры. Другие параметры зависят от конкретной раелизации.
	 *
	 *      {type} string $optimized_img_url УРЛ оптимизированного изображения на сервере оптимизации
	 *      {type} int $src_size размер исходного изображения в байтах
	 *      {type} int $optimized_size размер оптимизированного изображения в байтах
	 *      {type} int $optimized_percent На сколько процентов уменьшилось изображение
	 *      {type} bool $not_need_replace Изображение не надо заменять.
	 *      {type} bool $not_need_download Изображение не надо скачивать.
	 * }
	 */
	abstract function process( $params );

	/**
	 * Качество изображения
	 * Метод конвертирует качество из настроек плагина в формат сервиса оптимизации
	 *
	 * @param mixed $quality   качество
	 */
	abstract function quality( $quality );

	/**
	 * HTTP запрос к API стороннего сервиса.
	 *
	 * @param string            $type      POST|GET
	 * @param string            $url       URL для запроса
	 * @param array|string|null $body      Параметры запроса. По умолчанию: false.
	 * @param array             $headers   Дополнительные заголовки. По умолчанию: false.
	 *
	 * @return string|WP_Error
	 */
	protected function request( $type, $url, $body = null, array $headers = [] ) {
		$args = [
			'method'  => $type,
			'headers' => $headers,
			'body'    => $body,
			'timeout' => 150 // it make take some time for large images and slow Internet connections
		];

		$error_message = sprintf( 'Failed to get content of URL: %s as wp_remote_request()', $url );

		wp_raise_memory_limit( 'image' );
		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			WRIO_Logger::error( sprintf( '%s returned error (%s).', $error_message, $response->get_error_message() ) );

			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code !== 200 ) {
			WRIO_Logger::error( sprintf( '%s responded Http error (%s).', $error_message, $response_code ) );

			return new WP_Error( 'http_request_failed', sprintf( "Server responded an HTTP error %s", $response_code ) );
		}

		if ( empty( $response_body ) ) {
			WRIO_Logger::error( sprintf( '%s responded an empty request body.', $error_message ) );

			return new WP_Error( 'http_request_failed', "Server responded an empty request body." );
		}

		return $response_body;
	}

	/**
	 * HTTP запрос к API стороннего сервиса с использованием библиотеки CURL
	 *
	 * @param string      $url           URL для запроса
	 * @param array|false $post_fields   Параметры запроса. По умолчанию: false.
	 * @param array|false $headers       Дополнительные заголовки. По умолчанию: false.
	 *
	 * @return string
	 * todo: need to use wp_remote*, see https://webcraftic.atlassian.net/browse/RIO-71
	 * @throws Exception
	 */
	/*protected function curlRequest( $url, $post_fields = false, $headers = false ) {
		$ch      = curl_init();
		$timeout = 10;
		
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		
		if ( $post_fields ) {
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields );
		}
		
		if ( $headers ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		}
		
		$response = curl_exec( $ch );
		
		if ( curl_errno( $ch ) ) {
			throw new Exception( curl_error( $ch ), 'http_error' );
		}
		
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		
		if ( $http_code != 200 ) {
			throw new Exception( 'HTTP error code: ' . $http_code, 'http_error' );
		}
		
		curl_close( $ch );
		
		return $response;
	}*/

	/**
	 * Использует ли сервер отложенную оптимизацию
	 *
	 * @return bool
	 */
	public function isDeferred() {
		return false;
	}

	/**
	 * Проверка отложенной оптимизации изображения
	 *
	 * @param array $optimized_data   Параметры отложенной оптимизации. Набор параметров зависит от конкретной реализации
	 *
	 * @return bool|array
	 */
	public function checkDeferredOptimization( $optimized_data ) {
		return false;
	}

	/**
	 * Проверка данных для отложенной оптимизации.
	 *
	 * Проверяет наличие необходимых параметров и соответствие серверу.
	 *
	 * @param array $optimized_data   Параметры отложенной оптимизации. Набор параметров зависит от конкретной реализации
	 *
	 * @return bool
	 */
	public function validateDeferredData( $optimized_data ) {
		return false;
	}
}
