<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с кастомными папками
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Custom_Folders {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;

	/**
	 * @var WRIO_Folder[]
	 */
	private $folders = [];

	/**
	 * @var WRIO_Folder_Image[]
	 */
	private $folder_images = [];

	/**
	 * WRIO_Custom_Folders constructor.
	 */
	public function __construct() {
		$folders = WRIO_Plugin::app()->getOption( 'custom_folders', [] );

		if ( ! empty( $folders ) ) {
			foreach ( (array) $folders as $uid => $folder ) {
				$this->folders[ $uid ] = new WRIO_Folder( $folder );
			}
		}

		$this->init();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 *
	 * @return object|\WRIO_Custom_Folders object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( static::$_instance ) ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	/**
	 * Object init.
	 */
	public function init() {

		// todo: убрать эти фильтры
		add_filter( 'wbcr/rio/optimize_template/optimize_ajax_action', [ $this, 'optimizeAjaxAction' ], 10, 2 );
		add_filter( 'wbcr/rio/optimize_template/reoptimize_ajax_action', [
			$this,
			'reoptimizeAjaxAction',
		], 10, 2 );
		add_filter( 'wbcr/rio/optimize_template/restore_ajax_action', [ $this, 'restoreAjaxAction' ], 10, 2 );

		add_action( 'admin_menu', [ $this, 'add_media_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'media_page_assets' ] );
		add_action( 'wbcr/rio/optimize_template/optimized_percent', [ $this, 'optimizedPercent' ], 10, 2 );
		//add_action( 'wbcr/riop/queue_item_saved', [ $this, 'webpSuccess' ], 20 );
		add_action( 'wbcr/rio/webp_success', [ $this, 'webpSuccess' ], 20 );
	}

	public function add_media_page() {
		add_submenu_page( 'upload.php', 'Other Media', 'Other Media', 'manage_options', 'rio-custom-media', [
			$this,
			'custom_media_page'
		] );
	}

	public function media_page_assets( $hook ) {
		if ( 'media_page_rio-custom-media' == $hook ) {
			wp_enqueue_style( 'wio-install-addons', WRIO_PLUGIN_URL . '/admin/assets/css/media.css', [], WRIO_Plugin::app()->getPluginVersion() );
			wp_enqueue_style( 'wriop-other-media', WRIOP_PLUGIN_URL . '/admin/assets/css/other-media.css', [], WRIO_Plugin::app()->getPluginVersion() );
			wp_enqueue_script( 'wio-install-addons', WRIO_PLUGIN_URL . '/admin/assets/js/single-optimization.js', [ 'jquery' ], WRIO_Plugin::app()->getPluginVersion() );
		}
	}

	public function custom_media_page() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		require_once( WRIOP_PLUGIN_DIR . '/includes/classes/class.folders-list-table.php' );

		$list_table = new WRIO_Folders_List_Table();
		$list_table->prepare_items();
		$list_table->display(); // выводит на экран весь блок с таблицей и фильтрами
	}

	/**
	 * Get folder by specified id.
	 *
	 * @param string $uid   Folder sha256 hash.
	 *
	 * @return bool|WRIO_Folder
	 */
	public function getFolder( $uid ) {
		if ( isset( $this->folders[ $uid ] ) ) {
			return $this->folders[ $uid ];
		}

		return false;
	}

	/**
	 * Get all available folders.
	 *
	 * @return WRIO_Folder[]
	 */
	public function getFolders() {
		return $this->folders;
	}

	/**
	 * Add new folder by specified path.
	 *
	 * @param string $path   Path to folder.
	 *
	 * @return WRIO_Folder|\WP_Error
	 */
	public function addFolder( $path ) {

		if ( empty( $path ) ) {
			return new WP_Error( 'empty_path', 'Path is empty.' );
		}

		$uid = hash( 'sha256', $path );
		if ( ! $this->getFolder( $uid ) ) {
			$folder_data = [
				'path' => $path,
				'uid'  => $uid,
			];
			$new_folder  = new WRIO_Folder( $folder_data );
			$new_folder->reCountFiles();
			$this->folders[ $uid ] = $new_folder;
			$this->saveFolders();

			return $new_folder;
		}

		return new WP_Error( 'folder_already_exists', 'Folder has already been added before.' );
	}

	/**
	 * Remove folder by sha256 hash.
	 *
	 * @param string $uid   SHA256 folder hash id.
	 *
	 * @return bool
	 */
	public function removeFolder( $uid ) {
		if ( empty( $uid ) ) {
			return false;
		}

		$folder = $this->getFolder( $uid );

		if ( ! $folder ) {
			return false;
		}

		$folder->remove();

		unset( $this->folders[ $uid ] );

		return true;
	}

	/**
	 * Saved all folders in options.
	 */
	public function saveFolders() {
		$folders = [];
		foreach ( $this->folders as $uid => $folder ) {
			$folders[ $uid ] = $folder->toArray();
		}
		WRIO_Plugin::app()->updateOption( 'custom_folders', $folders );
	}

	/**
	 * Возвращает объект image
	 *
	 * @param int         $image_id
	 * @param array|false $image_meta
	 *
	 * @return WRIO_Folder_Image
	 */
	public function getImage( $image_id, $image_meta = false ) {
		if ( ! isset( $this->folder_images[ $image_id ] ) ) {
			$this->folder_images[ $image_id ] = new WRIO_Folder_Image( $image_id, $image_meta );
		}

		return $this->folder_images[ $image_id ];
	}

	/**
	 * Оптимизирует cf_image
	 *
	 * @param int    $image_id   номер картинки в таблице nextgen
	 * @param string $level      качество
	 *
	 * @return array
	 */
	public function optimizeImage( $image_id, $level = '' ) {
		$cf_image          = $this->getImage( $image_id );
		$optimization_data = $cf_image->getOptimizationData();

		if ( 'processing' == $optimization_data->get_result_status() ) {
			return $this->deferredOptimizeImage( $image_id );
		}

		$image_statistics = WRIO_Image_Statistic_Folders::get_instance();

		if ( $cf_image->isOptimized() ) {
			$optimized_size = $optimization_data->get_final_size();
			$original_size  = $optimization_data->get_original_size();
			$image_statistics->deductFromField( 'optimized_size', $optimized_size );
			$image_statistics->deductFromField( 'original_size', $original_size );
			$cf_image->restore();
		}

		$image_optimized_data = $cf_image->optimize( $level );

		$original_size  = $image_optimized_data['original_size'];
		$optimized_size = $image_optimized_data['optimized_size'];

		$image_statistics->addToField( 'optimized_size', $optimized_size );
		$image_statistics->addToField( 'original_size', $original_size );
		$image_statistics->save();

		$folder = $this->getFolder( $cf_image->get( 'folder_uid' ) );
		$folder->reCountOptimizedFiles();
		$this->saveFolders();

		return $image_optimized_data;
	}

	/**
	 * Отложенная оптимизация image. Этап 1: отправка на сервер оптимизации
	 *
	 * @param int $image_id
	 *
	 * @return array|false
	 */
	protected function deferredOptimizeImage( $image_id ) {
		$cf_image          = $this->getImage( $image_id );
		$optimization_data = $cf_image->getOptimizationData();
		$image_processor   = WIO_OptimizationTools::getImageProcessor();

		// если текущий сервер оптимизации не поддерживает отложенную оптимизацию, а в очереди есть аттачменты - ставим им ошибку
		if ( ! $image_processor->isDeferred() ) {
			$optimization_data->set_result_status( 'error' );
			/**
			 * @var $extra_data WRIO_CF_Image_Extra_Data
			 */
			$extra_data = $optimization_data->get_extra_data();
			$extra_data->set_error( 'deferred' );
			$extra_data->set_error_msg( 'server not support deferred optimization' );
			$optimization_data->set_extra_data( $extra_data );
			$optimization_data->save();

			WRIO_Logger::error( sprintf( 'Server %s does not support deferred optimization', get_class( $image_processor ) ) );

			return false;
		}
		$optimized_data = $cf_image->deferredOptimization();
		if ( $optimized_data ) {
			$image_statistics = WRIO_Image_Statistic_Folders::get_instance();
			$image_statistics->addToField( 'folders_optimized_size', $optimized_data['optimized_size'] );
			$image_statistics->addToField( 'folders_original_size', $optimized_data['original_size'] );
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

		if ( $backup_origin_images && ! $backup->isBackupWritable() ) {
			return new WP_Error( 'unwritable_backup_dir', __( 'No access for writing backups.', 'robin-image-optimizer' ) );
		}

		if ( ! $backup->isUploadWritable() ) {
			return new WP_Error( 'unwritable_upload_dir', __( 'No access for writing backups.', 'robin-image-optimizer' ) );
		}

		if ( empty( $this->folders ) ) {
			return new WP_Error( 'folders_not_found', __( 'You need to add an custom folder to start optimization!', 'robin-image-optimizer' ) );
		}

		$image_statistics = WRIO_Image_Statistic_Folders::get_instance();

		$folder_images = $image_statistics->getUnoptimized( $max_process_per_request ); // тут будет выборка
		$total         = $image_statistics->getUnoptimizedCount(); // тут общее кол-во неоптимизированных

		$folder_images_count = count( $folder_images );
		$optimized_count     = 0;
		$optimized_items     = [];

		// обработка
		if ( $folder_images_count ) {
			foreach ( $folder_images as $folder_image ) {
				$this->optimizeImage( $folder_image->id );
				$optimized_count ++;
				$optimized_items[ $folder_image->id ] = $folder_image;
			}
		}

		$remain = $total - $folder_images_count;

		// проверяем, есть ли аттачменты в очереди на отложенную оптимизацию
		$optimized_data = $this->processDeferredOptimization();

		if ( $optimized_data ) {
			$optimized_count = $optimized_data['optimized_count'];
			$remain          = $total - $optimized_count;
		}

		if ( $remain <= 0 ) {
			$remain = 0;
		}

		$last_optimized = end( $optimized_items );

		$responce = [
			'remain'          => $remain,
			'end'             => false,
			'optimized_count' => $optimized_count,
			'last_optimized'  => $image_statistics->get_last_optimized_image( $last_optimized->id ),
			'statistic'       => $image_statistics->load(),
		];

		return $responce;
	}

	/**
	 * Отложенная оптимизация. Этап 2: получение данных с сервера потимизации
	 *
	 * @return bool|array
	 */
	protected function processDeferredOptimization() {
		$image_statistics = WRIO_Image_Statistic_Folders::get_instance();
		$limit            = 1;
		$image_data       = $image_statistics->getDeferredUnoptimized( $limit );

		if ( ! $image_data || ! isset( $image_data[0] ) ) {
			return false;
		}

		$image_data     = $image_data[0];
		$cf_image       = $this->getImage( $image_data->id, $image_data );
		$optimized_data = $cf_image->deferredOptimization();

		if ( $optimized_data ) {
			$image_statistics->addToField( 'optimized_size', $optimized_data['optimized_size'] );
			$image_statistics->addToField( 'original_size', $optimized_data['original_size'] );
			$image_statistics->save();

			$folder = $this->getFolder( $cf_image->get( 'folder_uid' ) );
			$folder->reCountOptimizedFiles();
			$this->saveFolders();

			return $optimized_data;
		}

		return false;
	}

	/**
	 * Восстановление из резервной копии
	 *
	 * @param int $folder_uid                Folder id.
	 * @param int $max_process_per_request   кол-во аттачментов за 1 запуск
	 *
	 * @return array
	 */
	public function restoreFolderFromBackup( $folder_uid, $max_process_per_request = 5 ) {
		WRIO_Plugin::app()->updatePopulateOption( 'cron_running', false ); // останавливаем крон

		global $wpdb;

		$db_table         = RIO_Process_Queue::table_name();
		$optimized_count  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$db_table} WHERE item_type = 'cf_image' AND item_hash_alternative = %s AND result_status = 'success' LIMIT 1;", $folder_uid ) );
		$optimized_images = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$db_table} WHERE item_type = 'cf_image' AND item_hash_alternative = %s AND result_status = 'success' LIMIT %d", $folder_uid, $max_process_per_request ) );

		$images_count = 0;
		if ( $optimized_images ) {
			$images_count = count( $optimized_images );
		}

		$image_statistics = WRIO_Image_Statistic_Folders::get_instance();

		// обработка
		if ( $images_count ) {
			foreach ( $optimized_images as $row ) {
				$image_id = intval( $row->id );
				$cf_image = $this->getImage( $image_id );
				if ( $cf_image->isOptimized() ) {
					$restored = $cf_image->restore();

					if ( ! is_wp_error( $restored ) ) {
						$optimization_data = $cf_image->getOptimizationData();
						$optimized_size    = $optimization_data->get_final_size();
						$original_size     = $optimization_data->get_original_size();
						$image_statistics->deductFromField( 'optimized_size', $optimized_size );
						$image_statistics->deductFromField( 'original_size', $original_size );

						$folder = $this->getFolder( $cf_image->get( 'folder_uid' ) );
						$folder->reCountOptimizedFiles();

						WRIO_Logger::error( sprintf( 'Failed to restore custom folder. Object info: %s', wp_json_encode( $cf_image ) ) );
					}
				}
			}
			$this->saveFolders();
			$image_statistics->save();
		}
		$remain = $optimized_count - $images_count;

		return [
			'remain' => $remain,
		];
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

		$wpdb->update( $db_table, [ 'result_status' => 'unoptimized' ], [
			'item_type'     => 'cf_image',
			'result_status' => 'error'
		] );
		//do_action( 'wbcr/rio/multisite_restore_blog' );
	}

	/**
	 * Хук возвращает ajax action для кнопки оптимизации.
	 *
	 * @param string $action
	 * @param string $type
	 *
	 * @return string
	 */
	public function optimizeAjaxAction( $action, $type ) {
		if ( $type == 'custom-folders' ) {
			return 'wriop_process_cf_images';
		}

		return $action;
	}

	/**
	 * Хук возвращает ajax action для кнопки переоптимизации.
	 *
	 * @param string $action
	 * @param string $type
	 *
	 * @return string
	 */
	public function reoptimizeAjaxAction( $action, $type ) {
		if ( $type == 'custom-folders' ) {
			return 'wio_cf_reoptimize_image';
		}

		return $action;
	}

	/**
	 * Хук возвращает ajax action для кнопки восстановления.
	 *
	 * @param string $action
	 * @param string $type
	 *
	 * @return string
	 */
	public function restoreAjaxAction( $action, $type ) {
		if ( $type == 'custom-folders' ) {
			return 'wio_cf_restore_image';
		}

		return $action;
	}

	/**
	 * Возвращает блок оптимизации.
	 *
	 * @param int $image_id
	 *
	 * @return string
	 */
	public function getMediaColumnContent( $image_id ) {
		$media_library = WRIO_Media_Library::get_instance();
		$params        = $this->calculateParams( $image_id );

		return $media_library->getMediaColumnTemplate( $params, 'custom-folders' );
	}

	/**
	 * Просчитывает параметры блока оптимизации
	 *
	 * @param int $image_id   номер изображения nextgen
	 *
	 * @return array $params
	 */
	public function calculateParams( $image_id ) {
		$cf_image             = new WRIO_Folder_Image( $image_id );
		$isOptimized          = $cf_image->isOptimized();
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
			$optimization_data = $cf_image->getOptimizationData();
			/**
			 * @var WRIO_CF_Image_Extra_Data $extra_data
			 */
			$extra_data           = $optimization_data->get_extra_data();
			$optimization_level   = $optimization_data->get_processing_level();
			$original_main_size   = $optimization_data->get_original_size();
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
			'thumbnails_optimized' => 0,
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
	 * Возвращает процент оптимизации
	 * Фильтр wbcr/rio/optimize_template/optimized_percent
	 *
	 * @param int    $percent   процент оптимизации
	 * @param string $type      тип страницы
	 *
	 * @return int процент оптимизации
	 */
	public function optimizedPercent( $percent, $type ) {
		if ( 'custom-folders' == $type ) {
			$image_statistics = WRIO_Image_Statistic_Folders::get_instance();

			return $image_statistics->getOptimizedPercent();
		}

		return $percent;
	}

	/**
	 * Сохраняет WebP размер для cf_image
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
		if ( $item_type != 'cf_image' ) {
			return false;
		}
		$optimization_data = RIO_Process_Queue::find_by_hash( $queue_model->get_item_hash_alternative() );
		if ( ! $optimization_data ) {
			return false;
		}
		/**
		 * @var WRIO_CF_Image_Extra_Data $extra_data
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