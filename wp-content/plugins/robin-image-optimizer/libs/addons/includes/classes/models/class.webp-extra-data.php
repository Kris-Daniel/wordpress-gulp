<?php

/**
 * Class RIOP_WebP_Extra_Data.
 *
 * @property string $source_src
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class RIOP_WebP_Extra_Data extends RIO_Base_Extra_Data {
	/**
	 * @var null|string E.g. attachment, nextgen, etc
	 */
	protected $convert_from = null;

	/**
	 * @var null|string|int
	 */
	protected $converted_from_size = null;

	/**
	 * @var string|null Image source src.
	 */
	protected $source_src = null;

	/**
	 * @var string|null Image absolute path.
	 */
	protected $source_path = null;

	/**
	 * @var string|null Converted WebP image src.
	 */
	protected $converted_src = null;

	/**
	 * @var string|null Converted WebP absolute path.
	 */
	protected $converted_path = null;

	/**
	 * @var int|null Post ID.
	 */
	protected $post_id = null;

	/**
	 * @param string $source_src
	 */
	public function set_source_src ( $source_src ) {
		$this->source_src = trim( $source_src );
	}

	/**
	 * Get source property.
	 *
	 * @param bool $decoded Whether to decode src or not.
	 *
	 * @return string
	 */
	public function get_source_src ( $decoded = true ) {
		$src = $this->source_src;

		if ( $decoded ) {
			return urldecode( $src );
		}

		return $src;
	}

	/**
	 * @return null|string
	 */
	public function get_source_path () {
		return $this->source_path;
	}

	/**
	 * @param null|string $source_path
	 */
	public function set_source_path ( $source_path ) {
		$this->source_path = $source_path;
	}

	/**
	 * @return null|string
	 */
	public function get_convert_from () {
		return $this->convert_from;
	}

	/**
	 * @param null|string $convert_from
	 */
	public function set_convert_from ( $convert_from ) {
		$this->convert_from = $convert_from;
	}

	/**
	 * @return int|null|string
	 */
	public function get_converted_from_size () {
		return $this->converted_from_size;
	}

	/**
	 * @param int|null|string $converted_from_size
	 */
	public function set_converted_from_size ( $converted_from_size ) {
		$this->converted_from_size = $converted_from_size;
	}

	/**
	 * @return string
	 */
	public function get_converted_path () {
		return $this->converted_path;
	}

	/**
	 * @param string $converted_path
	 */
	public function set_converted_path ( $converted_path ) {
		$this->converted_path = $converted_path;
	}

	/**
	 * @return string
	 */
	public function get_converted_src () {
		return $this->converted_src;
	}

	/**
	 * @param string $converted_src
	 */
	public function set_converted_src ( $converted_src ) {
		$this->converted_src = $converted_src;
	}

	/**
	 * @return int
	 */
	public function get_post_id () {
		return $this->post_id;
	}

	/**
	 * @param int $post_id
	 *
	 * @return RIOP_WebP_Extra_Data
	 */
	public function set_post_id ( $post_id ) {
		$this->post_id = $post_id;

		return $this;
	}
}
