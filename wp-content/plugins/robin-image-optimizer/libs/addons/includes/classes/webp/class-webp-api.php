<?php

/**
 * Class WRIO_WebP_Api processing images from processing queue, sends them to API and saves locally.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class WRIO_WebP_Api {

	/**
	 * @var string API url.
	 */
	private $_api_url = 'http://142.93.91.206/';

	/**
	 * @var int|null Attachment ID.
	 */
	private $_models = null;

	/**
	 * @var null|int UNIX epoch when last request was processed.
	 */
	private $_last_request_tick = null;


	/**
	 * WRIO_WebP_Api constructor.
	 *
	 * @param RIO_Process_Queue[] $model   Item to be converted to WebP.
	 */
	public function __construct( $model ) {
		$this->_models = $model;
	}

	/**
	 * Process image queue based on provided attachment ID.
	 *
	 * When attachment has multiple thumbnails, all of them would be converted one after another.
	 *
	 * Notice: when there are no items queried for provided data, false would be returned.
	 *
	 * @return bool true on success execution, false on failure or missing item in queue.
	 */
	public function process_image_queue() {

		foreach ( $this->_models as $model ) {
			/**
			 * @var RIOP_WebP_Extra_Data $extra_data
			 */
			$extra_data = $model->get_extra_data();

			if ( $extra_data === null ) {
				continue;
			}

			$response = $this->request( $model );

			if ( $this->can_save( $response ) && $this->save_file( $response, $model ) ) {
				$this->update( $model );
			}
		}

		return true;
	}

	/**
	 * Request API
	 *
	 * @param RIO_Process_Queue $model   Queue model.
	 *
	 * @return array|bool|WP_Error
	 */
	public function request( $model ) {

		if ( $this->_last_request_tick === null ) {
			$this->_last_request_tick = time();
		} else {
			if ( is_int( $this->_last_request_tick ) && ( time() - $this->_last_request_tick ) < 1 ) {
				// Need to have some rest before calling REST :D to comply with API request limit
				sleep( 2 );
			}

			$this->_last_request_tick = time();
		}

		if ( ! wrio_is_license_activate() ) {
			WRIO_Logger::error( "Unable to get license to make proper request to the API" );

			return false;
		}

		$transient_string = md5( WRIO_Plugin::app()->getPrefix() . '_processing_image' . $model->get_item_hash() );

		$transient_value = get_transient( $transient_string );

		if ( is_numeric( $transient_value ) && (int) $transient_value === 1 ) {
			WRIO_Logger::info( sprintf( 'Skipping to wp_remote_get() as transient "%s" already exist. Usually it means that no request was returned yet', $transient_string ) );

			return false;
		}

		set_transient( $transient_string, 1 );

		$url = $this->_api_url . 'v1/image/convert?';

		$url .= http_build_query( [ 'format' => 'webp' ] );

		/**
		 * @var RIOP_WebP_Extra_Data $extra_data
		 */
		$extra_data = $model->get_extra_data();

		$multipartBoundary = '--------------------------' . microtime( true );

		$file_contents = file_get_contents( $extra_data->get_source_path() );

		$body = "--" . $multipartBoundary . "\r\n" . "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename( $extra_data->get_source_path() ) . "\"\r\n" . "Content-Type: " . $model->get_original_mime_type() . "\r\n\r\n" . $file_contents . "\r\n";

		$body .= "--" . $multipartBoundary . "--\r\n";

		$headers = [
			// should be base64 encoded, otherwise API would fail authentication
			'Authorization' => 'Bearer ' . base64_encode( wrio_get_license_key() ),
			'PluginId'      => wrio_get_freemius_plugin_id(),
			'Content-Type'  => 'multipart/form-data; boundary=' . $multipartBoundary,
		];

		$response = wp_remote_post( $url, [
			'timeout' => 60,
			'headers' => $headers,
			'body'    => $body,
		] );

		delete_transient( $transient_string );

		return $response;
	}

	/**
	 * Process response from API.
	 *
	 * @param array|WP_Error|false $response
	 *
	 * @return bool True means response image was successfully saved, false on failure.
	 */
	public function can_save( $response ) {
		\WRIO_Logger::info( 'WebP convertation: Checks to save a webp by response.' );
		//\WRIO_Logger::debug( var_export( $response, true ) );

		if ( is_wp_error( $response ) ) {
			WRIO_Logger::error( sprintf( 'Error response from API. Code: %s, error: %s', $response->get_error_code(), $response->get_error_message() ) );

			return false;
		}

		if ( false === $response ) {
			WRIO_Logger::error( 'Unknown response returned from API or it was not requested, failing to process response' );

			return false;
		}

		$content_disposition = wp_remote_retrieve_header( $response, 'content-disposition' );

		if ( 0 === strpos( $content_disposition, 'attachment;' ) ) {

			$body = wp_remote_retrieve_body( $response );

			if ( empty( $body ) ) {
				WRIO_Logger::error( 'Response returned content-disposition header as "attachment;", but empty body returned, failing to proceed' );

				return false;
			}

			\WRIO_Logger::info( 'WebP convertation: Image can be saved. ' );

			return true;
		}

		$response_text = wp_remote_retrieve_body( $response );

		if ( ! empty( $response_text ) ) {
			$response_json = json_decode( $response_text );

			if ( ! empty( $response_json ) ) {
				if ( isset( $response_json->error ) && ! empty( $response_json->error ) ) {
					WRIO_Logger::error( sprintf( 'Unable to convert attachment as API returned error: "%s"', $response_json->error ) );
				}

				if ( isset( $response_json->status ) && 401 === (int) $response_json->status ) {
					WRIO_Logger::error( sprintf( 'Error response from API. Code: %s, error: %s', $response_json->message, $response_json->code ) );
				}
			}
		}

		return false;
	}

	/**
	 * Save file from response.
	 *
	 * It is assumed that it was checked by can_save() method.
	 *
	 * @param array|WP_Error|false $response
	 * @param RIO_Process_Queue    $queue_model
	 *
	 * @return bool
	 * @see can_save() for further information.
	 *
	 */
	public function save_file( $response, $queue_model ) {

		try {
			$save_path = static::get_save_path( $queue_model );
		} catch( \Exception $exception ) {
			WRIO_Logger::error( sprintf( 'Unable to process response failed to get save path: "%s"', $exception->getMessage() ) );

			return false;
		}

		\WRIO_Logger::info( sprintf( 'WebP convertation: Try to save webp image in %s.', $save_path ) );

		$body = wp_remote_retrieve_body( $response );

		$file_saved = @file_put_contents( $save_path, $body );

		if ( ! $file_saved ) {
			/**
			 * @var $http_response WP_HTTP_Requests_Response
			 */
			$http_response = $response['http_response'];
			WRIO_Logger::error( sprintf( 'Failed to save file "%s" under %s with file_put_contents()', $save_path, $http_response->get_response_object()->url ) );

			return false;
		}

		\WRIO_Logger::info( 'WebP convertation: Image saved successfully!' );

		return true;
	}

	/**
	 * Update processing item data to finish its cycle.
	 *
	 * @param RIO_Process_Queue $queue_model   Queue model to be update.
	 *
	 * @return bool
	 */
	public function update( $queue_model ) {

		try {
			$save_path = static::get_save_path( $queue_model );
		} catch( \Exception $exception ) {
			WRIO_Logger::error( sprintf( 'Unable to update queue model #%s as of exception: %s', $queue_model->get_id(), $exception->getMessage() ) );

			return false;
		}

		$queue_model->result_status = RIO_Process_Queue::STATUS_SUCCESS;
		$queue_model->final_size    = filesize( $save_path );

		/**
		 * @var RIOP_WebP_Extra_Data $updated_extra_data
		 */
		$updated_extra_data = $queue_model->get_extra_data();
		$updated_extra_data->set_converted_src( $this->get_save_url( $queue_model ) );
		$updated_extra_data->set_converted_path( $save_path );

		$queue_model->extra_data = $updated_extra_data;

		/**
		 * Хук срабатывает после успешной конвертации в WebP
		 *
		 * @since 1.2.0
		 *
		 * @param RIO_Process_Queue $queue_model
		 *
		 */
		do_action( 'wbcr/rio/webp_success', $queue_model );

		return $queue_model->save();
	}

	/**
	 * Get complete save url.
	 *
	 * @param RIO_Process_Queue $queue_model   Instance of queue item.
	 *
	 * @return string
	 */
	public function get_save_url( $queue_model ) {
		/**
		 * @var $extra_data RIOP_WebP_Extra_Data
		 */
		$extra_data = $queue_model->get_extra_data();

		if ( empty( $extra_data ) ) {
			WRIO_Logger::error( sprintf( 'Unable to get extra data for queue item #%s', $queue_model->get_id() ) );

			return null;
		}

		$origin_file_name = wp_basename( $extra_data->get_source_src() );
		$webp_file_name   = trim( wp_basename( $extra_data->get_source_path() ) ) . '.webp';

		return str_replace( $origin_file_name, $webp_file_name, $extra_data->get_source_src() );
	}

	/**
	 * Get absolute save path.
	 *
	 * @param \RIO_Process_Queue $queue_model
	 *
	 * @return bool
	 * @throws Exception on failure to create missing directory
	 */
	public static function get_save_path( $queue_model ) {
		/**
		 * @var $extra_data RIOP_WebP_Extra_Data
		 */
		$extra_data = $queue_model->get_extra_data();

		if ( empty( $extra_data ) ) {
			WRIO_Logger::error( sprintf( 'Unable to get extra data for queue item #%s', $queue_model->get_id() ) );

			return null;
		}

		$path = dirname( $extra_data->get_source_path() );

		// Create DIR when does not exist
		if ( ! file_exists( $path ) ) {
			$message = sprintf( 'Failed to create directory %s with mode %s recursively', $path, 0755 );
			WRIO_Logger::error( $message );
			throw new \Exception( $message );
		}

		return trailingslashit( $path ) . trim( wp_basename( $extra_data->get_source_path() ) ) . '.webp';
	}
}
