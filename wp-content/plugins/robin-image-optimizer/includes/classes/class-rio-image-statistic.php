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
class WRIO_Image_Statistic {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;

	/**
	 * @var array
	 * @see WRIO_Image_Statistic::load()
	 */
	protected $statistic;

	/**
	 * Инициализация статистики
	 *
	 * @throws Exception
	 */
	public function __construct() {
		$this->statistic = $this->load();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 *
	 * @return object|\static object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( static::$_instance ) ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	/**
	 * Возвращает статистические данные
	 *
	 * @return array
	 */
	public function get() {
		return $this->statistic;
	}

	/**
	 * Добавляет новые данные к текущей статистике
	 * К текущим числам добавляются новые
	 *
	 * @param string $field   Поле, к которому добавляем значение
	 * @param int    $value   добавляемое значение
	 */
	public function addToField( $field, $value ) {
		if ( isset( $this->statistic[ $field ] ) ) {
			$this->statistic[ $field ] = $this->statistic[ $field ] + $value;
		}
	}

	/**
	 * Вычитает данные из текущей статистики
	 * Из текущего числа вычитается
	 *
	 * @param string $field   Поле, из которого вычитается значение
	 * @param int    $value   вычитаемое значение
	 */
	public function deductFromField( $field, $value ) {
		$value = (int) $value;
		if ( isset( $this->statistic[ $field ] ) ) {
			$this->statistic[ $field ] = $this->statistic[ $field ] - $value;
			if ( $this->statistic[ $field ] < 0 ) {
				$this->statistic[ $field ] = 0;
			}
		}
	}

	/**
	 * Сохранение статистики
	 */
	public function save() {
		WRIO_Plugin::app()->updateOption( 'original_size', $this->statistic['original_size'] );
		WRIO_Plugin::app()->updateOption( 'optimized_size', $this->statistic['optimized_size'] );
	}

	/**
	 * Загрузка статистики и расчёт некоторых параметров
	 *
	 * @return array {
	 *              Параметры
	 *              {type} int $original Всего картинок
	 *              {type} int $optimized Всего оптимизировано картинок
	 *              {type} int $optimized_percent Суммарная оптимизация относитально всех картинок в процентах
	 *              {type} int $percent_line Суммарный КПД оптимизации в процентах. Для вывода линии в интерфейсе.
	 *              {type} int $unoptimized Не оптимизировано
	 *              {type} int $optimized_size Всего оптимизировано. В байтах
	 *              {type} int $original_size Оригинальный размер всех картинок. В байтах
	 *              {type} int $save_size_percent Суммарный КПД оптимизации в процентах
	 *              {type} int $error Кол-во ошибок
	 * }
	 */
	public function load() {
		$original_size  = WRIO_Plugin::app()->getOption( 'original_size', 0 );
		$optimized_size = WRIO_Plugin::app()->getOption( 'optimized_size', 0 );

		global $wpdb;
		$sql             = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type IN ( 'image/jpeg', 'image/gif', 'image/png' );";
		$total_images    = $wpdb->get_var( $sql );
		$error_count     = RIO_Process_Queue::count_by_type_status( 'attachment', 'error' );
		$optimized_count = RIO_Process_Queue::count_by_type_status( 'attachment', 'success' );

		if ( ! $total_images ) {
			$total_images = 0;
		}
		if ( ! $error_count ) {
			$error_count = 0;
		}
		if ( ! $optimized_count ) {
			$optimized_count = 0;
		}
		// unoptimized count: all - optimized - error
		$unoptimized_count = static::get_unoptimized_count();
		if ( $unoptimized_count < 0 ) {
			$unoptimized_count = 0;
		}
		if ( $optimized_size && $original_size ) {
			$percent_diff      = round( ( $original_size - $optimized_size ) * 100 / $original_size, 1 );
			$percent_diff_line = round( $optimized_size * 100 / $original_size, 0 );
		} else {
			$percent_diff      = 0;
			$percent_diff_line = 100;
		}
		if ( $total_images ) {
			$optimized_images_percent = floor( $optimized_count * 100 / $total_images );
		} else {
			$optimized_images_percent = 0;
		}

		return [
			'original'          => $total_images,
			'optimized'         => $optimized_count,
			'optimized_percent' => $optimized_images_percent,
			'percent_line'      => $percent_diff_line,
			'unoptimized'       => $unoptimized_count,
			'optimized_size'    => $optimized_size,
			'original_size'     => $original_size,
			'save_size_percent' => $percent_diff,
			'error'             => $error_count,
		];
	}

	/**
	 * Count of non-optimized images
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 * @return int
	 */
	public static function get_unoptimized_count() {
		global $wpdb;
		$db_table = RIO_Process_Queue::table_name();

		$sql_unoptimized = "SELECT COUNT(DISTINCT p.ID) 
			FROM {$wpdb->posts} p
			WHERE NOT EXISTS (SELECT * 
			     FROM {$db_table} 
			     WHERE p.ID = object_id AND p.post_type = item_type) 
			AND p.post_type = 'attachment' AND p.post_mime_type IN ( 'image/jpeg', 'image/gif', 'image/png' )";

		$total_images = $wpdb->get_var( $sql_unoptimized );

		return (int) $total_images;
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

		$items    = [];
		$db_table = RIO_Process_Queue::table_name();
		$sql      = $wpdb->prepare( "SELECT *	FROM {$db_table}					
					WHERE item_type = 'attachment' AND result_status IN (%s, %s)
					ORDER BY id DESC
					LIMIT %d ;", RIO_Process_Queue::STATUS_SUCCESS, RIO_Process_Queue::STATUS_ERROR, $limit );

		$optimized_images = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! empty( $optimized_images ) ) {
			foreach ( $optimized_images as $row ) {
				$items[] = $this->format_for_log( new RIO_Process_Queue( $row ) );
			}
		}

		return $items;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 *
	 * @param int $object_id
	 */
	public function get_last_optimized_image( $object_id ) {
		global $wpdb;

		$items    = [];
		$db_table = RIO_Process_Queue::table_name();
		$sql      = $wpdb->prepare( "SELECT *	FROM {$db_table}					
					WHERE object_id = '%d' AND item_type = 'attachment' AND result_status IN (%s, %s)
					ORDER BY id DESC
					LIMIT 1;", (int) $object_id, RIO_Process_Queue::STATUS_SUCCESS, RIO_Process_Queue::STATUS_ERROR );

		$model = $wpdb->get_row( $sql, ARRAY_A );

		if ( ! empty( $model ) ) {
			$items[] = $this->format_for_log( new RIO_Process_Queue( $model ) );
		}

		return $items;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.9
	 *
	 * @param RIO_Process_Queue $queue_model
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function format_for_log( $queue_model ) {
		if ( ! $queue_model instanceof RIO_Process_Queue ) {
			throw new Exception( 'Variable $queue_model must be an instance of RIO_Process_Queue!' );
		}

		/**
		 * @var RIO_Attachment_Extra_Data $extra_data
		 */
		$extra_data = $queue_model->get_extra_data();

		$default_formated_data = [
			'id'               => $queue_model->get_id(),
			'url'              => admin_url( sprintf( "post.php?post=%d&action=edit", $queue_model->get_object_id() ) ),
			'thumbnail_url'    => null,
			'file_name'        => null,
			'original_size'    => 0,
			'optimized_size'   => 0,
			'type'             => 'success',
			'webp_size'        => null,
			'original_saving'  => 0,
			'thumbnails_count' => 0,
			'total_saving'     => 0,
		];

		$upload_dir = wp_upload_dir();

		$attachment_meta = wp_get_attachment_metadata( $queue_model->get_object_id() );

		if ( ! empty( $attachment_meta ) ) {
			$image_url     = trailingslashit( $upload_dir['baseurl'] ) . $attachment_meta['file'];
			$thumbnail_url = $image_url;

			if ( isset( $attachment_meta['sizes']['thumbnail'] ) ) {
				$image_basename = wp_basename( $image_url );
				$thumbnail_url  = str_replace( $image_basename, $attachment_meta['sizes']['thumbnail']['file'], $image_url );
			}

			$formated_data = wp_parse_args( [
				'thumbnail_url'  => $thumbnail_url,
				'file_name'      => wp_basename( $attachment_meta['file'] ),
				'original_size'  => size_format( $queue_model->get_original_size(), 2 ),
				'optimized_size' => size_format( $queue_model->get_final_size(), 2 )
			], $default_formated_data );

			$main_file = trailingslashit( $upload_dir['basedir'] ) . $attachment_meta['file'];

			# An extra data may be empty after a failed migration or an unknown error.
			if ( ! empty( $extra_data ) ) {
				$original_main_size = $extra_data->get_original_main_size();

				if ( $original_main_size && file_exists( $main_file ) ) {
					$original_saving                  = ( $original_main_size - filesize( $main_file ) ) * 100 / $original_main_size;
					$formated_data['original_saving'] = round( $original_saving ) . '%';
				}

				$webp_size = $extra_data->get_webp_main_size();

				if ( $webp_size ) {
					$formated_data['webp_size'] = size_format( $webp_size, 2 );
				}

				$formated_data['thumbnails_count'] = $extra_data->get_thumbnails_count();
			}

			if ( $queue_model->get_original_size() ) {
				$total_saving                  = ( $queue_model->get_original_size() - $queue_model->get_final_size() ) * 100 / $queue_model->get_original_size();
				$formated_data['total_saving'] = round( $total_saving, 2 ) . '%';
			}
		} else {
			$attachment = get_post( $queue_model->get_object_id() );

			if ( ! empty( $attachment ) ) {
				$formated_data = [
					'thumbnail_url' => $attachment->guid,
					'file_name'     => wp_basename( $attachment->guid )
				];
			}

			$formated_data = wp_parse_args( $formated_data, $default_formated_data );
		}

		# We collect information about errors
		if ( $queue_model->get_result_status() == RIO_Process_Queue::STATUS_ERROR ) {
			$error_message = null;

			if ( ! empty( $extra_data ) ) {
				$error_message = $extra_data->get_error_msg();
			}

			$formated_data['type']      = 'error';
			$formated_data['error_msg'] = ! empty( $error_message ) ? $error_message : __( 'Unknown error', 'robin-image-optimizer' );

			return $formated_data;
		}

		return $formated_data;
	}

	/**
	 * Возвращает общий процент оптимизированных изображений
	 *
	 * @return int общий процент оптимизации
	 */
	public function getOptimizedPercent() {
		if ( isset( $this->statistic['optimized_percent'] ) ) {
			return $this->statistic['optimized_percent'];
		}

		return 0;
	}

	/**
	 * Пересчёт размера файла в байтах на человекопонятный вид
	 *
	 * Пример: вводим 67894 байт, получаем 67.8 KB
	 * Пример: вводим 6789477 байт, получаем 6.7 MB
	 *
	 * @param int $size   размер файла в байтах
	 *
	 * @return string
	 */
	public function convertToReadableSize( $size ) {
		return wrio_convert_bytes( $size );
	}
}
