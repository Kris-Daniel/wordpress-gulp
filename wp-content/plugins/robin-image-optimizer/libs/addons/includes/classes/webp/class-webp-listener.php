<?php

namespace WRIO\WEBP;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Post;
use WRIO\WEBP\HTML\Delivery;

/**
 * Class Listener listens to new events via hooks.
 *
 * For example, once attachment optimized and if WebP option enabled it will kicked and converted.
 *
 * Same applies for custom folder and NextGen plugin.
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @author        Alexander Teshabaev <sasha.tesh@gmail.com>
 * @copyright (c) 22.09.2018, Webcraftic
 * @version       1.0
 */
class Listener {

	/**
	 * Default type.
	 */
	const DEFAULT_TYPE = 'webp';

	/**
	 * @var null|\RIO_Process_Queue[] Saved queue items.
	 */
	private $_saved_models = null;

	/**
	 * WRIO_Webp constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init the object.
	 */
	public function init() {
		if ( Delivery::is_webp_enabled() ) {
			add_action( 'wbcr/riop/queue_item_saved', function ( $model ) {

				/**
				 * @var \RIO_Process_Queue $model
				 */
				if ( $model->get_item_type() !== self::DEFAULT_TYPE ) { // Otherwise it can become recursive

					$this->process_queue_item( $model );

					if ( ! empty( $this->_saved_models ) ) {
						( new \WRIO_WebP_Api( $this->_saved_models ) )->process_image_queue();
					}

					$this->_saved_models = null;
				}
			} );
		}

		add_action( 'wbcr/rio/attachment_restored', [ $this, 'process_attachment_restore' ] );
		add_action( 'wbcr/rio/cf_image_restored', [ $this, 'process_attachment_restore' ] );
		add_action( 'wbcr/rio/nextgen_image_restored', [ $this, 'process_attachment_restore' ] );
	}

	/**
	 * Process attachment restore.
	 *
	 * @param \RIO_Process_Queue $model
	 *
	 * @return bool
	 */
	public function process_attachment_restore( $model ) {
		$item_params = [
			'object_id' => $model->get_object_id(),
			'item_type' => Listener::DEFAULT_TYPE,
		];
		if ( 'cf_image' == $model->get_item_type() ) {
			unset( $item_params['object_id'] ); // для custom folders не нужен номер объекта
			/**
			 * @var $extra_data \WRIO_CF_Image_Extra_Data
			 */
			$extra_data               = $model->get_extra_data();
			$item_params['item_hash'] = hash( 'sha256', $extra_data->get_image_url() );
		}

		$delete_items = \RIO_Process_Queue::find_all( $item_params );

		if ( empty( $delete_items ) ) {
			return false;
		}

		foreach ( $delete_items as $item ) {
			/**
			 * @var $extra \RIOP_WebP_Extra_Data
			 */
			$extra = $item->get_extra_data();

			if ( empty( $extra ) ) {
				\WRIO_Logger::warning( sprintf( 'Failed to clean-up queue item #%s as it is missing extra data', $item->get_id() ) );
				continue;
			}

			$converted_path = $extra->get_converted_path();
			if ( ! empty( $converted_path ) ) {
				if ( @unlink( $converted_path ) ) {
					\WRIO_Logger::info( sprintf( 'Unlinked %s from disk, ready to delete item #%s from DB', $converted_path, $item->get_id() ) );
				} else {
					\WRIO_Logger::error( sprintf( 'Failed to unlink %s from disk', $converted_path ) );
				}
			}

			if ( $item->delete() ) {
				\WRIO_Logger::info( sprintf( 'Deleted #%s as attachment #%s was recovered', $item->get_id(), $item->get_object_id() ) );
			} else {
				\WRIO_Logger::error( sprintf( 'Failed to delete queue item #%s as delete() method failed', $item->get_id() ) );
			}
		}

		return true;
	}

	/**
	 * Process new queue item.
	 *
	 * @param \RIO_Process_Queue $model   Model to process.
	 *
	 * @return bool
	 */
	public function process_queue_item( $model ) {
		if ( ! ( $model instanceof \RIO_Process_Queue ) ) {
			\WRIO_Logger::info( 'Model must be instance of RIO_Process_Queue to be process by %s' . __FUNCTION__ );

			return false;
		}

		if ( ! $model->is_optimized() ) {
			\WRIO_Logger::info( sprintf( 'Skipping to process attachment #%s as it is not optimized', $model->get_id() ) );

			return false;
		}

		switch ( $model->get_item_type() ) {
			case 'attachment':
				$this->process_attachment( $model );
				break;
			case 'cf_image':
				$this->process_custom_folder( $model );
				break;
			case 'nextgen';
				$this->process_nextgen( $model );
				break;
		}

		return true;
	}

	/**
	 * Process attachment.
	 *
	 * Finds attachment by id, gets its src and srcset and saves them on the database.
	 *
	 * After this, images are processed by Cron or manually via admin GUI.
	 *
	 * @param \RIO_Process_Queue $model   Attachment model to process.
	 *
	 * @return bool
	 */
	public function process_attachment( $model ) {

		\WRIO_Logger::info( sprintf( 'Start webp convertation proccess for attachment #%s', $model->get_id() ) );

		$attachment = get_post( $model->get_object_id() );

		if ( empty( $attachment ) ) {
			\WRIO_Logger::warning( sprintf( 'Webp convertation: No attachment found by #%s', $model->get_object_id() ) );

			return false;
		}

		$allowed_mimes = [ 'image/jpeg', 'image/png', 'image/gif' ];

		if ( ! in_array( $attachment->post_mime_type, $allowed_mimes ) ) {
			\WRIO_Logger::warning( sprintf( 'Webp convertation: Attachment #%s with MIME type %s cannot be processed as only these are allowed: %s', $attachment->ID, $attachment->post_mime_type, implode( ', ', $allowed_mimes ) ) );

			return false;
		}

		$attachment_meta = static::get_attachment_data( $attachment );

		if ( empty( $attachment_meta ) ) {
			\WRIO_Logger::warning( sprintf( 'Webp convertation: Unable to get attachment #%s meta such as height, abs. path, URL, etc. Skipping WebP processing...', $attachment->ID ) );

			return false;
		}

		/**
		 * @var $data array
		 */
		foreach ( $attachment_meta as $hash => $data ) {

			\WRIO_Logger::info( sprintf( 'Webp convertation: Ready to save hash "%s" (extra data: %s) as it does not exist yet', $hash, json_encode( $data ) ) );

			$source_path = isset( $data['absolute_path'] ) ? $data['absolute_path'] : null;

			if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
				\WRIO_Logger::error( sprintf( "Webp convertation: Image is not found.\r\nSource path: %s", $source_path ) );
				continue;
			}

			$source_src = $data['url'];

			$webp_exists = \RIO_Process_Queue::find_by_hash( hash( 'sha256', $source_src ) );

			if ( $webp_exists ) {
				\WRIO_Logger::warning( sprintf( "Webp convertation: Skipped because the webp image already exists.\r\nSource scr: %s", $source_src ) );

				return false;
			}

			$extra_data = new \RIOP_WebP_Extra_Data( [
				'convert_from'        => 'attachment',
				'converted_from_size' => $data['size'],
				'source_src'          => $source_src,
				'source_path'         => $source_path,
			] );

			$saved = $this->save( [
				'item_hash'          => $source_src,
				'object_id'          => $attachment->ID,
				'original_mime_type' => $attachment->post_mime_type,
			], $extra_data );

			if ( $saved instanceof \RIO_Process_Queue ) {
				$this->_saved_models[] = $saved;
			}
		}

		\WRIO_Logger::info( sprintf( 'End webp convertation proccess for attachment #%s. Saved models: %d', $model->get_id(), sizeof( $this->_saved_models ) ) );

		return true;
	}

	/**
	 * Process custom folder.
	 *
	 * @param \RIO_Process_Queue $model   Model to process.
	 *
	 * @return bool
	 */
	public function process_custom_folder( $model ) {

		\WRIO_Logger::info( sprintf( 'Start webp convertation proccess for Custom folder item #%s', $model->get_id() ) );

		/**
		 * @var $model_extra_data \WRIO_CF_Image_Extra_Data
		 */
		$model_extra_data = $model->get_extra_data();

		$webp_exists = \RIO_Process_Queue::find_by_hash( hash( 'sha256', $model_extra_data->get_image_url() ) );

		if ( $webp_exists ) {
			\WRIO_Logger::warning( sprintf( "Webp convertation: Skipped because the webp image already exists.\r\nSource scr: %s", $model_extra_data->get_image_url() ) );

			return false;
		}

		$extra_data = new \RIOP_WebP_Extra_Data( [
			'convert_from' => 'cf_image',
			'source_src'   => $model_extra_data->get_image_url(),
			'source_path'  => $model_extra_data->get_image_absolute_path(),
		] );

		$saved = $this->save( [
			'item_hash'             => $model_extra_data->get_image_url(),
			'item_hash_alternative' => $model_extra_data->get_image_relative_path(),
			'original_mime_type'    => $model->get_original_mime_type(),
		], $extra_data );

		if ( $saved instanceof \RIO_Process_Queue ) {
			$this->_saved_models[] = $saved;
		}

		\WRIO_Logger::info( sprintf( 'End webp convertation proccess for Custom folder #%s. Saved models: %d', $model->get_id(), sizeof( $this->_saved_models ) ) );

		return true;
	}

	/**
	 * Process NextGen plugin images.
	 *
	 * @link https://wordpress.org/plugins/nextgen-gallery/ Plugin link.
	 *
	 * @param \RIO_Process_Queue $model   Model to process.
	 *
	 * @return bool
	 */
	public function process_nextgen( $model ) {

		\WRIO_Logger::info( sprintf( 'Start webp convertation proccess for NextGen item #%s', $model->get_id() ) );

		/**
		 * @var $model_extra_data \WRIO_Nextgen_Extra_Data
		 */
		$model_extra_data = $model->get_extra_data();

		if ( ! ( $model_extra_data instanceof \WRIO_Nextgen_Extra_Data ) ) {
			return false;
		}

		$webp_exists = \RIO_Process_Queue::find_by_hash( hash( 'sha256', $model_extra_data->get_image_url() ) );

		if ( $webp_exists ) {
			\WRIO_Logger::warning( sprintf( "Webp convertation: Skipped because the webp image already exists.\r\nSource scr: %s", $model_extra_data->get_image_url() ) );

			return false;
		}

		// Original
		$extra_data = new \RIOP_WebP_Extra_Data( [
			'convert_from'        => 'nextgen',
			'converted_from_size' => null,
			'source_src'          => $model_extra_data->get_image_url(),
			'source_path'         => $model_extra_data->get_image_absolute_path(),
		] );

		$original_saved = $this->save( [
			'item_hash'          => $model_extra_data->get_image_url(),
			'original_mime_type' => $model->get_original_mime_type(),
			'object_id'          => $model->get_object_id(),
		], $extra_data );

		if ( $original_saved instanceof \RIO_Process_Queue ) {
			$this->_saved_models[] = $original_saved;
		}

		// Thumbnail
		$extra_data_thumbnail = new \RIOP_WebP_Extra_Data( [
			'convert_from'        => 'nextgen',
			'converted_from_size' => null,
			'source_src'          => $model_extra_data->get_image_thumbnail_url(),
			'source_path'         => $model_extra_data->get_image_thumbnail_absolute_path(),
		] );

		$thumbmail_saved = $this->save( [
			'item_hash'          => $model_extra_data->get_image_thumbnail_url(),
			'original_mime_type' => $model->get_original_mime_type(),
		], $extra_data_thumbnail );

		if ( $thumbmail_saved instanceof \RIO_Process_Queue ) {
			$this->_saved_models[] = $thumbmail_saved;
		}

		\WRIO_Logger::info( sprintf( 'End webp convertation proccess for NextGen #%s. Saved models: %d', $model->get_id(), sizeof( $this->_saved_models ) ) );

		return true;
	}

	/**
	 * Get attachment data such as height, width, absolute path and URL.
	 *
	 * @param WP_Post|int $attachment    Attachment to get data for.
	 *
	 * @return array {
	 * Associative array of attachment data and its thumbnails, where key is sha256 hash or attachment URL.
	 *
	 * @type string       $size          Size of an image, e.g. medium.
	 * @type int          $height        Height in pixels.
	 * @type int          $width         Width in pixels.
	 * @type string       $mime          MIME type, e.g. image/jpeg.
	 * @type string       $absolute_path Absolute path to thumbnail.
	 * @type string       $url           URL path to thumbnail.
	 * }
	 */
	public static function get_attachment_data( $attachment ) {
		$attachment = get_post( $attachment );
		$hashmap    = [];

		if ( empty( $attachment ) ) {
			return $hashmap;
		}

		$dirs = wp_upload_dir();

		if ( isset( $dirs['error'] ) && $dirs['error'] !== false ) {
			return $hashmap;
		}

		$attachment_meta = wp_get_attachment_metadata( $attachment->ID );

		// Fallback to get attachment meta it can be empty when WordPress failed to create it or invocation
		// of method was produced too soon
		if ( empty( $attachment_meta ) ) {
			\WRIO_Logger::error( sprintf( 'Attachment #%d metadata is empty. Webp image can not be converted.',$attachment->ID ) );

			return $hashmap;
			/*$exploded_url = explode( 'wp-content/uploads/', $attachment->guid, 2 );

			if ( isset( $exploded_url[1] ) ) {
				$exploded_relative_path = trim( $exploded_url[1] );
				$path_from_url          = trailingslashit( $dirs['basedir'] ) . $exploded_relative_path;

				// Need to remove this filter, as it would start recursion
				remove_filter( 'wp_generate_attachment_metadata', 'WRIO_Media_Library::optimize_after_upload' );

				$attachment_meta = wp_generate_attachment_metadata( $attachment->ID, $path_from_url );

				add_filter( 'wp_generate_attachment_metadata', 'WRIO_Media_Library::optimize_after_upload', 10, 2 );
			}*/
		}

		if ( isset( $dirs['basedir'] ) && isset( $attachment_meta['file'] ) ) {

			$original = [
				'size'          => 'original',
				'height'        => $attachment_meta['height'],
				'width'         => $attachment_meta['width'],
				'absolute_path' => wp_normalize_path( trailingslashit( $dirs['basedir'] ) . $attachment_meta['file'] ),
				'url'           => \WRIO_Url::normalize( trailingslashit( $dirs['baseurl'] ) . $attachment_meta['file'] ),
			];

			$hashmap[ hash( 'sha256', $original['url'] ) ] = $original;

			foreach ( $attachment_meta['sizes'] as $size => $size_data ) {
				// [2019, 01, somename.jpg]
				$exploded = explode( '/', $attachment_meta['file'] );

				// [2019, 01]
				array_pop( $exploded );

				// [2019, 01, someothername.jpg]
				$exploded[] = $size_data['file'];

				$new_file = implode( '/', $exploded );

				$url           = \WRIO_Url::normalize( trailingslashit( $dirs['baseurl'] ) . $new_file );
				$absolute_path = wp_normalize_path( trailingslashit( $dirs['basedir'] ) . $new_file );
				$hashed_url    = hash( 'sha256', $url );

				$hashmap[ $hashed_url ] = [
					'size'          => $size,
					'height'        => $size_data['height'],
					'width'         => $size_data['width'],
					'mime'          => $size_data['mime-type'],
					'absolute_path' => $absolute_path,
					'url'           => $url,
				];
			}
		}

		return $hashmap;
	}

	/**
	 * Add new image to be converted to WebP.
	 *
	 * @param array                 $props        List of properties to be set on the model.
	 * @param \RIOP_WebP_Extra_Data $extra_data   List of extra data params
	 *
	 * @return false|\RIO_Process_Queue
	 */
	public function save( $props, $extra_data ) {

		$model = new \RIO_Process_Queue();

		if ( isset( $props['item_hash'] ) ) {
			$model->set_item_hash( $props['item_hash'] );
		}

		if ( isset( $props['item_hash_alternative'] ) ) {
			$model->set_item_hash_alternative( $props['item_hash_alternative'] );
		}

		if ( isset( $props['object_id'] ) ) {
			$model->object_id = $props['object_id'];
		}

		if ( isset( $props['original_mime_type'] ) ) {
			$model->original_mime_type = $props['original_mime_type'];
		}

		$model->item_type        = self::DEFAULT_TYPE;
		$model->result_status    = \RIO_Process_Queue::STATUS_PROCESSING;
		$model->processing_level = \WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level', \RIO_Process_Queue::LEVEL_NORMAL );
		$model->is_backed_up     = false;
		$model->original_size    = @filesize( $extra_data->get_source_path() );
		$model->final_size       = 0; // to be known
		$model->final_mime_type  = 'image/webp';
		$model->extra_data       = $extra_data;

		$is_saved = $model->save();

		if ( $is_saved ) {
			\WRIO_Logger::info( sprintf( 'Saved item with src "%s" and hash "%s" successfully', $extra_data->get_source_src(), $model->get_item_hash() ) );

			return $model;
		}

		\WRIO_Logger::info( sprintf( 'Failed to save item with src "%s" and hash "%s" as save() method failed, check SQL for errors', $extra_data->get_source_src(), $model->get_item_hash() ) );

		return false;
	}
}
