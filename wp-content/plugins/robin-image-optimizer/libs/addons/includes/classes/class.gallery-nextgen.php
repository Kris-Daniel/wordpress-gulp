<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с галереей nextgen
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Nextgen_Gallery {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;

	/**
	 * @var array $nextgen_images контейнер для хранения nextgen_images
	 */
	private $nextgen_images = [];

	/**
	 * Инициализация функционала nextgen галереи. Установка хуков
	 */
	public function __construct() {
		if ( ! wrio_is_active_nextgen_gallery() ) {
			return;
		}

		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/models/class.nextgen-extra-data.php' );
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.image-nextgen.php' );
		require_once( WRIOP_PLUGIN_DIR . '/admin/ajax/optimization.php' );

		add_filter( 'ngg_manage_images_number_of_columns', [ $this, 'addColumns' ] );

		add_action( 'wp_ajax_wio_ng_reoptimize_image', 'wbcr_riop_reoptimizeImage' );
		add_action( 'wp_ajax_wio_ng_restore_image', 'wbcr_riop_restoreImage' );
		add_action( 'wp_ajax_wio_process_ng_images', 'wbcr_riop_optimizeImages' );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueMeadiaScripts' ], 10 );
		add_action( 'ngg_delete_picture', [ $this, 'deleteImageHook' ], 10, 2 );
		add_action( 'ngg_recovered_image', [ $this, 'recoverImageHook' ], 10, 1 );

		add_filter( 'wbcr/rio/optimize_template/reoptimize_ajax_action', [
			$this,
			'reoptimizeAjaxAction',
		], 10, 2 );

		add_filter( 'wbcr/rio/optimize_template/restore_ajax_action', [ $this, 'restoreAjaxAction' ], 10, 2 );
		//add_filter( 'wbcr/rio/multisite_blogs', [ $this, 'multisiteBlogs' ], 10, 2 );
		add_action( 'wbcr/rio/optimize_template/optimized_percent', [ $this, 'optimizedPercent' ], 10, 2 );
		add_action( 'wbcr/riop/queue_item_saved', [ $this, 'webpSuccess' ] );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 *
	 * @return object|\WRIO_Nextgen_Gallery object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( static::$_instance ) ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	/**
	 * Возвращает сайты, на которых установлен NextGen gallery
	 * Используется в мультисайт режиме
	 *
	 * @param array  $blogs   сайты
	 * @param string $type    тип
	 *
	 * @return array сайты, на которых установлен nextgen
	 */
	/*public function multisiteBlogs( $blogs, $type ) {
		if ( 'nextgen' == $type ) {
			$nextgen_basename = 'nextgen-gallery/nggallery.php';
			if ( is_plugin_active_for_network( $nextgen_basename ) ) {
				return $blogs;
			} else {
				$nextgen_blogs = [];
				foreach ( $blogs as $blog ) {
					switch_to_blog( intval( $blog->blog_id ) );
					if ( is_plugin_active( $nextgen_basename ) ) {
						$nextgen_blogs[] = $blog;
					}
					restore_current_blog();
				}

				return $nextgen_blogs;
			}
		}

		return $blogs;
	}*/

	/**
	 * Хук возвращает ajax action для кнопки переоптимизации
	 */
	public function reoptimizeAjaxAction( $action, $type ) {
		if ( $type == 'nextgen' ) {
			return 'wio_ng_reoptimize_image';
		}

		return $action;
	}

	/**
	 * Хук возвращает ajax action для кнопки восстановления
	 */
	public function restoreAjaxAction( $action, $type ) {
		if ( $type == 'nextgen' ) {
			return 'wio_ng_restore_image';
		}

		return $action;
	}

	/**
	 * Добавляем стили и скрипты в медиабиблиотеку
	 */
	public function enqueueMeadiaScripts( $hook ) {
		if ( strpos( $hook, 'page_nggallery-manage-gallery' ) === false ) {
			return;
		}
		wp_enqueue_style( 'wio-install-addons', WRIO_PLUGIN_URL . '/admin/assets/css/media.css', [], WRIO_Plugin::app()->getPluginVersion() );
		wp_enqueue_script( 'wio-install-addons', WRIO_PLUGIN_URL . '/admin/assets/js/single-optimization.js', [ 'jquery' ], WRIO_Plugin::app()->getPluginVersion() );
	}

	/**
	 * Добавляет колонку оптимизации в галерею nextgen
	 */
	public function addColumns( $count ) {
		$count ++;
		add_filter( 'ngg_manage_images_column_' . $count . '_header', [ $this, 'columnTitle' ] );
		add_filter( 'ngg_manage_images_column_' . $count . '_content', [ $this, 'columnContent' ], 10, 2 );

		return $count;
	}

	/**
	 * Название колонки оптимизации в галерее
	 */
	public function columnTitle() {
		return __( 'Image optimizer', 'image optimizer' );
	}

	/**
	 * Возвращает содержимое блока оптимизации
	 */
	public function columnContent( $output, $image ) {
		$output = $this->getMediaColumnContent( $image->pid );

		return $output;
	}

	/**
	 * Возвращает блок оптимизации
	 */
	public function getMediaColumnContent( $image_id ) {

		$media_library = WRIO_Media_Library::get_instance();
		$params        = $this->calculateParams( $image_id );

		return $media_library->getMediaColumnTemplate( $params, 'nextgen' );
	}

	/**
	 * Просчитывает параметры блока оптимизации
	 *
	 * @param int $image_id   номер изображения nextgen
	 *
	 * @return array $params
	 */
	public function calculateParams( $image_id ) {
		$nextgen_image        = new WRIO_Image_Nextgen( $image_id );
		$isOptimized          = $nextgen_image->isOptimized();
		$diff_percent         = 0;
		$diff_percent_all     = 0;
		$original_main_size   = 0;
		$attachment_file_size = 0;
		$optimized_size       = 0;
		$original_size        = 0;
		$optimization_level   = '';
		$error_msg            = '';
		$backuped             = '';
		if ( $isOptimized ) {
			$optimization_data = $nextgen_image->getOptimizationData();
			/**
			 * @var WRIO_Nextgen_Extra_Data $extra_data
			 */
			$extra_data           = $optimization_data->get_extra_data();
			$optimization_level   = $optimization_data->get_processing_level();
			$original_main_size   = $extra_data->get_original_main_size();
			$attachment_file_size = $optimization_data->get_final_size();
			$optimized_size       = $optimization_data->get_final_size();
			$original_size        = $optimization_data->get_original_size();
			$backuped             = $optimization_data->get_is_backed_up();
			$error_msg            = $extra_data->get_error_msg();
			if ( $attachment_file_size and $original_main_size ) {
				$diff_percent = round( ( $original_main_size - $attachment_file_size ) * 100 / $original_main_size );
			}
			if ( $optimized_size and $original_size ) {
				$diff_percent_all = round( ( $original_size - $optimized_size ) * 100 / $original_size );
			}
		}
		$params = [
			'attachment_id'        => $image_id,
			'is_optimized'         => $isOptimized,
			'attach_dimensions'    => 0,
			'attachment_file_size' => $attachment_file_size,
			'optimized_size'       => $optimized_size,
			'original_size'        => $original_size,
			'original_main_size'   => $original_main_size,
			'thumbnails_optimized' => 1,
			'optimization_level'   => $optimization_level,
			'error_msg'            => $error_msg,
			'backuped'             => $backuped,
			'diff_percent'         => $diff_percent,
			'diff_percent_all'     => $diff_percent_all,
			'is_skipped'           => false,
		];

		return $params;
	}

	/**
	 * Возвращает объект nextgen_image
	 *
	 * @param int   $image_id
	 * @param array $image_meta
	 *
	 * @return WRIO_Image_Nextgen
	 */
	public function getNextgenImage( $image_id, $image_meta = false ) {
		if ( ! isset( $this->nextgen_images[ $image_id ] ) ) {
			$this->nextgen_images[ $image_id ] = new WRIO_Image_Nextgen( $image_id, $image_meta );
		}

		return $this->nextgen_images[ $image_id ];
	}

	/**
	 * Оптимизирует nextgen_image
	 *
	 * @param int    $image_id   номер картинки в таблице nextgen
	 * @param string $level      качество
	 *
	 * @return array
	 */
	public function optimizeNextgenImage( $image_id, $level = '' ) {
		$nextgen_image     = $this->getNextgenImage( $image_id );
		$optimization_data = $nextgen_image->getOptimizationData();
		if ( 'processing' == $optimization_data->get_result_status() ) {
			return $this->deferredOptimizeNextgenImage( $image_id );
		}
		$image_statistics = WRIO_Image_Statistic_Nextgen::get_instance();
		if ( $nextgen_image->isOptimized() ) {
			$optimized_size = $optimization_data->get_final_size();
			$original_size  = $optimization_data->get_original_size();
			$image_statistics->deductFromField( 'optimized_size', $optimized_size );
			$image_statistics->deductFromField( 'original_size', $original_size );
			$nextgen_image->restore();
		}
		$image_optimized_data = $nextgen_image->optimize( $level );
		$original_size        = $image_optimized_data['original_size'];
		$optimized_size       = $image_optimized_data['optimized_size'];
		$image_statistics->addToField( 'optimized_size', $optimized_size );
		$image_statistics->addToField( 'original_size', $original_size );
		$image_statistics->save();

		return $image_optimized_data;
	}

	/**
	 * Отложенная оптимизация nextgen_image.
	 *
	 * @param int $image_id
	 *
	 * @return array|bool array on success, false on failure.
	 */
	protected function deferredOptimizeNextgenImage( $image_id ) {
		$nextgen_image     = $this->getNextgenImage( $image_id );
		$optimization_data = $nextgen_image->getOptimizationData();
		$image_processor   = WIO_OptimizationTools::getImageProcessor();

		// если текущий сервер оптимизации не поддерживает отложенную оптимизацию, а в очереди есть аттачменты - ставим им ошибку
		if ( ! $image_processor->isDeferred() ) {
			$optimization_data->set_result_status( 'error' );

			/**
			 * @var WRIO_Nextgen_Extra_Data $extra_data
			 */
			$extra_data = $optimization_data->get_extra_data();
			$extra_data->set_error( 'deferred' );
			$extra_data->set_error_msg( 'server not support deferred optimization' );
			$optimization_data->set_extra_data( $extra_data );
			$optimization_data->save();

			WRIO_Logger::error( sprintf( 'Server %s does not support deferred optimization', get_class( $image_processor ) ) );

			return false;
		}
		$optimized_data = $nextgen_image->deferredOptimization();
		if ( $optimized_data ) {
			$image_statistics = WRIO_Image_Statistic_Nextgen::get_instance();
			$image_statistics->addToField( 'optimized_size', $optimized_data['optimized_size'] );
			$image_statistics->addToField( 'original_size', $optimized_data['original_size'] );
			$image_statistics->save();
		}

		return $optimized_data;
	}


	/**
	 * Обработка неоптимизированных изображений
	 *
	 * @param int $max_process_per_request   кол-во аттачментов за 1 запуск
	 *
	 * @return array|\WP_Error
	 */
	public function processUnoptimizedImages( $max_process_per_request = 5 ) {
		$backup               = WRIOP_Backup::get_instance();
		$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );
		if ( $backup_origin_images and ! $backup->isBackupWritable() ) {
			return new WP_Error( 'unwritable_backup_dir', __( 'No access for writing backups.', 'robin-image-optimizer' ) );
		}
		if ( ! $backup->isUploadWritable() ) {
			return new WP_Error( 'unwritable_upload_dir', __( 'No access for writing backups.', 'robin-image-optimizer' ) );
		}
		$image_statistics = WRIO_Image_Statistic_Nextgen::get_instance();

		//выборка неоптимизированных изображений
		$gallery_images       = $image_statistics->getUnoptimized( $max_process_per_request ); // тут будет выборка
		$total_unoptimized    = $image_statistics->getUnoptimizedCount(); // тут общее кол-во неоптимизированных
		$gallery_images_count = 0;
		if ( isset( $gallery_images ) ) {
			$gallery_images_count = count( $gallery_images );
		}

		$optimized_count = 0;

		// обработка
		if ( $gallery_images_count ) {
			foreach ( $gallery_images as $gallery_image ) {
				$this->optimizeNextgenImage( $gallery_image->pid );
			}
		}

		$remain = $total_unoptimized - $gallery_images_count;
		// проверяем, есть ли аттачменты в очереди на отложенную оптимизацию
		$optimized_data = $this->processDeferredOptimization();
		if ( $optimized_data ) {
			$optimized_count = $optimized_data['optimized_count'];
			$remain          = $total_unoptimized - $optimized_count;
		}
		if ( $remain <= 0 ) {
			$remain = 0;
		}
		$responce = [
			'remain'          => $remain,
			'end'             => false,
			'last_optimized'  => $image_statistics->get_last_optimized_images( $max_process_per_request ),
			'statistic'       => $image_statistics->load(),
			'optimized_count' => $optimized_count,
		];

		return $responce;
	}

	/**
	 * Отложенная оптимизация
	 *
	 * @return bool|array
	 */
	protected function processDeferredOptimization() {
		global $wpdb;
		$db_table = RIO_Process_Queue::table_name();
		$image_id = $wpdb->get_var( "SELECT object_id FROM {$db_table} WHERE item_type = 'nextgen' and result_status = 'processing' LIMIT 1;" );
		if ( ! $image_id ) {
			return false;
		}
		$nextgen_image  = $this->getNextgenImage( $image_id );
		$optimized_data = $nextgen_image->deferredOptimization();
		if ( $optimized_data ) {
			$image_statistics = WRIO_Image_Statistic_Nextgen::get_instance();
			$image_statistics->addToField( 'optimized_size', $optimized_data['optimized_size'] );
			$image_statistics->addToField( 'original_size', $optimized_data['original_size'] );
			$image_statistics->save();

			return $optimized_data;
		}

		return false;
	}

	/**
	 * Сбрасывает текущие ошибки оптимизации
	 * Позволяет изображениям, которые оптимизированы с ошибкой, заново пройти оптимизацию.
	 *
	 * @return void
	 */
	public function resetCurrentErrors() {
		//do_action( 'wbcr/rio/multisite_current_blog' );

		global $wpdb;

		$db_table = RIO_Process_Queue::table_name();

		$wpdb->delete( $db_table, [
			'item_type'     => 'nextgen',
			'result_status' => 'error',
		], [ '%s', '%s' ] );
		//do_action( 'wbcr/rio/multisite_restore_blog' );
	}

	/**
	 * Хук срабатывает при восстановлении картинок через стандартный интерфейс nextgen
	 */
	public function recoverImageHook( $image ) {
		$image_id      = isset( $image->pid ) ? $image->pid : 0;
		$nextgen_image = new WRIO_Image_Nextgen( $image_id );
		if ( $nextgen_image->isOptimized() ) {
			$optimization_data = $nextgen_image->getOptimizationData();
			$image_statistics  = WRIO_Image_Statistic_Nextgen::get_instance();
			$optimized_size    = $optimization_data->get_final_size();
			$original_size     = $optimization_data->get_original_size();
			$image_statistics->deductFromField( 'optimized_size', $optimized_size );
			$image_statistics->deductFromField( 'original_size', $original_size );
			$image_statistics->save();

			if ( ! $nextgen_image->restore() ) {
				WRIO_Logger::error( sprintf( 'Failed to restore Nextgen image. Object info: %s', wp_json_encode( $nextgen_image ) ) );
			}

			$optimization_data->delete();
		}
		$this->deleteNextgenOptimizationData( $image_id );
	}

	/**
	 * Хук срабатывает при удалении картинки
	 */
	public function deleteImageHook( $image_id, $image ) {
		$nextgen_image = new WRIO_Image_Nextgen( $image_id );

		if ( $nextgen_image->isOptimized() ) {
			$restored = $nextgen_image->restore();

			if ( ! is_wp_error( $restored ) ) {
				$optimization_data = $nextgen_image->getOptimizationData();
				$image_statistics  = WRIO_Image_Statistic_Nextgen::get_instance();
				$optimized_size    = $optimization_data->get_final_size();
				$original_size     = $optimization_data->get_original_size();
				$image_statistics->deductFromField( 'optimized_size', $optimized_size );
				$image_statistics->deductFromField( 'original_size', $original_size );
				$image_statistics->save();

				WRIO_Logger::error( sprintf( 'Failed to restore Nextgen image. Object info: %s', wp_json_encode( $nextgen_image ) ) );

				$optimization_data->delete();
			}
		}
		$this->deleteNextgenOptimizationData( $image_id );
	}

	/**
	 * Удаляет данные по оптимизации из таблицы в базе данных
	 *
	 * @param int $image_id
	 *
	 * @return void
	 */
	protected function deleteNextgenOptimizationData( $image_id = 0 ) {
		global $wpdb;

		$db_table = RIO_Process_Queue::table_name();

		$wpdb->delete( $db_table, [
			'object_id' => $image_id,
			'item_type' => 'nextgen',
		] );
	}

	/**
	 * Возвращает процент оптимизации
	 * Фильтр wbcr/rio/optimize_template/optimized_percent
	 *
	 * @param int    $percent   процент оптимизации
	 * @param string $type      тип страницы
	 *
	 * @return int процент оптимизации
	 */
	public function optimizedPercent( $percent, $type ) {
		if ( 'nextgen' == $type ) {
			$image_statistics = WRIO_Image_Statistic_Nextgen::get_instance();

			return $image_statistics->getOptimizedPercent();
		}

		return $percent;
	}

	/**
	 * Сохраняет WebP размер
	 *
	 * @param RIO_Process_Queue $queue_model
	 *
	 * @return bool
	 */
	public function webpSuccess( $queue_model ) {
		if ( ! class_exists( 'WRIO\WEBP\Listener' ) ) {
			return false; // если не установлена премиум версия, то WebP не активен
		}
		if ( $queue_model->get_item_type() !== WRIO\WEBP\Listener::DEFAULT_TYPE ) {
			return false;
		}
		if ( $queue_model->get_result_status() !== RIO_Process_Queue::STATUS_SUCCESS ) {
			return false;
		}

		/**
		 * @var RIOP_WebP_Extra_Data $extra_data
		 */
		$extra_data = $queue_model->get_extra_data();
		$item_type  = $extra_data->get_convert_from();

		if ( $item_type != 'nextgen' ) {
			return false;
		}

		if ( ! $queue_model->object_id ) {
			return false;
		}

		$optimization_data = new RIO_Process_Queue( [
			'object_id' => $queue_model->object_id,
			'item_type' => 'nextgen',
		] );

		$optimization_data->load();

		/**
		 * @var WRIO_Nextgen_Extra_Data $extra_data
		 */
		$extra_data = $optimization_data->get_extra_data();
		if ( ! $extra_data ) {
			return false;
		}

		$extra_data->set_webp_main_size( $queue_model->get_final_size() );
		$optimization_data->set_extra_data( $extra_data );
		add_filter( 'wbcr/riop/queue_item_save_execute_hook', '__return_false' );
		$optimization_data->save();
		remove_filter( 'wbcr/riop/queue_item_save_execute_hook', '__return_false' );

		return true;
	}

}
