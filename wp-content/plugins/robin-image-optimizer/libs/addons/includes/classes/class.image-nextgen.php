<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с nextgen image
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Image_Nextgen {

	/**
	 * @var int номер картинки в таблице nextgen
	 */
	private $id;

	/**
	 * @var string путь к картинке
	 */
	private $path;

	/**
	 * @var string путь к миниатюре
	 */
	private $thumbnail_path;

	/**
	 * @var string имя файла
	 */
	private $file;

	/**
	 * @var string имя файла миниатюры
	 */
	private $thumbnail_file;

	/**
	 * @var string УРЛ
	 */
	private $url;

	/**
	 * @var string УРЛ миниатюры
	 */
	private $thumbnail_url;

	/**
	 * @var array мета данные картинки
	 */
	private $meta;

	/**
	 * @var array мета данные галереи
	 */
	private $gallery_meta;

	/**
	 * @var RIO_Process_Queue данные по оптимизации
	 */
	private $optimization_data = null;

	/**
	 * Инициализация картинки из nextgen gallery
	 *
	 * @param int   $image_id     номер картинки в таблице nextgen
	 * @param array $image_data   метаданные картинки
	 */
	public function __construct( $image_id, $image_data = false ) {
		global $wpdb;

		$this->id = $image_id;

		if ( $image_data ) {
			$this->meta = $image_data;
		} else {
			$this->meta = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}ngg_pictures WHERE pid = " . intval( $image_id ) );
		}

		$this->file           = $this->meta->filename;
		$this->thumbnail_file = 'thumbs_' . $this->meta->filename;

		global $wpdb;
		$this->gallery_meta = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}ngg_gallery WHERE gid = " . intval( $this->meta->galleryid ) );

		$this->path           = wp_normalize_path( ABSPATH . $this->gallery_meta->path . $this->meta->filename );
		$this->thumbnail_path = wp_normalize_path( ABSPATH . $this->gallery_meta->path . 'thumbs/' . $this->thumbnail_file );
		$this->url            = home_url( $this->gallery_meta->path . $this->meta->filename );
		$this->thumbnail_url  = home_url( $this->gallery_meta->path . 'thumbs/' . $this->thumbnail_file );
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
	 * Проверка на оптимизацию изображения
	 *
	 * @return bool
	 */
	public function isOptimized() {
		$optimization_data = $this->getOptimizationData();
		if ( empty( $optimization_data ) ) {
			return false;
		}
		if ( $optimization_data->is_optimized() ) {
			return true;
		}

		return false;
	}

	/**
	 * Возвращает данные по оптимизации
	 *
	 * @return RIO_Process_Queue
	 */
	public function getOptimizationData() {
		if ( empty( $this->optimization_data ) ) {
			$this->optimization_data = $this->createOptimizationData();
			$this->optimization_data->load();
		}

		return $this->optimization_data;
	}

	/**
	 * Создаёт новый объект RIO_Process_Queue
	 *
	 * @return RIO_Process_Queue
	 */
	public function createOptimizationData() {
		return new RIO_Process_Queue( [
			'object_id' => $this->id,
			'item_type' => 'nextgen',
		] );
	}

	/**
	 * Оптимизирует изображения
	 *
	 * @param string $optimization_level   качество
	 *
	 * @return array
	 */
	public function optimize( $optimization_level = '' ) {
		$is_image_backuped = $this->backup();

		if ( is_wp_error( $is_image_backuped ) ) {
			$error_msg = $is_image_backuped->get_error_message() . PHP_EOL;

			return [
				'errors_count'    => 1,
				'original_size'   => 0,
				'optimized_size'  => 0,
				'optimized_count' => 0,
			];
		}

		// делаем рисайз
		$image_processor = WIO_OptimizationTools::getImageProcessor();

		if ( ! $optimization_level ) {
			$optimization_level = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level', 'normal' );
		}

		if ( $optimization_level == 'custom' ) {
			$custom_quality     = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level_custom', 100 );
			$optimization_level = intval( $custom_quality );
		}

		$optimization_data             = $this->createOptimizationData();
		$results                       = [];
		$results['processing_level']   = $optimization_level;
		$results['original_mime_type'] = '';
		$results['final_mime_type']    = '';

		$main_file_path = wp_normalize_path( ABSPATH . $this->gallery_meta->path . $this->meta->filename );
		$main_file_url  = home_url( $this->gallery_meta->path . $this->meta->filename );

		clearstatcache(); // на всякий случай очистим кеш файловой статистики
		if ( ! file_exists( $main_file_path ) ) {
			$results['result_status'] = 'error';
			$results['original_size'] = 0;
			$results['final_size']    = 0;
			$extra_data               = [
				'error'     => 'file',
				'error_msg' => __( 'File not found', 'robin-image-optimizer' ),
			];
			$results['extra_data']    = new WRIO_Nextgen_Extra_Data( $extra_data );
			$optimization_data->configure( $results );
			$optimization_data->save();

			return [
				'errors_count'    => 1,
				'original_size'   => 0,
				'optimized_size'  => 0,
				'optimized_count' => 0,
			];
		}
		$original_main_size = filesize( $main_file_path ); // оптимизированный размер только главной картинки

		$optimized_img_data = $image_processor->process( [
			'image_url'  => $main_file_url,
			'image_path' => $main_file_path,
			'quality'    => $image_processor->quality( $optimization_level ),
			'save_exif'  => WRIO_Plugin::app()->getPopulateOption( 'save_exif_data', false ),
		] );

		if ( is_wp_error( $optimized_img_data ) ) {
			$results['result_status'] = 'error';
			$results['original_size'] = 0;
			$results['final_size']    = 0;
			$extra_data               = [
				'error'     => 'optimization',
				'error_msg' => $optimized_img_data->get_error_message(),
			];
			$results['extra_data']    = new WRIO_Nextgen_Extra_Data( $extra_data );
			$optimization_data->configure( $results );
			$optimization_data->save();

			return [
				'errors_count'    => 1,
				'original_size'   => 0,
				'optimized_size'  => 0,
				'optimized_count' => 0,
			];
		}

		// оптимизируем thumbnail
		$optimized_thumbnail_data = $image_processor->process( [
			'image_url'  => $this->thumbnail_url,
			'image_path' => $this->thumbnail_path,
			'quality'    => $image_processor->quality( $optimization_level ),
			'save_exif'  => WRIO_Plugin::app()->getPopulateOption( 'save_exif_data', false ),
		] );

		// отложенная оптимизация
		if ( isset( $optimized_img_data['status'] ) && $optimized_img_data['status'] == 'processing' ) {
			$results['result_status'] = 'processing';
			$results['is_backed_up']  = $is_image_backuped;
			$results['original_size'] = 0;
			$results['final_size']    = 0;
			$extra_data               = [
				'main_optimized_data'       => $optimized_img_data,
				'thumbnails_optimized_data' => $optimized_thumbnail_data,
				'image_relative_path'       => str_replace( untrailingslashit( ABSPATH ), '', $this->path ),
			];
			$results['extra_data']    = new WRIO_Nextgen_Extra_Data( $extra_data );
			$optimization_data->configure( $results );
			$optimization_data->save();

			return [
				'processing'     => 1,
				'original_size'  => 0,
				'optimized_size' => 0,
			];
		}

		$this->replaceOriginalFile( $optimized_img_data );

		// некоторые провайдеры не отдают оптимизированный размер, поэтому после замены файла получаем его сами
		if ( ! $optimized_img_data['optimized_size'] ) {
			clearstatcache();
			$optimized_img_data['optimized_size'] = filesize( $main_file_path );
		}

		// при отрицательной оптимизации ставим значение оригинала
		if ( $optimized_img_data['optimized_size'] > $original_main_size ) {
			$optimized_img_data['optimized_size'] = $original_main_size;
		}

		$original_size       = $original_main_size;
		$optimized_size      = $optimized_img_data['optimized_size'];
		$optimized_main_size = $optimized_img_data['optimized_size'];

		if ( ! is_wp_error( $optimized_thumbnail_data ) ) {
			$original_size += filesize( $this->thumbnail_path );
			$this->replaceOriginalFile( $optimized_thumbnail_data, 'thumbnail' );
			// некоторые провайдеры не отдают оптимизированный размер, поэтому после замены файла получаем его сами
			if ( ! $optimized_thumbnail_data['optimized_size'] ) {
				clearstatcache();
				$optimized_thumbnail_data['optimized_size'] = filesize( $this->thumbnail_path );
			}
			$optimized_size += $optimized_thumbnail_data['optimized_size'];
		}
		$results['result_status'] = 'success';
		$results['final_size']    = $optimized_size;
		$results['original_size'] = $original_size;
		$results['is_backed_up']  = $is_image_backuped;
		$extra_data               = [
			'original_main_size'  => $original_main_size,
			'optimized_main_size' => $optimized_main_size,
			'image_relative_path' => str_replace( wp_normalize_path( untrailingslashit( ABSPATH ) ), '', $this->path ),
		];
		$results['extra_data']    = new WRIO_Nextgen_Extra_Data( $extra_data );
		$mime_type                = '';
		if ( function_exists( 'wp_get_image_mime' ) ) {
			$mime_type = wp_get_image_mime( $main_file_path );
		}
		$results['original_mime_type'] = $mime_type;
		$results['final_mime_type']    = $mime_type;
		$optimization_data->configure( $results );
		$optimization_data->save();

		return [
			'errors_count'    => 0,
			'original_size'   => $original_size,
			'optimized_size'  => $optimized_size,
			'optimized_count' => 1,
		];
	}

	/**
	 * Отложенная оптимизация аттачмента
	 *
	 * @return bool|array
	 */
	public function deferredOptimization() {
		$results           = [
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
		 * @var WRIO_Nextgen_Extra_Data $extra_data
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

		$thumbnails_processed     = true;
		$thumbnail_optimized_data = $extra_data->get_thumbnails_optimized_data();
		if ( ! $thumbnail_optimized_data['optimized_img_url'] ) {
			$thumbnail_image_url = $image_processor->checkDeferredOptimization( $thumbnail_optimized_data );
			if ( $thumbnail_image_url ) {
				$thumbnail_optimized_data['optimized_img_url'] = $thumbnail_image_url;
			} else {
				$thumbnails_processed = false;
			}
		}
		$extra_data->set_thumbnails_optimized_data( $thumbnail_optimized_data );

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
			$thumbnail_file = $this->get( 'thumbnail_path' );
			$original_size  = $original_size + filesize( $thumbnail_file );
			$this->replaceOriginalFile( [
				'optimized_img_url' => $thumbnail_optimized_data['optimized_img_url'],
			], 'thumbnail' );
			clearstatcache();
			$optimized_size = $optimized_size + filesize( $thumbnail_file );
			$thumbnails_count ++;
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
			// удаляем промежуточные данные
			$extra_data->set_main_optimized_data( null );
			$extra_data->set_thumbnails_optimized_data( null );

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
	 * Заменяет оригинальный файл на оптимизированный
	 *
	 * @param array  $optimized_img_data   результат оптимизации ввиде массива данных
	 * @param string $image_size           Размер(thumbnail, medium ... )
	 */
	public function replaceOriginalFile( $optimized_img_data, $image_size = '' ) {
		$optimized_img_url = $optimized_img_data['optimized_img_url'];
		if ( isset( $optimized_img_data['not_need_download'] ) and $optimized_img_data['not_need_download'] ) {
			$optimized_file = $optimized_img_url;
		} else {
			$optimized_file = $this->remoteDownloadImage( $optimized_img_url );
		}
		if ( isset( $optimized_img_data['not_need_replace'] ) and $optimized_img_data['not_need_replace'] ) {
			// если картинка уже оптимизирована и провайдер её не может уменьшить - он может вернуть положительный ответ, но без самой картинки. В таком случае ничего заменять не надо
			return true;
		}
		if ( ! $optimized_file ) {
			return false;
		}
		$path = $this->path;
		if ( $image_size == 'thumbnail' ) {
			$path = $this->thumbnail_path;
		}

		if ( ! is_file( $path ) ) {
			return false;
		}

		file_put_contents( $path, $optimized_file );

		return true;
	}

	/**
	 * Загрузка картинки с удалённого сервера
	 *
	 * todo: RIO-18 можем ли мы создать универсальный метод для всех внешних запросов, чтобы не дублировать код?
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected function remoteDownloadImage( $url ) {
		if ( ! function_exists( 'curl_version' ) ) {
			return file_get_contents( $url );
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

		return $image_body;
	}

	/**
	 * Делает резервную копию изображения
	 */
	public function backup() {
		$backup = WRIOP_Backup::get_instance();

		return $backup->backupNextgen( $this );
	}

	/**
	 * Восстанавливает из резервной копии.
	 *
	 * @return bool|WP_Error
	 */
	public function restore() {

		$backup   = WRIOP_Backup::get_instance();
		$restored = $backup->restoreNextgen( $this );

		if ( is_wp_error( $restored ) ) {
			return $restored;
		}

		global $wpdb;

		$io_db_table = RIO_Process_Queue::table_name();

		$wpdb->delete( $io_db_table, [
			'object_id' => $this->id,
			'item_type' => 'nextgen',
		] );

		/**
		 * Хук срабатывает после восстановления nextgen image
		 *
		 * @since 1.2.0
		 *
		 * @param RIO_Process_Queue $optimization_data
		 *
		 */
		do_action( 'wbcr/rio/nextgen_image_restored', $this->optimization_data );

		return true;
	}
}
