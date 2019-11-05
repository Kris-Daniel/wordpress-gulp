<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RIO_Base_Helper {
	/**
	 * Configure passed object.
	 *
	 * @param object $object Object class to configure.
	 * @param array $config Key => value list of props to be set on object.
	 *
	 * @return mixed
	 */
	public static function configure ( $object, $config ) {
		foreach ( $config as $name => $value ) {
			$object->$name = $value;
		}

		return $object;
	}
}