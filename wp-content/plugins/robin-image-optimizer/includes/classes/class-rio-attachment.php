<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с WordPress attachment.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WIO_Attachment {

	/**
	 * @var int
	 */
	private $id;


	/**
	 * @var array meta-данные
	 */
	private $attachment_meta;

	/**
	 * @var array массив с данными о папке uploads
	 */
	private $wp_upload_dir;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var RIO_Process_Queue
	 */
	private $optimization_data;

	/**
	 * Инициализация аттачмента
	 *
	 * @param int         $attachment_id     Номер аттачмента из медиабиблиотеки
	 * @param array|false $attachment_meta   метаданные аттачмента. Ключи массива аналогичны функции wp_get_attachment_metadata
	 */
	public function __construct( $attachment_id, $attachment_meta = false ) {
		$this->id              = $attachment_id;
		$this->wp_upload_dir   = wp_upload_dir();
		$this->attachment_meta = $attachment_meta;

		if ( ! $attachment_meta ) {
			// some meta can be missing due to: https://wordpress.stackexchange.com/q/330174/149161
			$this->attachment_meta = wp_get_attachment_metadata( $this->id );
		}

		$this->set_paths();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 * @return bool
	 */
	public function isset_attachment_meta() {
		return $this->attachment_meta && isset( $this->attachment_meta['file'] );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 */
	public function set_paths() {
		if ( ! $this->isset_attachment_meta() ) {
			return;
		}

		$this->url  = trailingslashit( $this->wp_upload_dir['baseurl'] ) . $this->attachment_meta['file'];
		$this->path = wp_normalize_path( trailingslashit( $this->wp_upload_dir['basedir'] ) . $this->attachment_meta['file'] );
	}

	/**
	 * Актуализирует мета данные аттачмента и загружает актуальные мета данные и данные по оптимизации из базы.
	 */
	public function reload( $attachment_meta = [] ) {
		if ( empty( $attachment_meta ) ) {
			$attachment_meta = wp_get_attachment_metadata( $this->id );
		}
		$this->attachment_meta   = $attachment_meta;
		$this->optimization_data = new RIO_Process_Queue( [
			'object_id'   => $this->id,
			'object_name' => '',
			'item_type'   => 'attachment',
		] );
		$this->optimization_data->load();
		$this->set_paths();
	}

	/**
	 * Fallback to get attachment meta it can be empty when WordPress failed to create it or invocation
	 * of method was produced too soon.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 * @return bool
	 */
	public function regenerate_metadata() {
		if ( $this->isset_attachment_meta() ) {
			return true;
		}

		WRIO_Logger::info( sprintf( 'Try regenerate metadata for attachment #%d', $this->id ) );

		// Need to remove this filter, as it would start recursion
		remove_filter( 'wp_generate_attachment_metadata', 'WRIO_Media_Library::optimize_after_upload' );

		$file_path = get_attached_file( $this->id );

		if ( empty( $file_path ) ) {
			$attachment = get_post( $this->id );

			if ( empty( $attachment ) || 'attachment' !== $attachment->post_type ) {
				return false;
			}

			$file_path = wrio_convert_url_to_abs_path( $attachment->guid );
		}

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			WRIO_Logger::info( sprintf( 'Failed regenerate attachment meta data. Attachment file (%s) doesn\'t exists!', $file_path ) );

			return false;
		}

		$attachment_meta = wp_generate_attachment_metadata( $this->id, $file_path );

		if ( empty( $attachment_meta ) ) {
			WRIO_Logger::error( sprintf( 'Failed regenerate meta data for attachment file (%s).', $file_path ) );

			return false;
		}

		WRIO_Logger::debug( sprintf( 'Generated metadata: %s', var_export( $attachment_meta, true ) ) );

		# Updating metadata in database
		wp_update_attachment_metadata( $this->id, $attachment_meta );

		$this->reload( $attachment_meta );

		add_filter( 'wp_generate_attachment_metadata', 'WRIO_Media_Library::optimize_after_upload', 10, 2 );

		WRIO_Logger::info( sprintf( 'Finish regenerate metadata for attachment #%d!', $this->id ) );

		return true;
	}

	/**
	 * Добавляем сообщение в лог файл.
	 *
	 * @param string $message   Текст сообщения об ошибке.
	 */
	public function writeLog( $message ) {

		$char = "\t-> ";
		$nl   = PHP_EOL;

		$error = sprintf( 'Error to optimize attachment (ID: #%s). Message: "%s"', $this->id, trim( $message ) ) . $nl;
		$error .= $char . sprintf( "Attachment optimized? %s", ( $this->isOptimized() ? 'Yes' : 'No' ) ) . $nl;
		$error .= $char . sprintf( "Should be resized? %s", ( $this->isNeedResize() ? 'Yes' : 'No' ) ) . $nl;
		$error .= $char . sprintf( "Original size: %sx%s", $this->attachment_meta['width'], $this->attachment_meta['height'] ) . $nl;
		$error .= $char . sprintf( "Relative path: %s", $this->attachment_meta['file'] ) . $nl;
		$error .= $char . sprintf( "Server used: %s", WRIO_Plugin::app()->getPopulateOption( 'image_optimization_server', 'server_1' ) ) . $nl;

		if ( ! empty( $this->attachment_meta['sizes'] ) ) {
			$error .= $char . ' Additional sizes:' . $nl;
			foreach ( $this->attachment_meta['sizes'] as $size_type => $size_info ) {
				$error .= "\t" . $char . sprintf( 'Type: %s, size: %sx%s, MIME type: %s', $size_type, $size_info['width'], $size_info['height'], $size_info['mime-type'] ) . $nl;
			}
		}

		WRIO_Logger::error( $error );
	}

	/**
	 * Возвращает объект с информацией об оптимизации
	 *
	 * @return RIO_Process_Queue
	 */
	public function getOptimizationData() {
		if ( ! isset( $optimization_data ) ) {
			$this->optimization_data = new RIO_Process_Queue( [
				'object_id'   => $this->id,
				'object_name' => '',
				'item_type'   => 'attachment',
			] );
			$this->optimization_data->load();
		}

		return $this->optimization_data;
	}

	/**
	 * Оптимизация аттачмента.
	 *
	 * @param string $optimization_level   Уровень оптимизации изображения.
	 *
	 * @return array
	 */
	public function optimize( $optimization_level = '' ) {
		$optimize_results = [
			'original_size'  => 0,
			'optimized_size' => 0,
		];

		if ( empty( $optimization_level ) ) {
			$optimization_level = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level', 'normal' );
		}

		if ( $optimization_level == 'custom' ) {
			$custom_quality     = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level_custom', 100 );
			$optimization_level = intval( $custom_quality );
		}

		$optimization_data           = $this->getOptimizationData();
		$results                     = [
			'original_size'      => 0,
			'final_size'         => 0,
			'original_mime_type' => '',
			'final_mime_type'    => '',
		];
		$results['processing_level'] = $optimization_level;

		# The path may be empty because no metadata has been created for the image.
		# We should try to create image metadata again.
		if ( ! $this->isset_attachment_meta() ) {
			WRIO_Logger::warning( sprintf( 'Attachment #%d doesn\'t have metadata.', $this->id ) );

			$this->regenerate_metadata();
		}

		if ( empty( $this->path ) || ! file_exists( $this->path ) ) {
			$results['result_status'] = 'error';

			$error_message = __( 'Attachment cannot be optimized.', 'robin-image-optimizer' );

			if ( empty( $this->path ) ) {
				$error_message .= ' ' . sprintf( __( 'Attachment #%d doesn\'t have metadata, the image may be damaged.', 'robin-image-optimizer' ), $this->id );
			} else {
				$error_message .= ' ' . sprintf( __( 'File "(%s)" doesn\'t exist', 'robin-image-optimizer' ), $this->path );
			}

			$extra_data = [
				'error'     => 'path',
				'error_msg' => $error_message,
			];

			$results['extra_data'] = new RIO_Attachment_Extra_Data( $extra_data );
			$optimization_data->configure( $results );
			$optimization_data->save();

			WRIO_Logger::error( sprintf( 'Failed to find original attachment #%s located in %s. Skipping optimization. This may be caused due to bug in %s function, which returns false for attachment meta', $this->id, empty( $path ) ? '*empty path*' : $path, 'wp_get_attachment_metadata()' ) );

			return $optimize_results;
		}

		// сначала бекапим
		$is_image_backuped = $this->backup();

		if ( is_wp_error( $is_image_backuped ) ) {
			$error_msg = $is_image_backuped->get_error_message();
			$this->writeLog( $error_msg );

			$results['result_status'] = 'error';
			$extra_data               = [
				'error'     => 'backup',
				'error_msg' => 'Failed to backup',
			];
			$results['extra_data']    = new RIO_Attachment_Extra_Data( $extra_data );
			$optimization_data->configure( $results );
			$optimization_data->save();

			WRIO_Logger::error( sprintf( 'Failed to make backup of original attachment #%s. Skipping optimization.', $this->id ) );

			return $optimize_results;
		}

		$results['is_backed_up'] = $is_image_backuped;

		$original_main_size = filesize( $this->path );

		// если файл большой - изменяем размер
		if ( $this->isNeedResize() ) {
			$this->resize();
		}

		$image_processor = WIO_OptimizationTools::getImageProcessor();

		clearstatcache(); // на всякий случай очистим кеш файловой статистики

		$optimized_img_data = $image_processor->process( [
			'image_url'  => $this->get( 'url' ),
			'image_path' => $this->get( 'path' ),
			'quality'    => $image_processor->quality( $optimization_level ),
			'save_exif'  => WRIO_Plugin::app()->getPopulateOption( 'save_exif_data', false ),
		] );

		// проверяем на ошибку
		if ( is_wp_error( $optimized_img_data ) ) {
			$error_msg = $optimized_img_data->get_error_message();
			$this->writeLog( $error_msg );

			$results['result_status'] = 'error';

			$extra_data = [
				'error'     => 'optimization',
				'error_msg' => $error_msg,
			];

			$results['extra_data'] = new RIO_Attachment_Extra_Data( $extra_data );

			$optimization_data->configure( $results );
			$optimization_data->save();

			WRIO_Logger::error( sprintf( 'Failed to process (url: %s, path: %s, quality: %s) as error was returned: %s', $this->get( 'url' ), $this->get( 'path' ), $image_processor->quality( $optimization_level ), $error_msg ) );

			return $optimize_results;
		}

		$results['original_mime_type'] = '';
		$results['final_mime_type']    = '';

		// отложенная оптимизация
		if ( isset( $optimized_img_data['status'] ) && $optimized_img_data['status'] == 'processing' ) {
			$results['result_status'] = 'processing';
			$results['original_size'] = 0;
			$results['final_size']    = 0;

			$extra_data = [
				'main_optimized_data'       => $optimized_img_data,
				'thumbnails_optimized_data' => $this->optimizeImageSizes(),
			];

			$results['extra_data'] = new RIO_Attachment_Extra_Data( $extra_data );

			$optimization_data->configure( $results );
			$optimization_data->save();
			$optimize_results['processing'] = 1;

			return $optimize_results;
		}

		//скачиваем и заменяем главную картинку
		$image_downloaded = $this->replaceOriginalFile( $optimized_img_data );

		// некоторые провайдеры не отдают оптимизированный размер, поэтому после замены файла получаем его сами
		if ( ! $optimized_img_data['optimized_size'] ) {
			clearstatcache();
			$optimized_img_data['optimized_size'] = filesize( $this->get( 'path' ) );
		}

		// при отрицательной оптимизации ставим значение оригинала
		if ( $optimized_img_data['optimized_size'] > $original_main_size ) {
			$optimized_img_data['optimized_size'] = $original_main_size;
		}

		if ( $image_downloaded ) {
			//просчитываем статистику
			$optimize_results['original_size']  += $original_main_size;
			$optimize_results['optimized_size'] += $optimized_img_data['optimized_size'];
			$thumbnails_count                   = 0;

			// оптимизируем дополнительные размеры
			$optimized_img_sizes_data = $this->optimizeImageSizes();

			// добавляем к статистике данные по оптимизации доп размеров
			if ( ! empty( $optimized_img_sizes_data ) ) {
				$optimize_results['original_size']  += $optimized_img_sizes_data['original_size'];
				$optimize_results['optimized_size'] += $optimized_img_sizes_data['optimized_size'];
				$thumbnails_count                   = $optimized_img_sizes_data['thumbnails_count'];
			}

			$results['result_status'] = 'success';
			$results['final_size']    = $optimize_results['optimized_size'];
			$results['original_size'] = $optimize_results['original_size'];

			$extra_data = [
				'thumbnails_count'   => $thumbnails_count,
				'original_main_size' => $original_main_size,
			];

			$results['extra_data'] = new RIO_Attachment_Extra_Data( $extra_data );
			$mime_type             = '';

			if ( function_exists( 'wp_get_image_mime' ) ) {
				$mime_type = wp_get_image_mime( $this->get( 'path' ) );
			} else {
				WRIO_Logger::error( 'App is missing wp_get_image_mime() function, unable to get MIME type' );
			}

			$results['original_mime_type'] = $mime_type;
			$results['final_mime_type']    = $mime_type;
			$optimization_data->configure( $results );
		} else {
			$error_msg = 'Failed to get optimized image from remote server';
			$this->writeLog( $error_msg );

			$results['result_status'] = 'error';

			$extra_data = [
				'error'     => 'download',
				'error_msg' => $error_msg,
			];

			$results['extra_data'] = new RIO_Attachment_Extra_Data( $extra_data );
			$optimization_data->configure( $results );
		}

		$optimization_data->save();

		return $optimize_results;
	}

	/**
	 * Отложенная оптимизация аттачмента
	 *
	 * @return bool|array
	 */
	public function deferredOptimization() {
		$results = [
			'original_size'   => 0,
			'optimized_size'  => 0,
			'optimized_count' => 0,
			'processing'      => 1,
		];

		$image_processor   = WIO_OptimizationTools::getImageProcessor();
		$optimization_data = $this->getOptimizationData();

		if ( $optimization_data->get_result_status() != 'processing' ) {
			return false;
		}

		// проверяем главную картинку
		/**
		 * @var RIO_Attachment_Extra_Data $extra_data
		 */
		$extra_data          = $optimization_data->get_extra_data();
		$main_optimized_data = $extra_data->get_main_optimized_data();
		$main_image_url      = '';

		if ( ! $main_optimized_data['optimized_img_url'] ) {
			$main_image_url = $image_processor->checkDeferredOptimization( $main_optimized_data );
			if ( $main_image_url ) {
				$main_optimized_data['optimized_img_url'] = $main_image_url;
				$extra_data->set_main_optimized_data( $main_optimized_data );
			}
		}

		$thumbnails_processed = true;
		$thumbnails           = (array) $extra_data->get_thumbnails_optimized_data();
		$thumbnails           = json_decode( json_encode( $thumbnails ), true ); // рекурсивная конвертация объекта в массив

		if ( is_array( $thumbnails['thumbnails'] ) ) {
			foreach ( $thumbnails['thumbnails'] as &$thumbnail_optimized_data ) {
				if ( ! $thumbnail_optimized_data['optimized_img_url'] ) {
					$thumbnail_image_url = $image_processor->checkDeferredOptimization( $thumbnail_optimized_data );
					if ( $thumbnail_image_url ) {
						$thumbnail_optimized_data['optimized_img_url'] = $thumbnail_image_url;
					} else {
						$thumbnails_processed = false;
					}
				}
			}
			$extra_data->set_thumbnails_optimized_data( $thumbnails );
		}

		// когда все файлы получены - сохраняем и возвращаем результат
		if ( $main_image_url && $thumbnails_processed ) {
			$original_size      = 0;
			$optimized_size     = 0;
			$thumbnails_count   = 0;
			$original_main_size = filesize( $this->get( 'path' ) );
			$original_size      = $original_size + $original_main_size;

			$this->replaceOriginalFile( [
				'optimized_img_url' => $main_image_url,
			] );

			clearstatcache();

			$optimized_main_size = filesize( $this->get( 'path' ) );

			// при отрицательной оптимизации ставим значение оригинала
			if ( $optimized_main_size > $original_main_size ) {
				$optimized_main_size = $original_main_size;
			}

			$optimized_size = $optimized_size + $optimized_main_size;

			if ( is_array( $thumbnails['thumbnails'] ) ) {
				foreach ( $thumbnails['thumbnails'] as $thumbnail_size => $thumbnail ) {
					$thumbnail_file          = $this->getImageSizePath( $thumbnail_size );
					$original_thumbnail_size = filesize( $thumbnail_file );
					$original_size           = $original_size + $original_thumbnail_size;

					$this->replaceOriginalFile( [
						'optimized_img_url' => $thumbnail['optimized_img_url'],
					], $thumbnail_size );

					clearstatcache();

					$optimized_thumbnail_size = filesize( $thumbnail_file );

					// при отрицательной оптимизации ставим значение оригинала
					if ( $optimized_thumbnail_size > $original_thumbnail_size ) {
						$optimized_thumbnail_size = $original_thumbnail_size;
					}

					$optimized_size = $optimized_size + $optimized_thumbnail_size;

					$thumbnails_count ++;
				}
			}

			$mime_type = '';
			if ( function_exists( 'wp_get_image_mime' ) ) {
				$mime_type = wp_get_image_mime( $this->get( 'path' ) );
			}

			$optimization_data->configure( [
				'final_size'         => $optimized_size,
				'original_size'      => $original_size,
				'result_status'      => 'success',
				'original_mime_type' => $mime_type,
				'final_mime_type'    => $mime_type,
			] );

			$extra_data->set_original_main_size( $original_main_size );
			$extra_data->set_thumbnails_count( $thumbnails_count );

			// удаляем промежуточные данные
			$extra_data->set_main_optimized_data( null );
			$extra_data->set_thumbnails_optimized_data( null );
			$extra_data->set_main_optimized_data( null );

			$results['optimized_count'] = 1;
			$results['original_size']   = $original_size;
			$results['optimized_size']  = $optimized_size;

			unset( $results['processing'] );
		}
		$optimization_data->set_extra_data( $extra_data );
		$optimization_data->save();

		return $results;
	}

	/**
	 * Метод проверяет, оптимизирован ли аттачмент
	 *
	 * @return bool
	 */
	public function isOptimized() {
		$optimization_data = $this->getOptimizationData();
		if ( $optimization_data->is_optimized() ) {
			return true;
		}

		return false;
	}

	/**
	 * Возвращает все размеры аттачмента, которые нужно оптимизировать
	 *
	 * @return array|false
	 */
	public function getAllowedSizes() {
		$allowed_sizes = WRIO_Plugin::app()->getPopulateOption( 'allowed_sizes_thumbnail', 'thumbnail,medium' );

		if ( ! $allowed_sizes ) {
			return false;
		}

		$allowed_sizes = explode( ',', $allowed_sizes );

		return $allowed_sizes;
	}

	/**
	 * Оптимизация других размеров аттачмента.
	 *
	 * @return array
	 */
	public function optimizeImageSizes() {
		$allowed_sizes = $this->getAllowedSizes();

		if ( $allowed_sizes === false ) {
			return [];
		}

		$image_processor = WIO_OptimizationTools::getImageProcessor();
		$quality         = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level', 'normal' );

		if ( $quality == 'custom' ) {
			$custom_quality = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level_custom', 100 );
			$quality        = intval( $custom_quality );
		}

		$exif = WRIO_Plugin::app()->getPopulateOption( 'save_exif_data', false );

		$original_size   = 0;
		$optimized_size  = 0;
		$errors_count    = 0;
		$optimized_count = 0;
		$thumbnails      = [];

		foreach ( $allowed_sizes as $image_size ) {
			$url  = $this->getImageSizeUrl( $image_size );
			$path = $this->getImageSizePath( $image_size );

			if ( ! $url || ! $path ) {
				continue;
			}

			$original_file_size = 0;

			if ( is_file( $path ) ) {
				$original_file_size = filesize( $path );
			}

			$optimized_img_data = $image_processor->process( [
				'image_url'  => $url,
				'image_path' => $path,
				'quality'    => $image_processor->quality( $quality ),
				'save_exif'  => $exif,
			] );
			// проверяем на ошибку
			if ( is_wp_error( $optimized_img_data ) ) {
				$errors_count ++;
			} else {
				//скачиваем и заменяем картинку
				$this->replaceOriginalFile( $optimized_img_data, $image_size );
				// некоторые провайдеры не отдают оптимизированный размер, поэтому после замены файла получаем его сами
				if ( ! $optimized_img_data['optimized_size'] ) {
					clearstatcache();
					$optimized_img_data['optimized_size'] = filesize( $path );
				}
				if ( ! $optimized_img_data['src_size'] ) {
					$optimized_img_data['src_size'] = $original_file_size;
				}

				// при отрицательной оптимизации ставим значение оригинала
				if ( $optimized_img_data['optimized_size'] > $original_file_size ) {
					$optimized_img_data['optimized_size'] = $original_file_size;
				}

				$thumbnails[ $image_size ] = $optimized_img_data;

				//просчитываем статистику
				$original_size  += $optimized_img_data['src_size'];
				$optimized_size += $optimized_img_data['optimized_size'];
				$optimized_count ++;
			}
		}

		return [
			'errors_count'     => $errors_count,
			'original_size'    => $original_size,
			'optimized_size'   => $optimized_size,
			'thumbnails_count' => $optimized_count,
			'thumbnails'       => $thumbnails,
		];
	}

	/**
	 * Возвращает путь.
	 *
	 * @param string $image_size   Размер(thumbnail, medium ... )
	 *
	 * @return string
	 */
	public function getPath( $image_size = '' ) {

		if ( empty( $image_size ) ) {
			$path = $this->path;
		} else {
			$path = $this->getImageSizePath( $image_size );
		}

		return $path;
	}

	/**
	 * Заменяет оригинальный файл на оптимизированный.
	 *
	 * @param array  $optimized_img_data   Hезультат оптимизации ввиде массива данных.
	 * @param string $image_size           Размер (thumbnail, medium ... )
	 *
	 * @return bool
	 */
	public function replaceOriginalFile( $optimized_img_data, $image_size = '' ) {

		$optimized_img_url = $optimized_img_data['optimized_img_url'];

		if ( isset( $optimized_img_data['not_need_download'] ) && (bool) $optimized_img_data['not_need_download'] ) {
			$optimized_file = $optimized_img_url;
		} else {
			$optimized_file = $this->remoteDownloadImage( $optimized_img_url );
		}

		if ( empty( $optimized_file ) ) {
			WRIO_Logger::error( sprintf( 'Unable to replace original image with new as failed to download %s', $optimized_img_url ) );

			return false;
		}

		if ( isset( $optimized_img_data['not_need_replace'] ) && $optimized_img_data['not_need_replace'] ) {
			// если картинка уже оптимизирована и провайдер её не может уменьшить - он может вернуть положительный ответ, но без самой картинки. В таком случае ничего заменять не надо
			return true;
		}

		$attachment_size_path = $this->getPath( $image_size );

		if ( ! is_file( $attachment_size_path ) ) {
			return false;
		}

		$bytes = @file_put_contents( $attachment_size_path, $optimized_file );

		if ( $bytes === false ) {
			WRIO_Logger::error( sprintf( 'Failed to put new image\'s %s content to %s as file_put_contents() failed', $optimized_img_url, $attachment_size_path ) );

			return false;
		}

		return true;
	}

	/**
	 * Скачивание изображения с удалённого сервера
	 *
	 * @param string $url
	 *
	 * @return string|null Image content on success, NULL on failure.
	 */
	protected function remoteDownloadImage( $url ) {

		if ( ! function_exists( 'curl_version' ) ) {
			$content = @file_get_contents( $url );

			if ( $content === false ) {
				WRIO_Logger::error( sprintf( 'Failed to get content of "%s" using file_get_contents()', $url ) );

				return null;
			}

			return $content;
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_URL, $url );

		$image_body = curl_exec( $ch );
		$http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( $http_code != '200' ) {
			$image_body = false;
		}
		curl_close( $ch );

		if ( $image_body === false ) {
			WRIO_Logger::error( sprintf( 'Failed to get content of "%s" using curl_exec()', $url ) );

			return null;
		}

		return $image_body;
	}

	/**
	 * Возвращает свойство аттачмента
	 *
	 * @param string $property   имя свойства
	 *
	 * @return mixed
	 */
	public function get( $property ) {
		if ( isset( $this->$property ) ) {
			return $this->$property;
		}

		return false;
	}

	/**
	 * Возвращает URL изображения по указанному размеру
	 *
	 * @param string $size   - размер изображения(thumbnail,medium,large...)
	 *
	 * @return string|null
	 */
	public function getImageSizeUrl( $size = 'thumbnail' ) {
		if ( ! isset( $this->attachment_meta['sizes'][ $size ] ) ) {
			return null;
		}

		$file = $this->attachment_meta['sizes'][ $size ]['file'];
		$url  = str_replace( wp_basename( $this->url ), $file, $this->url );

		return $url;
	}

	/**
	 * Возвращает путь к изображению по указанному размеру.
	 *
	 * @param string $size   Размер изображения (thumbnail, medium, large ...)
	 *
	 * @return string Путь до изображения.
	 */
	public function getImageSizePath( $size = 'thumbnail' ) {
		if ( ! isset( $this->attachment_meta['sizes'][ $size ] ) ) {
			return null;
		}

		$file = $this->attachment_meta['sizes'][ $size ]['file'];
		$path = str_replace( wp_basename( $this->path ), $file, $this->path );

		return $path;
	}

	/**
	 * Проверка необходимости делать изменение размера.
	 *
	 * @return bool
	 */
	protected function isNeedResize() {
		$resize_large_images = WRIO_Plugin::app()->getPopulateOption( 'resize_larger', true );

		if ( ! $resize_large_images ) {
			return false;
		}

		$resize_larger_w = (int) WRIO_Plugin::app()->getPopulateOption( 'resize_larger_w', 1600 );
		$resize_larger_h = (int) WRIO_Plugin::app()->getPopulateOption( 'resize_larger_h', 1600 );

		if ( ! $resize_larger_w && ! $resize_larger_h ) {
			return false;
		}

		// если ширина и высота установлены и > 0
		if ( $this->attachment_meta['width'] >= $this->attachment_meta['height'] ) {
			$larger_side        = $this->attachment_meta['width'];
			$resize_larger_side = $resize_larger_w;
		} else {
			$larger_side        = $this->attachment_meta['height'];
			$resize_larger_side = $resize_larger_h;
		}
		// если ширина 0, то рисайзим по высоте
		if ( ! $resize_larger_w ) {
			$resize_larger_side = $resize_larger_h;
			$larger_side        = $this->attachment_meta['height'];
		}
		// если высота 0, то рисайзим по ширине
		if ( ! $resize_larger_h ) {
			$resize_larger_side = $resize_larger_w;
			$larger_side        = $this->attachment_meta['width'];
		}
		// если большая сторона картинки меньше, чем задано в настройках, то не рисайзим.
		if ( $larger_side <= $resize_larger_side ) {
			return false;
		}

		return true;
	}

	/**
	 * Возвращает метаданные аттачмента
	 *
	 * @return array
	 */
	public function getMetaData() {
		return $this->attachment_meta;
	}

	/**
	 * Изменяет размер изображения до заданного в настройках размера.
	 *
	 * @return bool
	 */
	protected function resize() {
		$resize_larger_h = (int) WRIO_Plugin::app()->getPopulateOption( 'resize_larger_h', 1600 );
		$resize_larger_w = (int) WRIO_Plugin::app()->getPopulateOption( 'resize_larger_w', 1600 );

		$image = wp_get_image_editor( $this->path );

		if ( is_wp_error( $image ) ) {
			WRIO_Logger::error( sprintf( 'Failed to get image edit via wp_get_image_editor(), error: "%s"', $image->get_error_message() ) );

			return false;
		}

		$current_size = $image->get_size();
		$new_width    = 0;
		$new_height   = 0;

		// если обе стороны заданы
		if ( $resize_larger_h && $resize_larger_w ) {
			// определяем большую сторону и по ней маштабируем
			if ( $current_size['width'] >= $current_size['height'] ) {
				$new_width  = $resize_larger_w;
				$new_height = round( $current_size['height'] * $new_width / $current_size['width'] );
			} else {
				$new_height = $resize_larger_h;
				$new_width  = round( $current_size['width'] * $new_height / $current_size['height'] );
			}
		} else {
			// если задана одна из сторон
			if ( ! $resize_larger_w ) {
				// если ширина 0, то рисайзим по высоте
				$new_height = $resize_larger_h;
				$new_width  = round( $current_size['width'] * $new_height / $current_size['height'] );
			}
			if ( ! $resize_larger_h ) {
				// если высота 0, то рисайзим по ширине
				$new_width  = $resize_larger_w;
				$new_height = round( $current_size['height'] * $new_width / $current_size['width'] );
			}
		}

		$nl          = PHP_EOL;
		$log_message = sprintf( "\tResize from: %sx%s to %sx%s", $current_size['width'], $current_size['height'], $new_width, $new_height ) . $nl;
		$log_message .= sprintf( "\tLarger resize from %sx%s", $resize_larger_w, $resize_larger_h ) . $nl;
		$log_message .= sprintf( "\tAbsolute path: %s", $this->path ) . $nl;

		$resize_result = $image->resize( $new_width, $new_height, false );

		if ( is_wp_error( $resize_result ) ) {
			$this->writeLog( sprintf( 'Resize error: %s. Details: %s', $resize_result->get_error_messages(), $log_message ) );

			return false;
		}

		$save_result = $image->save( $this->path );

		if ( is_wp_error( $save_result ) ) {
			$this->writeLog( sprintf( 'Failed to save resized error in db: %s, Details: %s', $save_result->get_error_messages(), $log_message ) );

			return false;
		}

		$this->attachment_meta['width']      = $new_width;
		$this->attachment_meta['height']     = $new_height;
		$this->attachment_meta['old_width']  = $current_size['width'];
		$this->attachment_meta['old_height'] = $current_size['height'];

		wp_update_attachment_metadata( $this->id, $this->attachment_meta );

		return true;
	}

	/**
	 * Делает резервную копию
	 *
	 * @return true|WP_Error
	 */
	protected function backup() {
		$backup = WIO_Backup::get_instance();

		return $backup->backupAttachment( $this );
	}

	/**
	 * Восстанавливает файлы из резервной копии
	 *
	 * @return true|WP_Error
	 */
	public function restore() {
		$backup = WIO_Backup::get_instance();

		return $backup->restoreAttachment( $this );
	}
}
