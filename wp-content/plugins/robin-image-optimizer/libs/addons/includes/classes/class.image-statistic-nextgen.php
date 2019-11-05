<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы со статистическими данными по оптимизации изображений
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Image_Statistic_Nextgen extends WRIO_Image_Statistic {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;

	/**
	 * Сохранение статистики
	 */
	public function save() {
		WRIO_Plugin::app()->updateOption( 'nextgen_original_size', $this->statistic['original_size'] );
		WRIO_Plugin::app()->updateOption( 'nextgen_optimized_size', $this->statistic['optimized_size'] );
	}

	/**
	 * Загрузка статистики и расчёт некоторых параметров
	 *
	 * @return array
	 */
	public function load() {
		$original_size  = WRIO_Plugin::app()->getOption( 'nextgen_original_size', 0 );
		$optimized_size = WRIO_Plugin::app()->getOption( 'nextgen_optimized_size', 0 );
		global $wpdb;
		$io_db_table = RIO_Process_Queue::table_name();

		$sql_unoptimized = "SELECT COUNT(*)
			FROM {$wpdb->prefix}ngg_pictures as t1 LEFT JOIN {$io_db_table} as t2 
			ON t2.item_type = 'nextgen' and t1.pid = t2.object_id
			WHERE t2.result_status Is Null or ( t2.result_status != 'success' and t2.result_status != 'error' )";

		$error_count  = RIO_Process_Queue::count_by_type_status( 'nextgen', 'error' );
		$total_images = $this->getTotalCount();
		if ( ! $total_images ) {
			$total_images = 0;
			if ( $original_size || $optimized_size ) {
				// если нет картинок, то и размеров не должно быть
				$original_size  = $this->statistic['original_size'] = 0;
				$optimized_size = $this->statistic['optimized_size'] = 0;
				$this->save();
			}
		}
		$unoptimized = $wpdb->get_var( $sql_unoptimized );
		if ( $optimized_size and $original_size ) {
			$percent_diff      = round( ( $original_size - $optimized_size ) * 100 / $original_size, 1 );
			$percent_diff_line = round( $optimized_size * 100 / $original_size, 0 );
		} else {
			$percent_diff      = 0;
			$percent_diff_line = 100;
		}
		$optimized_exists_images_count = $total_images - $unoptimized - $error_count; // оптимизированные картинки, которые сейчас есть в медиабиблиотеке
		if ( $total_images ) {
			$optimized_images_percent = round( $optimized_exists_images_count * 100 / $total_images );
		} else {
			$optimized_images_percent = 0;
		}

		return [
			'original'          => $total_images,
			'optimized'         => $optimized_exists_images_count,
			'optimized_percent' => $optimized_images_percent,
			'percent_line'      => $percent_diff_line,
			'unoptimized'       => $unoptimized,
			'optimized_size'    => $optimized_size,
			'original_size'     => $original_size,
			'save_size_percent' => $percent_diff,
			'error'             => $error_count,
		];
	}

	/**
	 * Общее кол-во изображений nextgen
	 */
	public function getTotalCount() {
		global $wpdb;
		$sql_total    = "SELECT COUNT(*) FROM {$wpdb->prefix}ngg_pictures";
		$total_images = $wpdb->get_var( $sql_total );

		return $total_images;
	}

	/**
	 * Кол-во неоптимизированных изображений
	 */
	public function getUnoptimizedCount() {
		global $wpdb;
		$io_db_table     = RIO_Process_Queue::table_name();
		$sql_unoptimized = "SELECT COUNT(*)
			FROM {$wpdb->prefix}ngg_pictures as t1 LEFT JOIN {$io_db_table} as t2 
			ON t2.item_type = 'nextgen' and t1.pid = t2.object_id
			WHERE t2.result_status Is Null or ( t2.result_status != 'success' and t2.result_status != 'error' )";
		$total_images    = $wpdb->get_var( $sql_unoptimized );

		return $total_images;
	}

	/**
	 * Возвращает неоптимизированные изображения
	 *
	 * @param string $limit   ограничение выборки
	 *
	 * @return array
	 */
	public function getUnoptimized( $limit = 10 ) {
		global $wpdb;
		$io_db_table     = RIO_Process_Queue::table_name();
		$sql_unoptimized = "SELECT *
			FROM {$wpdb->prefix}ngg_pictures as t1 LEFT JOIN {$io_db_table} as t2 
			ON t2.item_type = 'nextgen' and t1.pid = t2.object_id
			WHERE t2.result_status Is Null or ( t2.result_status != 'success' and t2.result_status != 'error' )
			LIMIT " . intval( $limit );
		$unoptimized     = $wpdb->get_results( $sql_unoptimized );

		return $unoptimized;
	}

	/**
	 * Возвращает результат последних оптимизаций изображений
	 *
	 * @param int   $limit            лимит
	 *
	 * @return array {
	 *     Параметры
	 * @type string $id               id
	 * @type string $file_name        Имя файла
	 * @type string $url              URL
	 * @type string $thumbnail_url    URL превьюшки
	 * @type string $original_size    Размер до оптимизации
	 * @type string $optimized_size   Размер после оптимизации
	 * @type string $webp_size        webP размер
	 * @type string $original_saving  На сколько процентов изменился главный файл
	 * @type string $thumbnails_count Сколько превьюшек оптимизировано
	 * @type string $total_saving     Процент оптимизации главного файла и превьюшек
	 * }
	 */
	public function get_last_optimized_images( $limit = 100 ) {
		global $wpdb;
		$logs             = [];
		$db_table         = RIO_Process_Queue::table_name();
		$sql              = $wpdb->prepare( "SELECT t1.*,t2.filename as file_name, t3.path as gallery_path
					FROM {$db_table} as t1 
					LEFT JOIN {$wpdb->prefix}ngg_pictures as t2 ON t1.object_id = t2.pid
					LEFT JOIN {$wpdb->prefix}ngg_gallery as t3 ON t2.galleryid = t3.gid 
					WHERE t1.item_type = 'nextgen' AND t1.result_status IN (%s, %s)
					ORDER BY id DESC
					LIMIT %d ;", RIO_Process_Queue::STATUS_SUCCESS, RIO_Process_Queue::STATUS_ERROR, $limit );
		$optimized_images = $wpdb->get_results( $sql );

		if ( empty( $optimized_images ) ) {
			return [];
		}

		foreach ( $optimized_images as $row ) {
			$log = [];

			$optimization_data = new RIO_Process_Queue( $row );

			/**
			 * @var WRIO_Nextgen_Extra_Data $extra_data
			 */
			$extra_data = $optimization_data->get_extra_data();

			$original_main_size = 0;

			if ( ! empty( $extra_data ) ) {
				$original_main_size = $extra_data->get_original_main_size();
				$webp_size          = $extra_data->get_webp_main_size();
				if ( ! empty( $webp_size ) ) {
					$log['webp_size'] = size_format( $webp_size, 2 );
				} else {
					$log['webp_size'] = '-';
				}

				$error = $extra_data->get_error();

				if ( $row->result_status == RIO_Process_Queue::STATUS_ERROR || ! empty( $error ) ) {
					$log['type'] = 'error';

					$error_message = $extra_data->get_error_msg();

					$log['error_msg'] = ! empty( $error_message ) ? $error_message : esc_html__( 'Unknown error', 'robin-image-optimizer' );
				}
			}

			$main_file                = trailingslashit( ABSPATH ) . $row->gallery_path . $row->file_name;
			$main_file_optimized_size = $main_saving = $total_saving = 0;

			if ( file_exists( $main_file ) ) {
				$main_file_optimized_size = filesize( $main_file );
			}

			if ( $original_main_size ) {
				$main_saving = ( $original_main_size - $main_file_optimized_size ) * 100 / $original_main_size;
			}

			if ( $row->original_size ) {
				$total_saving = ( $row->original_size - $row->final_size ) * 100 / $row->original_size;
			}

			$image_url     = home_url( $row->gallery_path . $row->file_name );
			$thumbnail_url = home_url( $row->gallery_path . 'thumbs/thumbs_' . $row->file_name );

			$logs[] = array_merge( $log, [
				'id'               => $row->id,
				'url'              => $image_url,
				'thumbnail_url'    => $thumbnail_url,
				'file_name'        => preg_replace( '/^.+[\\\\\\/]/', '', $main_file ),
				'original_size'    => size_format( $row->original_size, 2 ),
				'optimized_size'   => size_format( $row->final_size, 2 ),
				'original_saving'  => round( $main_saving ) . '%',
				'thumbnails_count' => 1,
				'total_saving'     => round( $total_saving ) . '%',
			] );
		}

		return $logs;
	}

}
