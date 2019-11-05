<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с custom folder image.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Folder_Image {

	/**
	 * @var int номер картинки в таблице
	 */
	private $id;

	/**
	 * @var string путь к картинке относительно папки
	 */
	private $path;

	/**
	 * @var string УРЛ
	 */
	private $url;

	/**
	 * @var string уникальный идентификатор директории
	 */
	private $folder_uid;

	/**
	 * @var RIO_Process_Queue данные по оптимизации
	 */
	private $optimization_data;

	/**
	 * Инициализация картинки из custom folder
	 *
	 * @param int         $image_id     номер картинки в таблице folders
	 * @param array|false $image_data   метаданные картинки
	 */
	public function __construct( $image_id, $image_data = false ) {
		$this->id = $image_id;

		if ( $image_data instanceof RIO_Process_Queue ) {
			$this->optimization_data = $image_data;
		} else {
			$this->optimization_data = $this->createOptimizationData();

			if ( $image_data ) {
				$this->optimization_data->configure( (array) $image_data );
			} else {
				$this->loadOptimizationData();
			}
		}

		/**
		 * @var WRIO_CF_Image_Extra_Data $extra_data
		 */
		$extra_data       = $this->optimization_data->get_extra_data();
		$this->path       = wp_normalize_path( ABSPATH . $extra_data->get_file_path() );
		$this->url        = home_url( wp_normalize_path( $extra_data->get_file_path() ) );
		$this->folder_uid = $this->optimization_data->get_item_hash_alternative();
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
			'id'        => $this->id,
			'item_type' => 'cf_image',
		] );
	}


	protected function loadOptimizationData() {
		global $wpdb;

		if ( empty( $this->optimization_data ) ) {
			$this->optimization_data = $this->createOptimizationData();
		}

		$table_name = RIO_Process_Queue::table_name();
		$sql        = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d AND item_type = %s LIMIT 1;", [
			$this->id,
			'cf_image',
		] );

		$row = $wpdb->get_row( $sql );

		if ( ! empty( $row ) ) {
			$this->optimization_data->configure( $row );
		}
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
	 * Check whether file exists or not.
	 *
	 * @return bool
	 */
	public function isFileExists() {
		if ( file_exists( $this->path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Optimize folder image.
	 *
	 * @param string $optimization_level   Level of optimization.
	 *
	 * @return array
	 */
	public function optimize( $optimization_level = '' ) {
		$is_image_backuped = $this->backup();

		if ( is_wp_error( $is_image_backuped ) ) {

			WRIO_Logger::error( sprintf( 'Failed to backup with message: %s. Skipping optimization of custom folder', $is_image_backuped->get_error_message() ) );

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
		$optimization_data           = $this->getOptimizationData();
		$results                     = [];
		$results['processing_level'] = $optimization_level;
		$main_file_path              = $this->path;
		$main_file_url               = $this->url;
		clearstatcache(); // на всякий случай очистим кеш файловой статистики
		$original_main_size = filesize( $main_file_path ); // оптимизированный размер только главной картинки

		$optimized_img_data = $image_processor->process( [
			'image_url'  => $main_file_url,
			'image_path' => $main_file_path,
			'quality'    => $image_processor->quality( $optimization_level ),
			'save_exif'  => WRIO_Plugin::app()->getPopulateOption( 'save_exif_data', false ),
		] );

		if ( is_wp_error( $optimized_img_data ) ) {
			$results['result_status'] = 'error';
			/**
			 * @var $extra_data WRIO_CF_Image_Extra_Data
			 */
			$extra_data = $optimization_data->get_extra_data();
			$extra_data->set_error( 'optimization' );
			$extra_data->set_error_msg( $optimized_img_data->get_error_message() );
			$results['extra_data'] = $extra_data;
			$optimization_data->configure( $results );
			$optimization_data->save();

			return [
				'errors_count'    => 1,
				'original_size'   => 0,
				'optimized_size'  => 0,
				'optimized_count' => 0,
			];
		}

		// отложенная оптимизация
		if ( isset( $optimized_img_data['status'] ) && $optimized_img_data['status'] == 'processing' ) {
			$results['result_status'] = 'processing';
			$results['is_backed_up']  = $is_image_backuped;
			$results['original_size'] = 0;
			$results['final_size']    = 0;

			/**
			 * @var $extra_data WRIO_CF_Image_Extra_Data
			 */
			$extra_data = $optimization_data->get_extra_data();
			$extra_data->set_main_optimized_data( $optimized_img_data );
			$results['extra_data'] = $extra_data;
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

		$original_size  = $original_main_size;
		$optimized_size = $optimized_img_data['optimized_size'];

		$results['result_status'] = 'success';
		$results['final_size']    = $optimized_size;
		$results['original_size'] = $original_size;
		$results['is_backed_up']  = $is_image_backuped;

		/**
		 * @var $extra_data WRIO_CF_Image_Extra_Data
		 */
		$extra_data = $optimization_data->get_extra_data();
		$extra_data->set_main_optimized_data( null );
		$extra_data->set_error( null );
		$extra_data->set_error_msg( null );
		$results['extra_data'] = $extra_data;
		$mime_type             = '';
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
		 * @var $extra_data WRIO_CF_Image_Extra_Data
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

		$thumbnails_processed = true; // для кастомных папок нет превьюшек, поэтому всегда true

		// когда все файлы получены - сохраняем и возвращаем результат
		if ( $main_image_url && $thumbnails_processed ) {
			$original_size      = 0;
			$optimized_size     = 0;
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
			clearstatcache();
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
			$extra_data->set_error( null );
			$extra_data->set_error_msg( null );

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
	 * @param array $optimized_img_data   результат оптимизации ввиде массива данных
	 */
	public function replaceOriginalFile( $optimized_img_data ) {
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

		return $backup->backupCFImage( $this );
	}

	/**
	 * Восстанавливает из резервной копии
	 */
	public function restore() {
		$backup   = WRIOP_Backup::get_instance();
		$restored = $backup->restoreCFImage( $this );

		if ( is_wp_error( $restored ) ) {
			return $restored;
		}

		$optimization_data = $this->getOptimizationData();
		$optimization_data->set_result_status( 'unoptimized' );
		$optimization_data->save();

		/**
		 * Хук срабатывает после восстановления cf_image
		 *
		 * @since 1.2.0
		 *
		 * @param RIO_Process_Queue $optimization_data
		 *
		 */
		do_action( 'wbcr/rio/cf_image_restored', $this->optimization_data );

		return true;
	}
}
