<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для оптимизации изображений через API сервиса clearfy.pro.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WIO_Image_Processor_Clearfy1 extends WIO_Image_Processor_Abstract {

	/**
	 * @var string
	 */
	protected $api_url;

	/**
	 * @var string Имя сервера
	 */
	protected $server_name = 'server_4';

	public function __construct() {
		$this->api_url = wrio_get_server_url( 'server_4' );
	}

	/**
	 * Оптимизация изображения
	 *
	 * @param array $params   входные параметры оптимизации изображения
	 *
	 * @return array|WP_Error {
	 *      Результаты оптимизации
	 *
	 *      {type} string $optimized_img_url УРЛ оптимизированного изображения на сервере оптимизации
	 *      {type} int $src_size размер исходного изображения в байтах
	 *      {type} int $optimized_size размер оптимизированного изображения в байтах
	 *      {type} int $optimized_percent На сколько процентов уменьшилось изображение
	 *      {type} string $session_id Идентификатор сессии. Для отложенной оптимизации.
	 *      {type} string $file_id Идентификатор файла. Для отложенной оптимизации.
	 *      {type} bool $not_need_replace Изображение не надо заменять.
	 *      {type} bool $not_need_download Изображение не надо скачивать.
	 *      {type} string $status Статус оптимизации
	 *      {type} string $server Имя сервера оптимизации
	 * }
	 */
	public function process( $settings ) {

		$default_params = [
			'image_url' => '',
			'quality'   => 100,
			'save_exif' => false,
		];
		$settings       = wp_parse_args( $settings, $default_params );

		$session_id = $this->generateRandomString( 16 );
		$file_id    = 'o_' . $this->generateRandomString( 28 );
		$upload_url = $this->get_endpoint_url( 'upload', $session_id );

		if ( ! function_exists( 'curl_version' ) ) {
			return new WP_Error( 'http_request_failed', "For Robin image optimizer to work, you need to install php extension [curl]." );
		}

		WRIO_Logger::info( sprintf( "Preparing to upload a file (%s) to a remote server (%s).", $settings['image_path'], $this->server_name ) );

		// todo: need to use wp_remote*, see https://webcraftic.atlassian.net/browse/RIO-71
		$filename = $settings['image_path'];

		if ( ! class_exists( 'finfo' ) ) {
			WRIO_Logger::error( 'For Robin image optimizer to work, you need to install php extension [php_fileinfo].' );

			return new WP_Error( 'http_request_failed', "For Robin image optimizer to work, you need to install php extension [php_fileinfo]." );
		}

		$finfo    = new \finfo( FILEINFO_MIME_TYPE );
		$mimetype = $finfo->file( $filename );

		$ch    = curl_init( $upload_url );
		$cfile = curl_file_create( $filename, $mimetype, basename( $filename ) );
		$data  = [ 'file' => $cfile, 'name' => basename( $filename ), 'id' => $file_id ];

		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec( $ch );
		$r        = curl_getinfo( $ch );

		if ( $r["http_code"] != 200 ) {
			WRIO_Logger::error( sprintf( 'Failed to get content of URL: %s as wp_remote_request() responded Http error (%s).', $upload_url, $r["http_code"] ) );

			return new WP_Error( 'http_request_failed', sprintf( "Server responded an Http error %s", $r["http_code"] ) );
		}

		$compress_url      = $this->get_endpoint_url( 'compress', $session_id, $file_id, [ 'quality' => $settings['quality'] ] );
		$compress_response = $this->request( 'GET', $compress_url );

		if ( is_wp_error( $compress_response ) ) {
			return $compress_response;
		}

		WRIO_Logger::info( sprintf( "File successfully uploaded to remote server (%s).", $this->server_name ) );

		$optimized_image_data = [
			'optimized_img_url' => '',
			'src_size'          => 0,
			'optimized_size'    => 0,
			'optimized_percent' => 0,
			'session_id'        => $session_id,
			'file_id'           => $file_id,
			'not_need_replace'  => true,
			'not_need_download' => true,
			'status'            => 'processing', // отложенная оптимизация
			'server'            => $this->server_name,
		];

		return $optimized_image_data;
	}


	/**
	 * Проверка отложенной оптимизации изображения
	 *
	 * @param array $optimized_data   {
	 *                                Параметры отложенной оптимизации
	 *
	 *      {type} string $server Имя сервера оптимизации
	 *      {type} string $session_id Идентификатор сессии
	 *      {type} string $file_id Уникальный идентификатор файла. Генерируется сервером оптимизации.
	 * }
	 *
	 * @return bool|array
	 */
	public function checkDeferredOptimization( $optimized_data ) {

		$status_url = $this->get_endpoint_url( 'status', $optimized_data['session_id'], $optimized_data['file_id'] );
		$response   = $this->request( 'GET', $status_url );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response = @json_decode( $response );

		if ( isset( $response->compress_progress ) && $response->compress_progress == 100 ) {
			$optimized_url = $this->api_url . $response->compressed_url;

			return $optimized_url;
		}

		return false;
	}

	/**
	 * Проверка данных для отложенной оптимизации
	 * Проверяет наличие необходимых параметров и соответствие серверу
	 *
	 * @param array $optimized_data   {
	 *                                Параметры отложенной оптимизации
	 *
	 *      {type} string $server Имя сервера оптимизации
	 *      {type} string $session_id Идентификатор сессии
	 *      {type} string $file_id Уникальный идентификатор файла. Генерируется сервером оптимизации.
	 * }
	 *
	 * @return bool
	 */
	public function validateDeferredData( $optimized_data ) {
		if ( ! isset( $optimized_data['server'] ) ) {
			return false;
		}
		if ( $optimized_data['server'] != $this->server_name ) {
			return false;
		}
		if ( ! isset( $optimized_data['session_id'] ) or ! isset( $optimized_data['file_id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Качество изображения
	 * Метод конвертирует качество из настроек плагина в формат сервиса resmush
	 *
	 * @param mixed $quality   качество
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

	/**
	 * Генерирует случайную строку указанной длины
	 *
	 * @param int $length   Длина строки
	 *
	 * @return string
	 */
	public function generateRandomString( $length = 10 ) {
		$characters       = '0123456789abcdefghiklmnopqrstuvwxyz';
		$charactersLength = strlen( $characters );
		$randomString     = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
		}

		return $randomString;
	}

	/**
	 * Использует ли сервер отложенную оптимизацию
	 *
	 * @return bool
	 */
	public function isDeferred() {
		return true;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.1
	 *
	 * @param string $file_id
	 * @param array  $agrs
	 *
	 * @param string $ednpoint
	 * @param string $session_id
	 *
	 * @return string|null
	 */
	private function get_endpoint_url( $ednpoint, $session_id, $file_id = null, array $agrs = [] ) {
		$url = $this->api_url . '/' . $ednpoint . '/' . $session_id;

		if ( ! empty( $file_id ) ) {
			$url .= '/' . $file_id;
		}

		$parse_args = wp_parse_args( $agrs, [
			'rnd' => '0.' . rand( 11111111, 99999999 )
		] );

		return add_query_arg( $parse_args, $url );
	}
}
