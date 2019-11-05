<?php

/**
 * Class RIO_Base_Extra_Data is a base DTO model for `extra_data` property in RIO_Process_Queue.
 *
 * @see RIO_Process_Queue::$extra_data for further information
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class RIO_Base_Extra_Data extends RIO_Base_Object {

	/**
	 * @var string Instance of current class.
	 */
	protected $class;

	/**
	 * Magic override of to string method to convert
	 * @return bool|false|mixed|string
	 */
	public function __toString() {
		$props = get_object_vars( $this );
		// если свойство не установлено, то не сохраняем его
		foreach ( $props as $prop_name => $prop_value ) {
			if ( is_null( $prop_value ) ) {
				unset( $props[ $prop_name ] );
			}
		}
		$props['class'] = get_called_class();

		return wp_json_encode( $props );
	}

	/**
	 * Get class
	 *
	 * @return  string
	 */ 
	public function get_class() {
		return $this->class;
	}

	/**
	 * Set class
	 *
	 * @param  string  $class_name  Имя класса
	 *
	 * @return  void
	 */ 
	public function set_class( $class_name ) {
		$this->class = $class_name;
	}
}