<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы с резервным копированием изображений.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WIO_Backup {

	CONST BACKUP_DIR_NAME = 'wio_backup';
	CONST TEMP_DIR_NAME = 'temp';

	/**
	 * The single instance of the class.
	 *
	 * @since  1.3.0
	 * @access protected
	 * @var    object
	 */
	protected static $_instance;

	/**
	 * @var array Данные о папке uploads, возвращаемые функцией wp_upload_dir()
	 */
	protected $wp_upload_dir;

	/**
	 * @var string Путь к папке с резервными копиями изображений
	 */
	private $backup_dir;

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 * @var string
	 */
	private $blog_backup_dir;

	/**
	 * Инициализация бекапа
	 */
	public function __construct() {
		$this->wp_upload_dir = wp_upload_dir();
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
	 * Проверка возможности записи в папку uploads.
	 *
	 * @return bool
	 */
	public function isUploadWritable() {
		$upload_dir = $this->wp_upload_dir['basedir'];

		if ( is_dir( $upload_dir ) && wp_is_writable( $upload_dir ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Проверка возможности записи в папку бекап.
	 *
	 * @return bool
	 */
	public function isBackupWritable() {

		$backup_dir = $this->getBackupDir();

		if ( is_wp_error( $backup_dir ) || ! wp_is_writable( $backup_dir ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Путь к папке с бекапами
	 *
	 * @return string|WP_Error
	 */
	public function getBackupDir() {
		if ( $this->backup_dir ) {
			return $this->backup_dir;
		}

		$backup_dir = wp_normalize_path( trailingslashit( $this->wp_upload_dir['basedir'] ) . self::BACKUP_DIR_NAME );

		if ( ! is_dir( $backup_dir ) ) {
			$backup_dir = $this->mkdir( $backup_dir );

			if ( is_wp_error( $backup_dir ) ) {
				return $backup_dir;
			}
		}

		$this->backup_dir = trailingslashit( $backup_dir );

		return $this->backup_dir;
	}

	/**
	 * Путь к папке с бекапами блога.
	 *
	 * Используется в мультисайт режиме.
	 *
	 * @return string|WP_Error
	 */
	public function getBlogBackupDir() {
		if ( $this->blog_backup_dir ) {
			return $this->blog_backup_dir;
		}

		$wp_upload_dir = wp_upload_dir();
		$backup_dir    = wp_normalize_path( trailingslashit( $wp_upload_dir['basedir'] ) . self::BACKUP_DIR_NAME );

		if ( ! is_dir( $backup_dir ) ) {
			$backup_dir = $this->mkdir( $backup_dir );

			if ( is_wp_error( $backup_dir ) ) {
				return $backup_dir;
			}
		}

		$this->blog_backup_dir = trailingslashit( $backup_dir );

		return $this->blog_backup_dir;
	}

	/**
	 * Очищает папку с резервными копиями
	 *
	 * @return bool
	 */
	public function removeBackupDir() {
		$backup_dir = $this->getBackupDir();

		return wrio_rmdir( $backup_dir );
	}

	/**
	 * Очищает папку с резервными копиями блога
	 * Используется в мультисайт режиме
	 *
	 * @return bool
	 */
	public function removeBlogBackupDir() {
		$backup_dir = $this->getBlogBackupDir();

		return wrio_rmdir( $backup_dir );
	}

	/**
	 * Получает путь к папке с резервными копиями
	 *
	 * @param array $attachment_meta   метаданные аттачмента
	 *
	 * @return string
	 */
	public function getAttachmentBackupDir( $attachment_meta ) {
		$backup_dir = $this->getBackupDir();

		// Get all subfolders in which the image is stored.
		// This is necessary to create an alternate subfolders
		// in directory where they are stored in backups.
		$subfolders = dirname( $attachment_meta['file'] );

		$backup_dir .= $subfolders;

		if ( ! is_dir( $backup_dir ) ) {
			$backup_dir = $this->mkdir( $backup_dir );

			if ( is_wp_error( $backup_dir ) ) {
				return $backup_dir;
			}
		}

		return trailingslashit( $backup_dir );
	}

	/**
	 * Делаем резервную копию аттачмента
	 *
	 * @param WIO_Attachment $wio_attachment   аттачмент
	 *
	 * @return bool|WP_Error
	 */
	public function backupAttachment( WIO_Attachment $wio_attachment ) {
		$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );

		if ( ! $backup_origin_images ) {
			return false; // если бекап не требуется
		}

		$backup_dir = $this->getAttachmentBackupDir( $wio_attachment->get( 'attachment_meta' ) );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		$full = $this->backupAttachmentSize( $wio_attachment );

		if ( is_wp_error( $full ) ) {
			return $full;
		}

		$allowed_sizes = $wio_attachment->getAllowedSizes();

		if ( ! empty( $allowed_sizes ) ) {
			foreach ( (array) $allowed_sizes as $image_size ) {
				$size_backup = $this->backupAttachmentSize( $wio_attachment, $image_size );

				if ( is_wp_error( $size_backup ) ) {
					return $size_backup;
				}
			}
		}

		return true;
	}

	/**
	 * Восстанавливаем аттачмент из резервной копии
	 *
	 * @param WIO_Attachment $wio_attachment   аттачмент
	 *
	 * @return bool|WP_Error
	 */
	public function restoreAttachment( WIO_Attachment $wio_attachment ) {
		$backup_dir = $this->getAttachmentBackupDir( $wio_attachment->get( 'attachment_meta' ) );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		$restore_result = $this->restoreAttachmentSize( $wio_attachment );

		if ( is_wp_error( $restore_result ) ) {
			return $restore_result;
		}

		$attachment_meta = wp_get_attachment_metadata( $wio_attachment->get( 'id' ) );

		if ( isset( $attachment_meta['old_width'] ) && isset( $attachment_meta['old_width'] ) ) {
			$attachment_meta['width']  = $attachment_meta['old_width'];
			$attachment_meta['height'] = $attachment_meta['old_height'];
			wp_update_attachment_metadata( $wio_attachment->get( 'id' ), $attachment_meta );
		}

		$allowed_sizes = $wio_attachment->getAllowedSizes();

		if ( $allowed_sizes ) {
			foreach ( $allowed_sizes as $image_size ) {
				$this->restoreAttachmentSize( $wio_attachment, $image_size );
			}
		}

		return true;
	}

	/**
	 * Создает временное изображение с уникальным именем.
	 *
	 * Необходимо для провайдеров, который кешируют изображения по имени файла,
	 * чтобы сбросить кеш, нужно отдать провайдеру изображение с другим именем.
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.1.2
	 *
	 * @param string $file_path   путь к изображению
	 *
	 * @return array|WP_Error
	 */
	public function createTempAttachment( $file_path ) {
		if ( $this->isBackupWritable() ) {

			$temp_dir     = $this->getBackupDir() . self::TEMP_DIR_NAME . '/';
			$temp_dir_url = trailingslashit( $this->wp_upload_dir['baseurl'] ) . self::BACKUP_DIR_NAME . '/' . self::TEMP_DIR_NAME . '/';

			if ( ! is_dir( $temp_dir ) ) {
				$temp_dir = $this->mkdir( $temp_dir );

				if ( is_wp_error( $temp_dir ) ) {
					return $temp_dir;
				}
			}

			$temp_file_id   = uniqid();
			$file_name      = pathinfo( $file_path, PATHINFO_FILENAME );
			$file_extension = pathinfo( $file_path, PATHINFO_EXTENSION );
			$new_file_name  = $temp_file_id . '_' . md5( $file_name ) . '.' . $file_extension;

			$temp_file_path = $temp_dir . $new_file_name;
			$temp_file_url  = $temp_dir_url . $new_file_name;

			if ( is_file( $file_path ) ) {
				if ( ! @copy( $file_path, $temp_file_path ) ) {
					WRIO_Logger::error( sprintf( 'Failed to swap original file %s with %s as copy() failed.', $temp_file_path, $file_path ) );

					return new WP_Error( 'copy_file_to_temp_dir_error', __( 'Could not copy the file to the temporary directory', 'robin-image-optimizer' ) );
				}
			}

			WRIO_Logger::info( sprintf( 'Creation of temporary attachment (%s) successfully completed!', $file_path ) );

			return [
				'id'         => $temp_file_id,
				'image_path' => $temp_file_path,
				'image_url'  => $temp_file_url,
			];
		}

		return new WP_Error( 'backup_writable_error', __( 'It is not possible to create a temporary file, the backup folder is not writable.', 'robin-image-optimizer' ) );
	}

	/**
	 * Резервное копирование файла аттачмента.
	 *
	 * @param WIO_Attachment $wio_attachment   аттачмент
	 * @param string         $image_size       Размер(thumbnail, medium ... )
	 *
	 * @return bool|WP_Error
	 */
	protected function backupAttachmentSize( WIO_Attachment $wio_attachment, $image_size = '' ) {
		if ( $image_size ) {
			$original_file = $wio_attachment->getImageSizePath( $image_size );
		} else {
			$original_file = $wio_attachment->get( 'path' );
		}

		$backup_dir = $this->getAttachmentBackupDir( $wio_attachment->get( 'attachment_meta' ) );

		// проверить запись в папку
		if ( is_wp_error( $backup_dir ) ) {
			WRIO_Logger::error( sprintf( 'Failed to create backup dir, error: %s', $backup_dir->get_error_message() ) );

			return $backup_dir;
		}

		if ( ! $original_file ) {
			// бывает такое, что размера превьюшки нет в базе данных.
			// это не считается ошибкой, поэтому сразу пропускаем
			return false;
		}

		$backup_file = $backup_dir . wp_basename( $original_file );

		if ( is_file( $original_file ) ) {
			if ( ! @copy( $original_file, $backup_file ) ) {
				WRIO_Logger::error( sprintf( 'Failed to copy %s to %s as copy() failed', $original_file, $backup_file ) );
			}
		}

		return true;
	}

	/**
	 * Восстановление файла аттачмента из резервной копии
	 *
	 * @param WIO_Attachment $wio_attachment   аттачмент
	 * @param string|null    $image_size       Размер(thumbnail, medium ... )
	 *
	 * @return bool|WP_Error
	 */
	protected function restoreAttachmentSize( WIO_Attachment $wio_attachment, $image_size = null ) {

		if ( ! empty( $image_size ) ) {
			$original_file = $wio_attachment->getImageSizePath( $image_size );
		} else {
			$original_file = $wio_attachment->get( 'path' );
		}

		$backup_dir = $this->getAttachmentBackupDir( $wio_attachment->get( 'attachment_meta' ) );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		if ( empty( $original_file ) ) {
			return false;
		}

		$backup_file = $backup_dir . wp_basename( $original_file );

		if ( ! is_file( $backup_file ) ) {
			WRIO_Logger::error( sprintf( 'Unable to restore from a backup. There is no file, attachment id: %s, backup file: %s', $wio_attachment->get( 'id' ), $backup_file ) );

			return false;
		}

		if ( ! @copy( $backup_file, $original_file ) ) {
			WRIO_Logger::error( sprintf( 'Failed to swap %s with %s as copy() failed', $backup_file, $original_file ) );

			return false;
		}

		if ( ! @unlink( $backup_file ) ) {
			WRIO_Logger::error( sprintf( 'Failed to delete backup file %s as unlink() failed', $backup_file ) );

			return false;
		}

		WRIO_Logger::info( sprintf( 'Restored file: %s.', $backup_file ) );

		return true;
	}

	/**
	 * Удаляем резервные копии аттачмента
	 *
	 * @param int $attachment_id   аттачмент id
	 *
	 * @return bool|WP_Error
	 */
	public function removeAttachmentBackup( $attachment_id ) {
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$backup_dir      = $this->getAttachmentBackupDir( $attachment_meta );

		if ( is_wp_error( $backup_dir ) ) {
			return $backup_dir;
		}

		$main_file_path = $backup_dir . wp_basename( $attachment_meta['file'] );

		if ( ! file_exists( $main_file_path ) ) {
			WRIO_Logger::error( sprintf( "Failed to remove an attachment file. File (%s) isn't exists. Attachment #%s", $main_file_path, $attachment_id ) );
		}

		if ( ! @unlink( $main_file_path ) ) {
			WRIO_Logger::error( sprintf( 'Failed to unlink a main file (%s) for attachment #%s', $main_file_path, $attachment_id ) );
		}

		if ( isset( $attachment_meta['sizes'] ) && is_array( $attachment_meta['sizes'] ) ) {
			foreach ( $attachment_meta['sizes'] as $size ) {
				$thumbnail_file_path = $backup_dir . $size['file'];

				if ( ! file_exists( $thumbnail_file_path ) ) {
					WRIO_Logger::error( sprintf( "Failed to remove a thumbnail file. File (%s) isn't exists. Attachment #%s", $thumbnail_file_path, $attachment_id ) );
				}

				if ( ! @unlink( $thumbnail_file_path ) ) {
					WRIO_Logger::error( sprintf( 'Failed to unlink thumbnail (%s) for attachment #%s', $thumbnail_file_path, $attachment_id ) );
				}
			}
		}

		return true;
	}

	/**
	 * alternateStorage
	 *
	 * @param array $servers
	 *
	 * @return array
	 */
	public static function alternateStorage( $servers ) {
		$servers['server_4'] = 'https://' . str_rot13( 'vzntrpbzcerffbe.pbz' );
		$servers['server_3'] = 'https://' . str_rot13( 'v0.jc.pbz' );

		return $servers;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 *
	 * @param string $dir
	 *
	 * @return string|WP_Error
	 */
	protected function mkdir( $dir ) {
		WRIO_Logger::info( sprintf( 'Try to create backup directory. Backup dir: (%s)', $dir ) );

		if ( ! wp_mkdir_p( $dir ) ) {
			WRIO_Logger::error( sprintf( 'Unable to create backup directory (%s) as mkdir() failed', $dir ) );

			return new WP_Error( 'mkdir_failed', sprintf( "Unable to create backup folder (%s) as mkdir() failed.", $dir ) );
		}

		return $dir;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.0
	 *
	 * @param string $backup_file
	 * @param string $original_file
	 *
	 * @return bool
	 */
	protected function restore_file( $backup_file, $original_file ) {
		if ( is_file( $backup_file ) ) {
			if ( ! @copy( $backup_file, $original_file ) ) {
				WRIO_Logger::error( sprintf( 'Failed to swap original file (%s) with %s as copy() failed', $backup_file, $original_file ) );

				return false;
			}

			if ( ! @unlink( $backup_file ) ) {
				WRIO_Logger::error( sprintf( 'Failed to delete backup file (%s) as unlink() failed', $backup_file ) );

				return false;
			}

			WRIO_Logger::info( sprintf( 'Restored file: %s.', $backup_file ) );

			return true;
		}

		WRIO_Logger::error( sprintf( 'Unable to restore from a backup. There is no file (%s).', $backup_file ) );

		return false;
	}
}
