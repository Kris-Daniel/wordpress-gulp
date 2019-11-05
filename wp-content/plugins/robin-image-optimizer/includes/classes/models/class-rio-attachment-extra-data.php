<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RIO_Attachment_Extra_Data is a  DTO model for `attachment` post type used for `extra_data`
 * property in RIO_Process_Queue.
 *
 * @see RIO_Process_Queue::$extra_data for further information
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class RIO_Attachment_Extra_Data extends RIO_Base_Extra_Data {

	protected $error = null;
	protected $error_msg = null;
	protected $thumbnails_count = null;
	protected $original_main_size = null;
	protected $main_optimized_data = null;
	protected $thumbnails_optimized_data = null;
	protected $webp_main_size = null;

	public function get_error() {
		return $this->error;
	}

	public function set_error( $error ) {
		$this->error = $error;
	}

	public function get_error_msg() {
		return $this->error_msg;
	}

	public function set_error_msg( $error_msg ) {
		$this->error_msg = $error_msg;
	}

	public function get_thumbnails_count() {
		return $this->thumbnails_count;
	}

	public function set_thumbnails_count( $thumbnails_count ) {
		$this->thumbnails_count = $thumbnails_count;
	}

	public function get_original_main_size() {
		return $this->original_main_size;
	}

	public function set_original_main_size( $original_main_size ) {
		$this->original_main_size = $original_main_size;
	}

	public function get_main_optimized_data() {
		return (array)$this->main_optimized_data;
	}

	public function set_main_optimized_data( $main_optimized_data ) {
		$this->main_optimized_data = $main_optimized_data;
	}

	public function get_thumbnails_optimized_data() {
		return (array)$this->thumbnails_optimized_data;
	}

	public function set_thumbnails_optimized_data( $thumbnails_optimized_data ) {
		$this->thumbnails_optimized_data = $thumbnails_optimized_data;
	}

	public function get_webp_main_size() {
		return $this->webp_main_size;
	}

	public function set_webp_main_size( $webp_main_size ) {
		$this->webp_main_size = $webp_main_size;
	}
}
