<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WRIO_Base_Model used as a base class for any database related model.
 *
 * Usage example:
 * ```php
 * Custom extends RIO_Base_Model {
 *    public $prop;
 * }
 *
 * $model = new Custom(array('prop' => 123)); // or ['prop' => 123]
 * $model->save();
 * ```
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class RIO_Base_Active_Record extends RIO_Base_Object {

	/**
	 * Get table name.
	 *
	 * @return string|null
	 */
	public static function table_name () {
		return null;
	}

	/**
	 * @todo override with activerecord impl
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @throws Exception
	 */
	public function __set ( $name, $value ) {
		if ( property_exists( $this, $name ) ) {
			$this->$name = $value;
		}
	}


	/**
	 * Check whether table has SQL schema or not.
	 *
	 * @return bool
	 */
	public static function has_table_schema () {
		$schema = static::get_table_schema();

		return ! empty( $schema );
	}

	/**
	 * Check whether table has indexes defined.
	 *
	 * Notice: method would check whether model has schema defined first and then indexes.
	 *
	 * @return bool
	 */
	public static function has_table_indexes () {

		if ( ! static::has_table_schema() ) {
			return false;
		}

		$indexes = static::get_table_indexes();

		return ! empty( $indexes );
	}

	/**
	 * Get table SQL schema structure.
	 *
	 * @return string|null String when model has database table, null otherwise.
	 */
	public static function get_table_schema () {
		return null;
	}

	/**
	 * Get list of indexes.
	 *
	 * None associative list of
	 *
	 * @return array Empty array returned in case when no indexes exist on table.
	 */
	public static function get_table_indexes () {
		return array();
	}
}