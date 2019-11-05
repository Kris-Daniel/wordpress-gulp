<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с резервным копированием изображений
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIOP_Backup extends WIO_Backup {

	CONST CF_BACKUP_DIR_NAME = 'custom-folders';
	CONST NEXTGEN_BACKUP_DIR_NAME = 'nextgen-gallery';

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;

	/**
	 * Получает путь к папке с резервными копиями
	 *
	 * @param array $gallery_meta   метаданные аттачмента
	 *
	 * @return string
	 */
	public function getNextgenBackupDir( $gallery_meta ) {
		$backup_dir = $this->getBackupDir();
		$backup_dir .= self::NEXTGEN_BACKUP_DIR_NAME . '/' . $gallery_meta->gid;

		if ( ! is_dir( $backup_dir ) ) {
			$backup_dir = $this->mkdir( $backup_dir );

			if ( is_wp_error( $backup_dir ) ) {
				return $backup_dir;
			}
		}

		return trailingslashit( $backup_dir );
	}

	/**
	 * Делаем резервную копию NextGEN
	 *
	 * @param WRIO_Image_Nextgen $nextgen_image   аттачмент
	 *
	 * @return bool|WP_Error
	 */
	public function backupNextgen( $nextgen_image ) {
		$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );

		if ( ! $backup_origin_images ) {
			return false; // если бекап не требуется
		}

		$original_file           = $nextgen_image->get( 'path' );
		$original_thumbnail_file = $nextgen_image->get( 'thumbnail_path' );
		$backup_dir              = $this->getNextgenBackupDir( $nextgen_image->get( 'gallery_meta' ) );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		$backup_file = $backup_dir . $nextgen_image->get( 'file' );

		if ( is_file( $original_file ) ) {
			if ( ! @copy( $original_file, $backup_file ) ) {
				WRIO_Logger::error( sprintf( 'Failed to swap original file %s with %s as copy() failed', $backup_file, $original_file ) );

				return false;
			}
		}

		$backup_thumbnail_file = $backup_dir . $nextgen_image->get( 'thumbnail_file' );

		if ( is_file( $original_thumbnail_file ) ) {
			if ( @copy( $original_thumbnail_file, $backup_thumbnail_file ) ) {
				WRIO_Logger::error( sprintf( 'Failed to swap thumbnail file %s with %s as copy() failed', $backup_thumbnail_file, $original_thumbnail_file ) );

				return false;
			}
		}

		return true;
	}

	/**
	 * Restore NextGen images piece by piece.
	 *
	 * @param int $limit     Limit on number of NextGen image to restore. Default: 50, maximum 1000.
	 *
	 * @return array {
	 *     Result of process: how many images restored and how many remain.
	 * @type int  $processed Count of processed images.
	 * @type int  $remane    Count of remained images to be processed.
	 * }
	 */
	public function restoreAllNextGen( $limit = 50 ) {

		if ( ! is_numeric( $limit ) || is_numeric( $limit ) && $limit > 1000 ) {
			$limit = 50;
		}

		$queue_table = RIO_Process_Queue::table_name();
		$nextgen_sql = "SELECT * FROM {$queue_table} WHERE `item_type` = %s AND `result_status` = %s LIMIT %d";

		global $wpdb;

		$nextgen_images = $wpdb->get_results( $wpdb->prepare( $nextgen_sql, 'nextgen', RIO_Process_Queue::STATUS_SUCCESS, $limit ) );

		$result = [
			'processed' => 0,
			'remane'    => 0,
		];

		if ( empty( $nextgen_images ) ) {
			return $result;
		}

		foreach ( $nextgen_images as $nextgen_image ) {
			$nextgen_model = new WRIO_Image_Nextgen( $nextgen_image->object_id );

			$restored = $nextgen_model->restore();

			if ( ! is_wp_error( $restored ) ) {
				$result['processed'] = $result['processed'] ++;
			} else {
				WRIO_Logger::error( sprintf( 'Failed to restore nextgen image ID: %s as %s::%s failed with message: %s', $nextgen_image->object_id, get_class( 'WRIO_Image_Nextgen' ), 'restore()', $restored->get_error_message() ) );
			}
		}

		$nextgen_sql_remane = "SELECT COUNT(*) AS remane FROM {$queue_table} WHERE `item_type` = %s AND `result_status` = %s";

		$nextgen_image_remane = $wpdb->get_var( $wpdb->prepare( $nextgen_sql_remane, 'nextgen', RIO_Process_Queue::STATUS_SUCCESS ) );

		if ( $nextgen_image_remane === null ) {
			WRIO_Logger::error( sprintf( 'Failed to get remained number of nextgen image by SQL: %s', $nextgen_sql_remane ) );
		}

		$result['remane'] = $nextgen_image_remane !== null ? (int) $nextgen_image_remane : 0;

		if ( $result['remane'] === 0 ) {
			// Should empty original/optimized size once all backups are empty
			WRIO_Plugin::app()->updateOption( 'nextgen_original_size', 0 );
			WRIO_Plugin::app()->updateOption( 'nextgen_optimized_size', 0 );
		}

		return $result;
	}

	/**
	 * Restore Custom Folders piece by piece.
	 *
	 * @param int $limit     Limit on number of Custom Folders to restore. Default: 50, maximum 1000.
	 *
	 * @return array {
	 *     Result of process: how many folders restored and how many remain.
	 * @type int  $processed Count of processed folders.
	 * @type int  $remane    Count of remained folders to be processed.
	 * }
	 */
	public function restoreAllCustomFolders( $limit = 50 ) {
		if ( ! is_numeric( $limit ) || is_numeric( $limit ) && $limit > 1000 ) {
			$limit = 50;
		}

		$queue_table = RIO_Process_Queue::table_name();
		$cf_sql      = "SELECT * FROM {$queue_table} WHERE `item_type` = %s AND `result_status` = %s LIMIT %d";

		global $wpdb;

		$cf_images = $wpdb->get_results( $wpdb->prepare( $cf_sql, 'cf_image', RIO_Process_Queue::STATUS_SUCCESS, $limit ) );

		$result = [
			'processed' => 0,
			'remane'    => 0,
		];

		if ( empty( $cf_images ) ) {
			return $result;
		}

		foreach ( $cf_images as $cf_image ) {
			$cf_model = new WRIO_Folder_Image( $cf_image->object_id, $cf_image );

			$restored = $cf_model->restore();

			if ( ! is_wp_error( $restored ) ) {
				$result['processed'] = $result['processed'] ++;
			} else {
				WRIO_Logger::error( sprintf( 'Failed to restore Custom Folder ID: %s as %s::%s failed with message: %s', $cf_image->object_id, get_class( 'WRIO_Folder_Image' ), 'restore()', $restored->get_error_message() ) );
			}
		}

		$cf_sql_remane = "SELECT COUNT(*) AS remane FROM {$queue_table} WHERE `item_type` = %s AND `result_status` = %s";

		$cf_image_remane = $wpdb->get_var( $wpdb->prepare( $cf_sql_remane, 'cf_image', RIO_Process_Queue::STATUS_SUCCESS ) );

		if ( $cf_image_remane === null ) {
			WRIO_Logger::error( sprintf( 'Failed to get remained number of Custom Folder by SQL: %s', $cf_sql_remane ) );
		}

		$result['remane'] = $cf_image_remane !== null ? (int) $cf_image_remane : 0;

		if ( $result['remane'] === 0 ) {
			// Should empty original/optimized size once all backups are empty
			WRIO_Plugin::app()->updateOption( 'folders_original_size', 0 );
			WRIO_Plugin::app()->updateOption( 'folders_optimized_size', 0 );

			$custom_folders = WRIO_Custom_Folders::get_instance();
			$folders        = $custom_folders->getFolders();

			if ( ! empty( $folders ) ) {
				foreach ( $folders as $folder ) {
					$folder->reCountOptimizedFiles();
				}
				$custom_folders->saveFolders();
			}
		}

		return $result;
	}

	/**
	 * Восстанавливаем из резервной копии NextGEN
	 *
	 * @param WRIO_Image_Nextgen $nextgen_image   аттачмент
	 *
	 * @return bool|WP_Error
	 */

	public function restoreNextgen( $nextgen_image ) {

		$original_file           = $nextgen_image->get( 'path' );
		$original_thumbnail_file = $nextgen_image->get( 'thumbnail_path' );
		$backup_dir              = $this->getNextgenBackupDir( $nextgen_image->get( 'gallery_meta' ) );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		$backup_file           = $backup_dir . $nextgen_image->get( 'file' );
		$backup_thumbnail_file = $backup_dir . $nextgen_image->get( 'thumbnail_file' );

		if ( ! is_file( $backup_file ) ) {
			$error_msg = sprintf( "Unable to restore from a backup. There is no file (%s).", $backup_file );
			WRIO_Logger::error( sprintf( '%s, Nextgen image id: %s', $error_msg, $nextgen_image->get( 'id' ) ) );

			return new WP_Error( 'file_not_exists', $error_msg );
		}

		// Restore original file
		if ( ! $this->restore_file( $backup_file, $original_file ) ) {
			return false;
		}

		//Restore thumbnail file
		if ( ! $this->restore_file( $backup_thumbnail_file, $original_thumbnail_file ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Получает путь к папке с резервными копиями
	 *
	 * @param string $image_abs_path   абсолютный путь к файлу картинки
	 *
	 * @return string
	 */

	public function getCFBackupDir( $image_abs_path ) {
		$backup_dir = $this->getBackupDir();

		$image_abs_path = wp_normalize_path( $image_abs_path );
		$wp_abs_path    = wp_normalize_path( ABSPATH );

		// Get all subfolders in which the image is stored.
		// This is necessary to create an alternate subfolders
		// in directory where they are stored in backups.
		$subfolders = str_replace( $wp_abs_path, '', dirname( $image_abs_path ) );

		$backup_dir .= self::CF_BACKUP_DIR_NAME . '/' . $subfolders;

		if ( ! is_dir( $backup_dir ) ) {
			$backup_dir = $this->mkdir( $backup_dir );

			if ( is_wp_error( $backup_dir ) ) {
				return $backup_dir;
			}
		}

		return trailingslashit( $backup_dir );
	}

	/**
	 * Делаем резервную копию Custom Folder Image
	 *
	 * @param WRIO_Folder_Image $folder_image   Custom Folder Image
	 *
	 * @return bool|WP_Error
	 */
	public function backupCFImage( $folder_image ) {
		$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );

		if ( ! $backup_origin_images ) {
			return false; // если бекап не требуется
		}

		$original_file = $folder_image->get( 'path' );
		$backup_dir    = $this->getCFBackupDir( $original_file );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		$backup_file = $backup_dir . wp_basename( $original_file );

		if ( is_file( $original_file ) ) {
			if ( ! @copy( $original_file, $backup_file ) ) {
				WRIO_Logger::error( sprintf( 'Failed to swap original file %s with %s as copy() failed', $backup_file, $original_file ) );

				return false;
			}
		}

		return true;
	}

	/**
	 * Восстанавливаем из резервной копии Custom Folder Image
	 *
	 * @param WRIO_Folder_Image $folder_image   Custom Folder Image
	 *
	 * @return bool|WP_Error
	 */
	public function restoreCFImage( $folder_image ) {

		$original_file = $folder_image->get( 'path' );
		$backup_dir    = $this->getCFBackupDir( $original_file );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		$backup_file = $backup_dir . wp_basename( $original_file );

		if ( ! $this->restore_file( $backup_file, $original_file ) ) {
			return false;
		}

		return true;
	}
}
