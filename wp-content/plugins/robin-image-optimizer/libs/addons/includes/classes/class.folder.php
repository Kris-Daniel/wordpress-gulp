<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с кастомными папками.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Folder {

	/**
	 * @var string Folder path.
	 */
	protected $path = '';

	/**
	 * @var string SHA256 folder's path hash.
	 */
	protected $uid = '';

	/**
	 * @var int Number of files in current folder.
	 */
	protected $files_count = 0;

	/**
	 * @var int Number of optimized files in current folder.
	 */
	protected $optimized_count = 0;

	/**
	 * @var int Number of errors in current folder.
	 */
	protected $errors_count = 0;

	/**
	 * WRIO_Folder constructor.
	 *
	 * @param array $params   List of params set on the model.
	 */
	public function __construct( $params = [] ) {
		// нужна проверка пути
		foreach ( $params as $key => $value ) {
			$this->set( $key, $value );
		}
		if ( ! $this->uid ) {
			$this->uid = hash( 'sha256', $this->path );
		}
	}

	/**
	 * Get specified object's property.
	 *
	 * @param string $property_name   Property name.
	 *
	 * @return bool
	 */
	public function get( $property_name ) {
		if ( isset( $this->$property_name ) ) {
			return $this->$property_name;
		}

		return false;
	}

	/**
	 * Set specified object's property.
	 *
	 * @param string $property_name   Property name.
	 * @param mixed  $value           Value to set.
	 */
	public function set( $property_name, $value ) {
		if ( isset( $this->$property_name ) ) {
			$this->$property_name = $value;
		}
	}

	/**
	 * Convert object to associative array form.
	 *
	 * @return array
	 */
	public function toArray() {
		return [
			'path'            => $this->path,
			'files_count'     => $this->files_count,
			'uid'             => $this->uid,
			'optimized_count' => $this->optimized_count,
			'errors_count'    => $this->errors_count,
		];
	}

	/**
	 * Обходит каталог и добавляет в индекс файлы
	 *
	 * @param mixed $offset                 отступ от начала
	 * @param mixed $max_process_elements   кол-во элементов за итерацию
	 *
	 * @return int Кол-во элементов, обработанных при индексировании
	 */
	public function indexing( $offset = 0, $max_process_elements = 100 ) {
		global $wpdb;

		$iterator = $this->getRecursiveIterator();
		$allowed  = $this->getAllowedFilesExt();
		$files    = [];
		$db_table = RIO_Process_Queue::table_name();

		foreach ( $iterator as $file ) {
			$ext = substr( $file, strrpos( strtolower( $file ), '.' ) + 1 ); // получаем расширение файла
			if ( in_array( strtolower( $ext ), $allowed ) ) {
				// сделать путь относительно корня
				$files[] = $this->realPathToRelative( $file->getPathname() );
			}
		}

		$files = array_slice( $files, $offset, $max_process_elements );
		foreach ( $files as $file ) {
			$file = wp_normalize_path( $file );
			//$file_path = str_replace( $this->path, '', $file );
			$file_uid = hash( 'sha256', str_replace( wp_normalize_path( ABSPATH ), '', $file ) );
			$sql      = $wpdb->prepare( "SELECT * FROM {$db_table} WHERE item_hash_alternative = %s AND item_hash = %s;", $this->uid, $file_uid );
			$row      = $wpdb->get_row( $sql );

			if ( empty( $row ) ) {
				// если файла нет в индексе - добавляем
				$extra_data        = new WRIO_CF_Image_Extra_Data( [
					'file_path'            => $file,
					'folder_relative_path' => $this->path,
				] );
				$optimization_data = new RIO_Process_Queue( [
					'item_type'             => 'cf_image',
					'item_hash'             => $file_uid, // хэш пути к файлу
					'item_hash_alternative' => $this->uid, // хэш директории будет сделан сеттером
					'original_size'         => 0,
					'final_size'            => 0,
					'original_mime_type'    => '',
					'final_mime_type'       => '',
					'result_status'         => 'unoptimized',
					'processing_level'      => '',
					'extra_data'            => $extra_data,
				] );
				$optimization_data->save();
			} else {
				// делаем апдейт и выставляем ласт индекс дату. Потом у кого в индексе ласт индекс дата меньше заданной, того уже нет на диске
			}
		}

		return count( $files ); // сколько элементов обработано при индексировании.
	}

	/**
	 * Проверяет индекс на наличие несуществующих файлов.
	 *
	 * Находит файлы, которые пользователь удалил из папки, но в индексе они ещё есть.
	 * Удаляет из из индекса, вычитает из статистики
	 *
	 * @param mixed $offset                 отсутп от начала
	 * @param mixed $max_process_elements   кол-во элементов за итерацию
	 *
	 * @return int $processed Кол-во обработанных записей. Используется для расчёта отступа
	 */
	public function syncIndex( $offset = 0, $max_process_elements = 100 ) {
		global $wpdb;
		$db_table         = RIO_Process_Queue::table_name();
		$sql              = $wpdb->prepare( "SELECT * FROM {$db_table} WHERE item_hash_alternative = %s LIMIT %d OFFSET %d;", $this->uid, $max_process_elements, $offset );
		$rows             = $wpdb->get_results( $sql );
		$image_statistics = WRIO_Image_Statistic_Folders::get_instance();
		$processed        = 0;
		$deleted          = 0;

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$processed ++;
				$cf_image = new WRIO_Folder_Image( $row->id, $row );
				if ( ! $cf_image->isFileExists() ) {
					if ( $cf_image->isOptimized() ) {
						// если файл оптимизирован - вычитаем из статистики
						$optimization_data = $cf_image->getOptimizationData();
						$optimized_size    = $optimization_data->get_final_size();
						$original_size     = $optimization_data->get_original_size();
						$image_statistics->deductFromField( 'optimized_size', $optimized_size );
						$image_statistics->deductFromField( 'original_size', $original_size );
					}
					// если файла нет на диске - удаляем из индекса
					$wpdb->delete( $db_table, [
						'id' => $cf_image->get( 'id' ),
					], [ '%d' ] );
					$deleted ++;
				}
			}
			$image_statistics->save();
			$this->reCountOptimizedFiles();
		}
		$processed = $processed - $deleted;

		return $processed;
	}

	/**
	 * Get allowed list of extensions.
	 *
	 * @return array
	 */
	public function getAllowedFilesExt() {
		$allowed = [ 'jpg', 'jpeg', 'png' ];

		return $allowed;
	}

	/**
	 * Count number of files in a folder.
	 *
	 * @return int
	 */
	public function countFiles() {
		$iterator = $this->getRecursiveIterator();
		$allowed  = $this->getAllowedFilesExt();
		$count    = 0;
		foreach ( $iterator as $file ) {
			$ext = substr( $file, strrpos( strtolower( $file ), '.' ) + 1 ); // получаем расширение файла
			if ( in_array( strtolower( $ext ), $allowed ) ) {
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Count number of indexes files.
	 *
	 * @return null|string
	 */
	public function countIndexedFiles() {
		global $wpdb;
		$db_table    = RIO_Process_Queue::table_name();
		$sql_files   = $wpdb->prepare( "SELECT COUNT(*) FROM {$db_table} WHERE item_type = %s AND item_hash_alternative = %s;", 'cf_image', $this->uid );
		$files_count = $wpdb->get_var( $sql_files );

		return $files_count;
	}

	public function reCountFiles() {
		$this->files_count = $this->countFiles();

		return $this->files_count;
	}

	/**
	 * Recount optimized files.
	 *
	 * @return int|null|string
	 */
	public function reCountOptimizedFiles() {
		global $wpdb;
		$db_table              = RIO_Process_Queue::table_name();
		$sql                   = "SELECT COUNT(*) FROM {$db_table} WHERE item_type = %s AND result_status = %s AND item_hash_alternative = %s;";
		$sql_prepared          = $wpdb->prepare( $sql, 'cf_image', RIO_Process_Queue::STATUS_SUCCESS, $this->uid );
		$optimized_count       = $wpdb->get_var( $sql_prepared );
		$this->optimized_count = $optimized_count;

		return $this->optimized_count;
	}

	/**
	 * Remove folder and deduct its size from optimized and original size.
	 */
	public function remove() {
		// удаляем файлы из индекса и переситываем стату
		global $wpdb;
		$db_table     = RIO_Process_Queue::table_name();
		$sql          = "SELECT SUM(original_size) AS original_size, SUM(final_size) AS optimized_size FROM {$db_table} WHERE item_type = %s AND result_status = %s AND item_hash_alternative = %s";
		$prepared_sql = $wpdb->prepare( $sql, 'cf_image', RIO_Process_Queue::STATUS_SUCCESS, $this->uid );
		$sum          = $wpdb->get_row( $prepared_sql );

		// Deduct from statistics
		$image_statistics = WRIO_Image_Statistic_Folders::get_instance();
		$image_statistics->deductFromField( 'optimized_size', $sum->optimized_size );
		$image_statistics->deductFromField( 'original_size', $sum->original_size );
		$image_statistics->save();

		// Delete from db
		$wpdb->delete( $db_table, [
			'item_hash_alternative' => $this->uid,
			'item_type'             => 'cf_image',
		], [ '%s', '%s' ] );
	}

	/**
	 * Get absolute path from relative.
	 *
	 * @return bool|string
	 */
	public function realPath() {
		return realpath( ABSPATH . $this->path );
	}

	/**
	 * Возвращает путь к директории относительно корня сайта
	 * На входе: /home/user/test/wp.com/wp-content/uploads/custom-folder/
	 * На выходе: /wp-content/uploads/custom-folder/
	 *
	 * @param string $path   Путь к директории. Может быть абсолютным.
	 *
	 * @return string $relative_path относительный путь
	 */
	public function realPathToRelative( $path ) {
		$relative_path = str_replace( untrailingslashit( ABSPATH ), '', $path );

		return $relative_path;
	}

	/**
	 * Get iterator to go scan files in directory.
	 *
	 * @return RecursiveIteratorIterator
	 */
	public function getRecursiveIterator() {
		$iterator = new RecursiveDirectoryIterator( $this->realPath() );

		return new RecursiveIteratorIterator( $iterator );
	}
}
