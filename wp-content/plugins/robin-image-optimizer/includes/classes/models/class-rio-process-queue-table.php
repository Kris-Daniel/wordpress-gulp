<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RIO_Queue_Model is a database communication model for "prefix_rio_process_queue" table.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @property int|null                 $id                     Primary key.
 * @property null|string              $server_id              Server id which was used for processing.
 * @property null|int                 $object_id              Object id. Usually, this would be foreign key to another table.
 * @property null|string              $object_name            Object name. Usually, this can be table name where to take object id.
 * @property null|string              $item_type              Type of item. E.g. attachment, webp, etc.
 * @property null|string              $item_hash              Unique item hash. Serves purpose of search.
 * @property null|string              $item_hash_alternative  Unique alternative hash. Serves purpose of search.
 * @property null|string              $result_status          Status result.
 * @property null|string              $processing_level
 * @property null|bool                $is_backed_up           Whether item is backed-up or not. = false;
 * @property null|int                 $original_size          Original file size in bytes.
 * @property null|int                 $final_size             Final file size in bytes after it was optimized or converted.
 * @property null|string              $original_mime_type     Original MIME TYPE. e.g. image/jpeg.
 * @property null|string              $final_mime_type        Final mime type. e.g. image/webp.
 * @property null|RIO_Base_Extra_Data $extra_data             Extra data to be saved. e.g. JSON response from API.
 * @property null|int                 $created_at             UNIX timestamp when item was saved.
 *
 */
class RIO_Process_Queue extends RIO_Base_Active_Record {

	/**
	 * When new status is added, it should be added accordingly to get_statuses() method.
	 *
	 * @see get_statuses() for further information.
	 */
	const STATUS_SUCCESS = 'success'; // On success to convert or optimize.
	const STATUS_ERROR = 'error'; // On failure to convert or optimize.
	const STATUS_SKIP = 'skip'; // Skip.
	const STATUS_PROCESSING = 'processing'; // When conversion or optimization is in progress.
	const STATUS_UNOPTIMIZED = 'unoptimized'; // Когда картинка не оптимизирована

	/**
	 * When new level is added, it should be added accordingly to get_levels() method.
	 *
	 * @see get_levels() for further information.
	 */
	const LEVEL_NORMAL = 'normal';
	const LEVEL_AGGRESIVE = 'aggresive'; // todo: need to fix typo in word aggressive
	const LEVEL_ULTRA = 'ultra';
	const LEVEL_CUSTOM = 'custom';

	/**
	 * @var null|int Primary key.
	 */
	protected $id = null;

	/**
	 * @var null|string Server id which was used for processing.
	 */
	protected $server_id = null;

	/**
	 * @var null|int Object id. Usually, this would be foreign key to another table.
	 */
	protected $object_id = null;

	/**
	 * @var null|string Object name. Usually, this can be table name where to take object id.
	 */
	protected $object_name = null;

	/**
	 * @var null|string Type of item. E.g. attachment, webp, etc.
	 */
	protected $item_type = null;

	/**
	 * @var null|string Unique item hash. Serves purpose of search.
	 */
	protected $item_hash = null;

	/**
	 * @var null|string Unique alternative hash. Serves purpose of search.
	 */
	protected $item_hash_alternative = null;

	/**
	 * @var null|string Status result.
	 */
	protected $result_status = null;

	/**
	 * @var null|string
	 */
	protected $processing_level = null;

	/**
	 * @var null|bool Whether item is backed-up or not.
	 */
	protected $is_backed_up = false;

	/**
	 * @var null|int Original file size in bytes.
	 */
	protected $original_size = null;

	/**
	 * @var null|int Final file size in bytes after it was optimized or converted.
	 */
	protected $final_size = null;

	/**
	 * @var null|string Original MIME TYPE. e.g. image/jpeg.
	 */
	protected $original_mime_type = null;

	/**
	 * @var null|string Final mime type. e.g. image/webp.
	 */
	protected $final_mime_type = null;

	/**
	 * @var null|RIO_Base_Extra_Data Extra data to be saved. e.g. JSON response from API.
	 */
	protected $extra_data = null;

	/**
	 * @var null|int UNIX timestamp when item was saved.
	 */
	protected $created_at = null;

	/**
	 * @var RIO_Base_Extra_Data Keeps instance of extra data to leave state of $extra_data untouched.
	 */
	private static $_extra_data;

	/**
	 * {@inheritdoc}
	 */
	public function init() {
		parent::init();

		// Default model initiation
		static::$_extra_data = new RIO_Base_Extra_Data();
	}

	/**
	 * {@inheritdoc}
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rio_process_queue';
	}

	/**
	 * It is used to check the migration and create a schema in the database.
	 * Gets the level of activity performed in the database.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.8
	 * @return int
	 */
	public static function get_db_version() {
		if ( WRIO_Plugin::app()->isNetworkActive() ) {
			return (int) get_site_option( WRIO_Plugin::app()->getOptionName( 'db_version' ), 0 );
		}

		return (int) get_option( WRIO_Plugin::app()->getOptionName( 'db_version' ), 0 );
	}

	/**
	 * It is used to check the migration and create a schema in the database.
	 * Updates the level of activity performed in the database.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.8
	 * @return void
	 */
	public static function update_db_version( $version ) {
		if ( WRIO_Plugin::app()->isNetworkActive() ) {
			update_site_option( WRIO_Plugin::app()->getOptionName( 'db_version' ), (int) $version );

			return;
		}

		update_option( WRIO_Plugin::app()->getOptionName( 'db_version' ), (int) $version );
	}

	/**
	 * Find db item by hash.
	 *
	 * @param string $hash   Hash to search for.
	 *
	 * @return null|$this
	 */
	public static function find_by_hash( $hash ) {
		global $wpdb;

		$table_name = static::table_name();
		$sql        = "SELECT * FROM {$table_name} WHERE `item_hash` = %s";

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $hash ), ARRAY_A );

		if ( empty( $row ) ) {
			return null;
		}

		return new RIO_Process_Queue( $row );
	}

	/**
	 * Find all by specified condition.
	 *
	 * @param array  $condition   Key => value condition to be used to search.
	 * @param string $order
	 * @param string $limit
	 *
	 * @return null|self[]
	 */
	public static function find_all( array $condition ) {

		global $wpdb;

		$table = static::table_name();

		$sql = "SELECT * FROM $table WHERE ";

		foreach ( $condition as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$sql .= " `" . esc_sql( $key ) . "` = $value AND";
			} else if ( is_string( $value ) ) {
				$sql .= " `" . esc_sql( $key ) . "` = '" . esc_sql( $value ) . "' AND";
			}
		}

		$sql = rtrim( $sql, 'AND' );

		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $rows ) ) {
			return null;
		}

		foreach ( $rows as $key => $row ) {
			$rows[ $key ] = new RIO_Process_Queue( $row );
		}

		return $rows;
	}

	/**
	 * Find items by list of hashes.
	 *
	 * @param string|array $hashes   List of hashes in form of array or CSV.
	 * @param string|null  $status
	 *
	 * @return null|RIO_Process_Queue[]
	 */
	public static function find_by_hashes( $hashes, $status = null ) {
		if ( ! is_array( $hashes ) && ! is_string( $hashes ) ) {
			return null;
		}

		if ( ! is_array( $hashes ) && false !== strpos( $hashes, ',' ) ) {
			$hashes = explode( ',', $hashes );
		}

		$hashes = array_map( 'trim', $hashes );

		$table_name = static::table_name();
		$sql        = "SELECT * FROM {$table_name} WHERE `item_hash`";

		$hashes = array_map( function ( $hash ) {
			return "'$hash'";
		}, $hashes );

		$sql .= ' IN (' . implode( ', ', $hashes ) . ')';

		global $wpdb;

		if ( $status !== null ) {
			$sql .= $wpdb->prepare( ' AND `result_status` = %s', $status );
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $rows ) ) {
			return null;
		}

		foreach ( $rows as $key => $row ) {
			$rows[ $key ] = new RIO_Process_Queue( $row );
		}

		return $rows;
	}

	/**
	 * Find db item by alternative hash.
	 *
	 * @param string $hash   Hash to search for.
	 *
	 * @return null|$this
	 */
	public static function find_by_alternative_hash( $hash ) {
		global $wpdb;

		$table_name = static::table_name();
		$sql        = "SELECT * FROM {$table_name} WHERE `item_alternative_hash` = %s";

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $hash ), ARRAY_A );

		if ( empty( $row ) ) {
			return null;
		}

		return new RIO_Process_Queue( $row );
	}

	/**
	 * Get next item to process sorted by ASC (first inserted).
	 *
	 * @param string   $type        Type to search for.
	 * @param int|null $object_id   Object id added to the query.
	 * @param int|null $limit       Limit added to the query.
	 *
	 * @return null|RIO_Process_Queue[]
	 */
	public static function find_next_to_process( $type, $object_id = null, $limit = null ) {
		global $wpdb;

		$table_name = static::table_name();

		$where = $wpdb->prepare( "WHERE `item_type`= %s AND `result_status` = 'processing'", $type );

		if ( ! empty( $object_id ) ) {
			$where .= sprintf( ' AND `object_id` = %d', $object_id );
		}

		$sql = "SELECT * FROM {$table_name} {$where} ORDER BY `id` ASC";

		if ( is_int( $limit ) ) {
			$sql .= ' LIMIT ' . $limit;
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $rows ) ) {
			return null;
		}

		foreach ( $rows as $key => $row ) {
			$rows[ $key ] = new RIO_Process_Queue( $row );
		}

		return $rows;
	}

	/**
	 * Get count var by specified status.
	 *
	 * Notice: when type is undefined, it would return false.
	 *
	 * @param string $status   Status conversion or optimization.
	 *
	 * @return bool
	 */
	public static function count_by_status( $status ) {
		if ( empty( $status ) ) {
			return false;
		}

		if ( ! in_array( $status, static::get_statuses() ) ) {
			return false;
		}

		global $wpdb;

		$table_name   = static::table_name();
		$sql          = "SELECT COUNT(*) FROM {$table_name} WHERE `result_status` = %s";
		$prepared_sql = $wpdb->prepare( $sql, [ $status ] );

		return $wpdb->get_var( $prepared_sql );
	}

	/**
	 * Get count var by specified type and status.
	 *
	 * Notice: when type is undefined, it would return false.
	 *
	 * @param string $type     Type of file. Normally defined by TYPE_* constant.
	 * @param string $status   Status conversion or optimization.
	 *
	 * @return bool
	 */
	public static function count_by_type_status( $type, $status ) {

		if ( empty( $type ) || empty( $status ) ) {
			return false;
		}

		if ( ! in_array( $status, static::get_statuses() ) ) {
			return false;
		}

		global $wpdb;

		$table_name   = static::table_name();
		$sql          = "SELECT COUNT(*) FROM {$table_name} WHERE `item_type` = %s AND `result_status` = %s";
		$prepared_sql = $wpdb->prepare( $sql, [ $type, $status ] );

		return $wpdb->get_var( $prepared_sql );
	}

	/**
	 * Get count value for specified type, status and level.
	 *
	 * @param string $type     Type of file. Normally defined by TYPE_* constant.
	 * @param string $status   Status conversion or optimization.
	 * @param string $level    Level of optimization or conversion.
	 *
	 * @return bool|null|string
	 */
	public static function count_by_type_status_level( $type, $status, $level ) {
		if ( empty( $type ) || empty( $status ) || empty( $level ) ) {
			return false;
		}

		if ( ! in_array( $status, static::get_statuses() ) ) {
			return false;
		}

		if ( ! in_array( $level, static::get_levels() ) ) {
			return false;
		}

		global $wpdb;

		$table_name   = static::table_name();
		$sql          = "SELECT COUNT(*) FROM {$table_name} WHERE `item_type` = %s AND `result_status` = %s AND `processing_level` = %s";
		$prepared_sql = $wpdb->prepare( $sql, [ $type, $status, $level ] );

		return $wpdb->get_var( $prepared_sql );
	}

	/**
	 * Get available types.
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return [
			self::STATUS_SUCCESS,
			self::STATUS_ERROR,
			self::STATUS_PROCESSING,
			self::STATUS_UNOPTIMIZED,
			self::STATUS_SKIP,
		];
	}

	/**
	 * Get available levels.
	 *
	 * @return array
	 */
	public static function get_levels() {
		return [ self::LEVEL_NORMAL, self::LEVEL_AGGRESIVE, self::LEVEL_CUSTOM, self::LEVEL_ULTRA ];
	}

	/**
	 * Обновление свойств объекта из базы данных
	 *
	 * @return void
	 */
	public function load() {
		global $wpdb;
		$table_name = static::table_name();
		$sql        = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE object_id = %d AND item_type = %s LIMIT 1;", [
			$this->object_id,
			$this->item_type,
		] );

		$row = $wpdb->get_row( $sql );

		if ( ! empty( $row ) ) {
			$this->configure( $row );
		}
	}

	/**
	 * Проверяет, оптимизированно ли изображение
	 *
	 * @return bool
	 */
	public function is_optimized() {
		if ( $this->result_status == self::STATUS_SUCCESS ) {
			return true;
		}

		return false;
	}

	/**
	 * Проверяет, пропущено ли изображение
	 * Если изображение пропущено, то оно больше не участвует в оптимизации
	 *
	 * @return bool
	 */
	public function is_skipped() {
		if ( $this->result_status == self::STATUS_SKIP ) {
			return true;
		}

		return false;
	}

	/**
	 * Подготовка данных для сохранения в базе
	 *
	 * @return array свойства модели в виде массива. Подготовлены для сохранения в базе данных
	 */
	public function prepare_data_to_save() {
		if ( ! $this->created_at ) {
			$this->created_at = time();
		}

		if ( $this->extra_data instanceof RIO_Base_Extra_Data ) {
			$this->extra_data = (string) $this->extra_data;
		}

		$data = (array) $this;

		// @todo: remove later to usage of private attributes inside model
		foreach ( $data as $key => $value ) {
			$clean_key          = trim( $key, " \t\n\r\0\x0B*" );
			$data[ $clean_key ] = $value;
			unset( $data[ $key ] );
		}

		unset( $data['id'] );

		return $data;
	}

	/**
	 * Save item.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;
		$table_name = static::table_name();
		$data       = $this->prepare_data_to_save();

		$is_success = false;

		// если установлен id - значит данные уже есть в базе
		if ( $this->id ) {

			// если данные есть в базе, то обновляем их
			$result = $wpdb->update( $table_name, $data, [ 'id' => $this->id ] );

			WRIO_Logger::debug( sprintf( 'Updated queue item #%s, attributes values: %s', $this->id, wp_json_encode( $data ) ) );

			$is_success = true;
		} else {

			// если данных нет в базе, то вставляем новую запись в таблицу
			$count = $wpdb->insert( $table_name, $data );

			if ( $count !== false && $count > 0 ) {
				$lastId = $wpdb->insert_id;

				if ( ! empty( $lastId ) ) {
					$this->id = $lastId;

					$is_success = true;

					WRIO_Logger::debug( sprintf( 'New queue created #%s, attributes values: %s', $this->id, wp_json_encode( $data ) ) );
				}
			}
		}

		/**
		 * Filter whether to execute hook or not.
		 *
		 * @param $execute_hook bool
		 */
		$execute_hook = apply_filters( 'wbcr/riop/queue_item_save_execute_hook', true );

		if ( $is_success && $execute_hook ) {
			/**
			 * Fires after queue item was saved or updated successfully.
			 *
			 * @param RIO_Process_Queue $this
			 */
			do_action( 'wbcr/riop/queue_item_saved', $this );
		}

		return $is_success;
	}

	/**
	 * Удаление информации об оптимизации из таблицы в базе данных
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;
		$db_table = static::table_name();

		$rows_affected = $wpdb->delete( $db_table, [ 'id' => $this->id ] );

		WRIO_Logger::debug( sprintf( 'Deleted queue item #%s', $this->id ) );

		return $rows_affected !== false;
	}

	/**
	 * Возвращает extra_data
	 *
	 * @return RIO_Base_Extra_Data|null
	 */
	public function get_extra_data() {

		if ( empty( $this->extra_data ) ) {
			return null;
		}

		if ( is_string( $this->extra_data ) ) {
			$extra_data = (array) json_decode( $this->extra_data );

			if ( ! empty( $extra_data ) ) {
				$class = isset( $extra_data['class'] ) ? $extra_data['class'] : null;

				if ( ! empty( $class ) && class_exists( $class ) ) {
					$this->extra_data = new $class( $extra_data );

					return $this->extra_data;
				}
			}
		}

		if ( is_object( $this->extra_data ) ) {
			return $this->extra_data;
		}

		return null;
	}

	/**
	 * Устанавливает значение для массива метаданных
	 *
	 * @param object|string $extra_data
	 *
	 * @return void
	 */
	public function set_extra_data( $extra_data ) {
		$this->extra_data = $extra_data;
	}

	/**
	 * Set item hash.
	 *
	 * @param string $text   String to be hashed.
	 */
	public function set_item_hash( $text ) {
		if ( ! empty( $text ) ) {
			$this->item_hash = static::generate_item_hash( $text );
		}
	}

	/**
	 * @param int|null $id
	 */
	public function set_id( $id ) {
		if ( is_numeric( $id ) ) {
			$this->id = (int) $id;
		}
	}

	/**
	 * @return int|null
	 */
	public function get_id() {

		if ( is_numeric( $this->id ) ) {
			return (int) $this->id;
		}

		return $this->id;
	}

	/**
	 * @param null|string $server_id
	 */
	public function set_server_id( $server_id ) {
		$this->server_id = $server_id;
	}

	public function get_server_id() {
		return $this->server_id;
	}

	/**
	 * @param int|null $object_id
	 */
	public function set_object_id( $object_id ) {
		$this->object_id = $object_id;
	}

	public function get_object_id() {
		return $this->object_id;
	}

	/**
	 * @param null|string $object_name
	 */
	public function set_object_name( $object_name ) {
		$this->object_name = $object_name;
	}

	public function get_object_name() {
		return $this->object_name;
	}

	/**
	 * @param null|string $item_type
	 */
	public function set_item_type( $item_type ) {
		$this->item_type = $item_type;
	}

	public function get_item_type() {
		return $this->item_type;
	}

	/**
	 * @param null|string $text   Text to be hashed.
	 */
	public function set_item_hash_alternative( $text ) {
		if ( ! empty( $text ) ) {
			$this->item_hash_alternative = static::generate_item_alternative_hash( $text );
		}
	}

	public function get_item_hash_alternative() {
		return $this->item_hash_alternative;
	}

	/**
	 * @return null|string
	 */
	public function get_item_hash() {
		return $this->item_hash;
	}

	/**
	 * @param null|string $result_status
	 */
	public function set_result_status( $result_status ) {
		$this->result_status = $result_status;
	}

	public function get_result_status() {
		return $this->result_status;
	}

	/**
	 * @param null|string $processing_level
	 */
	public function set_processing_level( $processing_level ) {
		$this->processing_level = $processing_level;
	}

	public function get_processing_level() {
		return $this->processing_level;
	}

	/**
	 * @param bool|null $is_backed_up
	 */
	public function set_is_backed_up( $is_backed_up ) {
		$this->is_backed_up = $is_backed_up;
	}

	public function get_is_backed_up() {
		return $this->is_backed_up;
	}

	/**
	 * @param int|null $original_size
	 */
	public function set_original_size( $original_size ) {
		$this->original_size = $original_size;
	}

	public function get_original_size() {
		return $this->original_size;
	}

	/**
	 * @param int|null $final_size
	 */
	public function set_final_size( $final_size ) {
		$this->final_size = $final_size;
	}

	public function get_final_size() {
		return $this->final_size;
	}

	/**
	 * @param null|string $original_mime_type
	 */
	public function set_original_mime_type( $original_mime_type ) {
		$this->original_mime_type = $original_mime_type;
	}

	public function get_original_mime_type() {
		return $this->original_mime_type;
	}

	/**
	 * @param null|string $final_mime_type
	 */
	public function set_final_mime_type( $final_mime_type ) {
		$this->final_mime_type = $final_mime_type;
	}

	public function get_final_mime_type() {
		return $this->final_mime_type;
	}

	/**
	 * @param int|null $created_at
	 */
	public function set_created_at( $created_at ) {
		$this->created_at = $created_at;
	}

	/**
	 * get_created_at
	 *
	 * @return int
	 */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
	 * Generate item hash.
	 *
	 * @param string $text   Text to be hashed.
	 *
	 * @return string
	 */
	public static function generate_item_hash( $text ) {
		return hash( 'sha256', $text );
	}

	/**
	 * Generate item alternative hash.
	 *
	 * @param string $text   Text to be hashed.
	 *
	 * @return string
	 */
	public static function generate_item_alternative_hash( $text ) {
		return hash( 'sha256', $text );
	}

	/**
	 * Get table schema.
	 *
	 * @return string
	 */
	public static function get_table_schema() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = RIO_Process_Queue::table_name();
		$sql             = "CREATE TABLE IF NOT EXISTS {$table_name} (
			  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `server_id` varchar(60) DEFAULT NULL,
			  `object_id` bigint(20) UNSIGNED NULL,
			  `object_name` varchar(255) NULL,
			  `item_type` varchar(60) NOT NULL,
			  `item_hash` CHAR(64) NULL COMMENT 'sha256 size',
			  `item_hash_alternative` CHAR(64) NULL COMMENT 'sha256 size',
			  `result_status` varchar(60) NOT NULL,
			  `processing_level` varchar(60) NOT NULL,
			  `is_backed_up` tinyint(1) NOT NULL DEFAULT '0',
			  `original_size` int(11) UNSIGNED NOT NULL,
			  `final_size` int(11) UNSIGNED NOT NULL,
			  `original_mime_type` varchar(60) NOT NULL,
			  `final_mime_type` varchar(60) NOT NULL,
			  `extra_data` TEXT NULL DEFAULT NULL,
			  `created_at` bigint(20) NOT NULL,
			  PRIMARY KEY (`id`)
			) $charset_collate;";

		return $sql;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_table_indexes() {
		$table_name                  = static::table_name();
		$sql_index_type_status       = "ALTER TABLE {$table_name} ADD INDEX `index-type-status` (`item_type`, `result_status`);";
		$sql_index_type_status_level = "ALTER TABLE {$table_name} ADD INDEX `index-type-status-level` (`item_type`, `result_status`, `processing_level`);";
		$sql_index_hash              = "ALTER TABLE {$table_name} ADD UNIQUE `index-hash` (`item_hash`);";
		$sql_index_hash_alternative  = "ALTER TABLE {$table_name} ADD INDEX `index-hash-alternative` (`item_hash_alternative`);";
		$sql_index_type_attachments  = "ALTER TABLE {$table_name} ADD INDEX `index-type-attachments` (`object_id`, `item_type`);";

		return [
			$sql_index_type_status,
			$sql_index_type_status_level,
			$sql_index_hash,
			$sql_index_hash_alternative,
			$sql_index_type_attachments
		];
	}

	/**
	 * Try to create a plugin table in database
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 * @throws \Exception
	 */
	public static function try_create_plugin_tables() {
		global $wpdb;

		try {
			if ( ! RIO_Process_Queue::has_table_schema() ) {
				return;
			}

			if ( ! static::get_db_version() ) {
				$sql = static::get_table_schema();
				$wpdb->query( $sql );

				if ( static::has_table_indexes() ) {
					$indexes = static::get_table_indexes();

					foreach ( $indexes as $index ) {
						$wpdb->query( $index );
					}
				}

				static::update_db_version( 1 );
				static::fix_table_collation();
			}
		} catch( \Exception $e ) {
			WRIO_Logger::error( sprintf( "Failed create %s table in database.\r\nSQL: %s", static::table_name(), static::get_table_schema() ) );
		}
	}

	/**
	 * RIO-126: Fix collation for plugin table, if Wordpress tables are created in a different encoding.
	 * In some cases, plugin table can be utf8mb4_unicode_520_ci, and Wodpress table can be
	 * utf8mb4_unicode_ci. This leads to problems when performing comparisons between two tables.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 */
	public static function fix_table_collation() {
		global $wpdb;

		$wp_post_meta_collation  = null;
		$process_queue_collation = null;
		$wrio_table              = static::table_name();

		$result = $wpdb->get_results( "SHOW TABLE STATUS" );

		if ( ! empty( $result ) ) {
			foreach ( (array) $result as $table ) {
				if ( $wpdb->postmeta === $table->Name ) {
					$wp_post_meta_collation = $table->Collation;
				} else if ( $wpdb->prefix . 'rio_process_queue' === $table->Name ) {
					$process_queue_collation = $table->Collation;
				}
			}

			if ( ! empty( $wp_post_meta_collation ) && ! empty( $process_queue_collation ) ) {

				list( $wp_post_meta_charset ) = explode( '_', $wp_post_meta_collation );
				list( $process_queue_charset ) = explode( '_', $process_queue_collation );

				if ( ( $wp_post_meta_collation !== $process_queue_collation ) && $wp_post_meta_charset === $process_queue_charset ) {
					$wpdb->query( "ALTER TABLE {$wrio_table} CONVERT TO CHARACTER SET {$wp_post_meta_charset} COLLATE {$wp_post_meta_collation}" );

					WRIO_Logger::info( sprintf( "Successfully fix collation for plugin table.\r\nWP COLLATION: %s | PLUGIN COLLATION: %s", $wp_post_meta_collation, $process_queue_collation ) );
				}
			}
		}
	}
}