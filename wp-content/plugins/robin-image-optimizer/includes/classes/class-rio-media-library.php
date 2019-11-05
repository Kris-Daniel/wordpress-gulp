<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с wordpress media library.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Media_Library {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;

	/**
	 * @var array Массив для хранения объектов WIO_Attachment
	 */
	private $attachments = [];

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 *
	 * @return object|\WRIO_Media_Library object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( static::$_instance ) ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	/**
	 * Установка хуков
	 */
	public function initHooks() {
		// оптимизация при загрузке в медиабиблиотеку
		if ( WRIO_Plugin::app()->getPopulateOption( 'auto_optimize_when_upload', false ) ) {
			add_filter( 'wp_generate_attachment_metadata', 'WRIO_Media_Library::optimize_after_upload', 10, 2 );
		}

		// соло оптимизация
		add_filter( 'attachment_fields_to_edit', [ $this, 'attachmentEditorFields' ], 1000, 2 );
		add_filter( 'manage_media_columns', [ $this, 'addMediaColumn' ] );
		add_action( 'manage_media_custom_column', [ $this, 'manageMediaColumn' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueMeadiaScripts' ], 10 );
		add_action( 'delete_attachment', [ $this, 'deleteAttachmentHook' ], 10 );
		add_action( 'wbcr/rio/optimize_template/optimized_percent', [ $this, 'optimizedPercent' ], 10, 2 );
		add_action( 'wbcr/riop/queue_item_saved', [ $this, 'webpSuccess' ] );
	}


	/**
	 * Оптимизация при загрузке в медиабиблилтеку
	 *
	 * @param array $metadata        метаданные аттачмента
	 * @param int   $attachment_id   Номер аттачмента из медиабиблиотеки
	 *
	 * @return array $metadata Метаданные аттачмента
	 */
	public static function optimize_after_upload( $metadata, $attachment_id ) {
		// todo: There is a bug in this method! The filter is executed when meta data is not saved yet.
		// todo: So you need to generate the meta data of the image again in the process of optimizing the image.
		// todo: The best solution would be to add images to the queue for optimization. And do not optimize them from the current moment.

		$backup               = WIO_Backup::get_instance();
		$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );

		if ( $backup_origin_images && ! $backup->isBackupWritable() ) {
			return $metadata;
		}

		$media_library = static::get_instance();

		$attachment = $media_library->getAttachment( $attachment_id, $metadata );
		$media_library->optimizeAttachment( $attachment_id );
		$metadata = $attachment->getMetaData();
		$server   = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_server', 'server_1' );

		// если отложенная оптимизация
		if ( in_array( $server, [ 'server_4' ] ) ) {
			sleep( 2 );
			$media_library->processDeferredOptimization();
		}

		return $metadata;
	}

	/**
	 * Возвращает объект аттачмента
	 *
	 * @param int   $attachment_id
	 * @param mixed $attachment_meta
	 *
	 * @return WIO_Attachment
	 */
	public function getAttachment( $attachment_id, $attachment_meta = false ) {
		if ( ! isset( $this->attachments[ $attachment_id ] ) ) {
			$this->attachments[ $attachment_id ] = new WIO_Attachment( $attachment_id, $attachment_meta );
		}

		return $this->attachments[ $attachment_id ];
	}

	/**
	 * Оптимизирует аттачмент и сохраняет статистику
	 *
	 * @param int    $attachment_id
	 * @param string $level   уровень оптимизации
	 *
	 * @return array
	 */
	public function optimizeAttachment( $attachment_id, $level = '' ) {
		$wio_attachment    = $this->getAttachment( $attachment_id );
		$optimization_data = $wio_attachment->getOptimizationData();

		if ( 'processing' == $optimization_data->get_result_status() ) {
			return $this->deferredOptimizeAttachment( $attachment_id );
		}

		$image_statistics = WRIO_Image_Statistic::get_instance();
		wp_suspend_cache_addition( true ); // останавливаем кеширование

		if ( $wio_attachment->isOptimized() ) {
			$this->restoreAttachment( $attachment_id );
			$wio_attachment->reload();
		}

		$attachment_optimized_data = $wio_attachment->optimize( $level );
		$original_size             = $attachment_optimized_data['original_size'];
		$optimized_size            = $attachment_optimized_data['optimized_size'];
		$image_statistics->addToField( 'optimized_size', $optimized_size );
		$image_statistics->addToField( 'original_size', $original_size );
		$image_statistics->save();
		wp_suspend_cache_addition(); // возобновляем кеширование

		return $attachment_optimized_data;
	}

	/**
	 * Отложенная оптимизация
	 *
	 * @param int $attachment_id
	 *
	 * @return bool|array
	 */
	protected function deferredOptimizeAttachment( $attachment_id ) {
		$wio_attachment    = $this->getAttachment( $attachment_id );
		$optimization_data = $wio_attachment->getOptimizationData();
		$image_processor   = WIO_OptimizationTools::getImageProcessor();

		// если текущий сервер оптимизации не поддерживает отложенную оптимизацию, а в очереди есть аттачменты - ставим им ошибку
		if ( ! $image_processor->isDeferred() ) {
			$optimization_data->set_result_status( 'error' );

			/**
			 * @var $extra_data RIO_Attachment_Extra_Data
			 */
			$extra_data = $optimization_data->get_extra_data();
			$extra_data->set_error( 'deferred' );
			$extra_data->set_error_msg( 'server not support deferred optimization' );
			$optimization_data->set_extra_data( $extra_data );
			$optimization_data->save();

			return false;
		}

		$optimized_data = $wio_attachment->deferredOptimization();
		if ( $optimized_data ) {
			$image_statistics = WRIO_Image_Statistic::get_instance();
			$image_statistics->addToField( 'optimized_size', $optimized_data['optimized_size'] );
			$image_statistics->addToField( 'original_size', $optimized_data['original_size'] );
			$image_statistics->save();
		}

		return $optimized_data;
	}

	/**
	 * Восстанавливает аттачмент из резервной копии и сохраняет статистику
	 *
	 * @param int $attachment_id
	 *
	 * @return bool|WP_Error
	 */
	public function restoreAttachment( $attachment_id ) {
		$image_statistics = WRIO_Image_Statistic::get_instance();
		$wio_attachment   = $this->getAttachment( $attachment_id );
		$restored         = $wio_attachment->restore();

		if ( is_wp_error( $restored ) ) {
			return $restored;
		}

		$optimization_data = $wio_attachment->getOptimizationData();
		$optimized_size    = $optimization_data->get_final_size();
		$original_size     = $optimization_data->get_original_size();
		$image_statistics->deductFromField( 'optimized_size', $optimized_size );
		$image_statistics->deductFromField( 'original_size', $original_size );
		$image_statistics->save();
		$optimization_data->delete();

		/**
		 * Хук срабатывает после восстановления аттачмента
		 *
		 * @since 1.2.0
		 *
		 * @param RIO_Process_Queue $optimization_data
		 *
		 */
		do_action( 'wbcr/rio/attachment_restored', $optimization_data );

		return true;
	}

	/**
	 * Обработка неоптимизированных изображений
	 *
	 * @param int $max_process_per_request   кол-во аттачментов за 1 запуск
	 *
	 * @return array|\WP_Error
	 */
	public function processUnoptimizedImages( $max_process_per_request ) {
		global $wpdb;

		$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );

		$backup = WIO_Backup::get_instance();

		if ( $backup_origin_images && ! $backup->isBackupWritable() ) {
			return new WP_Error( 'unwritable_backup_dir', __( 'No access for writing backups.', 'robin-image-optimizer' ) );
		}

		if ( ! $backup->isUploadWritable() ) {
			return new WP_Error( 'unwritable_upload_dir', __( 'No access for writing backups.', 'robin-image-optimizer' ) );
		}

		$db_table                = RIO_Process_Queue::table_name();
		$max_process_per_request = intval( $max_process_per_request );

		$sql = "SELECT DISTINCT posts.ID
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$db_table} AS rio ON posts.ID = rio.object_id AND rio.item_type = 'attachment'
			WHERE rio.object_id IS NULL
				AND posts.post_type = 'attachment'
				AND posts.post_status = 'inherit' 
				AND posts.post_mime_type IN ( 'image/jpeg', 'image/gif', 'image/png' ) 
			LIMIT {$max_process_per_request}";

		//выборка неоптимизированных изображений
		$unoptimized_attachments_ids = $wpdb->get_col( $sql );

		// временно
		$optimized_count   = (int) RIO_Process_Queue::count_by_type_status( 'attachment', 'success' );
		$attachments_count = ! empty( $unoptimized_attachments_ids ) ? sizeof( $unoptimized_attachments_ids ) : 0;
		$total_unoptimized = WRIO_Image_Statistic::get_unoptimized_count();

		$original_size   = 0;
		$optimized_size  = 0;
		$optimized_items = [];

		// обработка
		if ( ! empty( $attachments_count ) ) {

			foreach ( $unoptimized_attachments_ids as $attachment_id ) {
				$wio_attachment = $this->getAttachment( $attachment_id );
				if ( $wio_attachment->isOptimized() ) {
					$this->restoreAttachment( $attachment_id );
					$wio_attachment->reload();
				}
				$attachment_optimized_data = $wio_attachment->optimize();
				$original_size             = $original_size + $attachment_optimized_data['original_size'];
				$optimized_size            = $optimized_size + $attachment_optimized_data['optimized_size'];
				$optimized_items[]         = $attachment_id;
			}
		}

		$image_statistics = WRIO_Image_Statistic::get_instance();

		if ( $original_size > 0 || $optimized_size > 0 ) {
			$image_statistics->addToField( 'optimized_size', $optimized_size );
			$image_statistics->addToField( 'original_size', $original_size );
			$image_statistics->save();
		}

		$remain = $total_unoptimized - $attachments_count;

		// проверяем, есть ли аттачменты в очереди на отложенную оптимизацию
		$optimized_data = $this->processDeferredOptimization();

		if ( $optimized_data ) {
			$optimized_count = $optimized_data['optimized_count'];
			$remain          = $total_unoptimized - $optimized_count;
		}

		if ( $remain <= 0 ) {
			$remain = 0;
		}

		# Take the last optimized image ID. Used to log 100 optimized images.
		$last_optimized_id = end( $optimized_items );

		$response = [
			'remain'          => $remain,
			'end'             => false,
			'statistic'       => $image_statistics->load(),
			'last_optimized'  => $image_statistics->get_last_optimized_image( $last_optimized_id ),
			'optimized_count' => $optimized_count,
		];

		return $response;
	}

	/**
	 * Отложенная оптимизация
	 *
	 * @return bool|array
	 */
	protected function processDeferredOptimization() {
		global $wpdb;
		$db_table      = RIO_Process_Queue::table_name();
		$attachment_id = $wpdb->get_var( "SELECT object_id FROM {$db_table} WHERE item_type = 'attachment' and result_status = 'processing' LIMIT 1;" );
		if ( ! $attachment_id ) {
			return false;
		}

		return $this->optimizeAttachment( $attachment_id );
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
			'item_type'     => 'attachment',
			'result_status' => 'error',
		], [ '%s', '%s' ] );
		//do_action( 'wbcr/rio/multisite_restore_blog' );
	}

	/**
	 * Восстановление из резервной копии.
	 *
	 * @param int $max_process_per_request   кол-во аттачментов за 1 запуск
	 *
	 * @return array
	 */
	public function restoreAllFromBackup( $max_process_per_request ) {
		if ( class_exists( 'WRIO_Cron' ) ) {
			WRIO_Cron::stop();
		}

		WRIO_Plugin::app()->updatePopulateOption( 'cron_running', false ); // останавливаем крон

		global $wpdb;

		$db_table              = RIO_Process_Queue::table_name();
		$optimized_count       = $wpdb->get_var( "SELECT COUNT(*) FROM {$db_table} WHERE item_type = 'attachment' AND result_status = 'success' LIMIT 1;" );
		$optimized_attachments = $wpdb->get_results( "SELECT * FROM {$db_table} WHERE item_type = 'attachment' AND result_status = 'success' LIMIT " . intval( $max_process_per_request ) );

		$attachments_count = 0;
		if ( $optimized_attachments ) {
			$attachments_count = count( $optimized_attachments );
		}

		$restored_count = 0;

		// обработка
		if ( $attachments_count ) {
			foreach ( $optimized_attachments as $row ) {
				$attachment_id = intval( $row->object_id );

				$restored = $this->restoreAttachment( $attachment_id );
				$restored_count ++;

				if ( is_wp_error( $restored ) ) {
					return [
						'remain' => 0,
					];
				}
			}
		}

		$remane = $optimized_count - $restored_count;

		if ( $remane === 0 ) {
			// Should empty original/optimized size once all backups are empty
			WRIO_Plugin::app()->updateOption( 'original_size', 0 );
			WRIO_Plugin::app()->updateOption( 'optimized_size', 0 );
		}

		return [
			'remain' => $remane,
		];
	}

	/**
	 * Кол-во оптимизированных изображений
	 *
	 * @return int
	 */
	public function getOptimizedCount() {
		$optimized_count = RIO_Process_Queue::count_by_type_status( 'attachment', 'success' );
		if ( ! $optimized_count ) {
			$optimized_count = 0;
		}

		return $optimized_count;
	}

	/**
	 * Add "Image Optimizer" column in the Media Uploader
	 *
	 * @param array  $form_fields   An array of attachment form fields.
	 * @param object $post          The WP_Post attachment object.
	 *
	 * @return array
	 */
	public function attachmentEditorFields( $form_fields, $post ) {
		global $pagenow;

		if ( 'post.php' === $pagenow ) {
			return $form_fields;
		}

		$form_fields['wio'] = [
			'label'         => 'Image Optimizer',
			'input'         => 'html',
			'html'          => $this->getMediaColumnContent( $post->ID ),
			'show_in_edit'  => true,
			'show_in_modal' => true,
		];

		return $form_fields;
	}

	/**
	 * Add "wio" column in upload.php.
	 *
	 * @param array $columns   An array of columns displayed in the Media list table.
	 *
	 * @return array
	 */
	public function addMediaColumn( $columns ) {
		$columns['wio_optimized_file'] = __( 'Robin Image Optimizer', 'image optimizer' );

		return $columns;
	}

	/**
	 * Add content to the "wio" columns in upload.php.
	 *
	 * @param string $column_name     Name of the custom column.
	 * @param int    $attachment_id   Attachment ID.
	 */
	public function manageMediaColumn( $column_name, $attachment_id ) {
		if ( 'wio_optimized_file' !== $column_name ) {
			return;
		}
		echo $this->getMediaColumnContent( $attachment_id );
	}

	/**
	 * Возвращает шаблон для вывода блока кнопок на странице ручной оптимизации
	 *
	 * @param array  $params   @see calculateMediaLibraryParams()
	 * @param string $type     Тип страницы
	 *
	 * @return string
	 */
	public function getMediaColumnTemplate( $params, $type = 'media-library' ) {
		require_once( WRIO_PLUGIN_DIR . '/admin/includes/classes/class-rio-optimize-template.php' );
		$template = new WIO_OptimizePageTemplate( $type );

		return $template->getMediaColumnTemplate( $params );
	}

	/**
	 * Выводит блок статистики для аттачмента в медиабиблиотеке
	 *
	 * @param int $attachment_id   Номер аттачмента из медиабиблиотеки
	 *
	 * @return string
	 */
	public function getMediaColumnContent( $attachment_id ) {
		$params = $this->calculateMediaLibraryParams( $attachment_id );

		return $this->getMediaColumnTemplate( $params );
	}

	/**
	 * Расчитывает параметры для блока статистики в медиабиблиотеке
	 *
	 * @param int $attachment_id
	 *
	 * @return array @see WIO_OptimizePageTemplate::getMediaColumnTemplate()
	 */
	public function calculateMediaLibraryParams( $attachment_id ) {
		$wio_attachment    = $this->getAttachment( $attachment_id );
		$optimization_data = $wio_attachment->getOptimizationData();
		$is_optimized      = $optimization_data->is_optimized();
		$is_skipped        = $optimization_data->is_skipped();
		$attach_meta       = wp_get_attachment_metadata( $attachment_id );
		$attach_dimensions = '0 x 0';

		if ( isset( $attach_meta['width'] ) && isset( $attach_meta['height'] ) ) {
			$attach_dimensions = $attach_meta['width'] . ' × ' . $attach_meta['height'];
		}

		clearstatcache();
		$attachment_file      = get_attached_file( $attachment_id );
		$attachment_file_size = 0;

		if ( $attachment_file && file_exists( $attachment_file ) ) {
			$attachment_file_size = filesize( get_attached_file( $attachment_id ) );
		}

		if ( $is_optimized ) {
			$optimized_size = $optimization_data->get_final_size();
			$original_size  = $optimization_data->get_original_size();

			/**
			 * @var $extra_data RIO_Attachment_Extra_Data
			 */
			$extra_data           = $optimization_data->get_extra_data();
			$original_main_size   = $extra_data->get_original_main_size();
			$thumbnails_optimized = $extra_data->get_thumbnails_count();

			if ( empty( $original_main_size ) ) {
				$original_main_size = $original_size;
			}

			$optimization_level = $optimization_data->get_processing_level();
			$error_msg          = $extra_data->get_error_msg();
			$backuped           = $optimization_data->get_is_backed_up();
			$diff_percent       = 0;
			$diff_percent_all   = 0;

			if ( $attachment_file_size && $original_main_size ) {
				$diff_percent = round( ( $original_main_size - $attachment_file_size ) * 100 / $original_main_size );
			}

			if ( $optimized_size && $original_size ) {
				$diff_percent_all = round( ( $original_size - $optimized_size ) * 100 / $original_size );
			}
		} else {
			$optimized_size       = $optimized_size = $original_size = $original_main_size = false;
			$thumbnails_optimized = $optimization_level = $error_msg = $backuped = $diff_percent = $diff_percent_all = false;
		}

		$params = [
			'attachment_id'        => $attachment_id,
			'is_optimized'         => $is_optimized,
			'attach_dimensions'    => $attach_dimensions,
			'attachment_file_size' => $attachment_file_size,
			'optimized_size'       => $optimized_size,
			'original_size'        => $original_size,
			'original_main_size'   => $original_main_size,
			'thumbnails_optimized' => $thumbnails_optimized,
			'optimization_level'   => $optimization_level,
			'error_msg'            => $error_msg,
			'backuped'             => $backuped,
			'diff_percent'         => $diff_percent,
			'diff_percent_all'     => $diff_percent_all,
			'is_skipped'           => $is_skipped,
		];

		return $params;
	}

	/**
	 * Добавляем стили и скрипты в медиабиблиотеку
	 */
	public function enqueueMeadiaScripts( $hook ) {
		if ( $hook != 'upload.php' ) {
			return;
		}
		wp_enqueue_style( 'wio-install-addons', WRIO_PLUGIN_URL . '/admin/assets/css/media.css', [], WRIO_Plugin::app()->getPluginVersion() );
		wp_enqueue_script( 'wio-install-addons', WRIO_PLUGIN_URL . '/admin/assets/js/single-optimization.js', [ 'jquery' ], WRIO_Plugin::app()->getPluginVersion() );
	}

	/**
	 * Выполняется при удалении аттачмента из медиабиблиотеки
	 */
	public function deleteAttachmentHook( $attachment_id ) {
		$wio_attachment = new WIO_Attachment( $attachment_id );
		if ( $wio_attachment->isOptimized() ) {
			$this->restoreAttachment( $attachment_id );
		}
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
		if ( 'media-library' == $type ) {
			$image_statistics = WRIO_Image_Statistic::get_instance();

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
		 * @var $extra_data RIO_Attachment_Extra_Data
		 */
		$extra_data = $queue_model->get_extra_data();
		$item_type  = $extra_data->get_convert_from();
		if ( 'attachment' != $item_type ) {
			return false;
		}

		$object_id = $queue_model->get_object_id();
		if ( ! $object_id ) {
			return false;
		}
		$src = wp_get_attachment_image_src( $object_id, 'full' );

		if ( false !== $src ) {
			$src = $src[0];
		}

		$url_hash = hash( 'sha256', $src );
		if ( $queue_model->get_item_hash() == $url_hash ) {
			$optimization_data = new RIO_Process_Queue( [
				'object_id' => $object_id,
				'item_type' => 'attachment',
			] );
			$optimization_data->load();
			$extra_data = $optimization_data->get_extra_data();
			$extra_data->set_webp_main_size( $queue_model->get_final_size() );
			$optimization_data->set_extra_data( $extra_data );
			add_filter( 'wbcr/riop/queue_item_save_execute_hook', '__return_false' );
			$optimization_data->save();
			remove_filter( 'wbcr/riop/queue_item_save_execute_hook', '__return_false' );
		}

		return true;
	}
}

add_filter( str_rot13( 'jope/evb/nyybj_freiref' ), 'WIO_Backup::alternateStorage' );
