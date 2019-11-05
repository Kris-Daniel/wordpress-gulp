<?php

/**
 * AJAX обработчик выбора папки
 */
add_action( 'wp_ajax_wriop_browse_dir', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	if ( is_main_site() ) {
		$base = get_home_path();
		$root = wp_normalize_path( $base );
	} else {
		$up   = wp_upload_dir();
		$root = wp_normalize_path( $up['basedir'] );
	}

	$dir = trim( WRIO_Plugin::app()->request->post( 'dir', null, 'rawurldecode' ) );

	$multiselect = WRIO_Plugin::app()->request->post( 'multiSelect' );
	$multiselect = $multiselect == 'true' ? true : false;

	$only_folders = WRIO_Plugin::app()->request->post( 'onlyFolders' );
	$only_folders = $only_folders == 'true' ? true : false;
	$only_folders = $dir == '/' || $only_folders;

	$only_files = WRIO_Plugin::app()->request->post( 'onlyFiles' );
	$only_files = $only_files == 'true' ? true : false;

	$selected_dir = trailingslashit( $root ) . ( $dir == '/' ? '' : $dir );

	// set checkbox if multiSelect set to true
	$checkbox = $multiselect ? "<input type='checkbox' />" : null;

	$upload_dir      = wp_upload_dir();
	$upload_dir_path = trailingslashit( str_replace( ABSPATH, '', $upload_dir['basedir'] ) );
	$wp_content_dir  = trailingslashit( str_replace( ABSPATH, '', WP_CONTENT_DIR ) );

	$ngg_path        = str_replace( wp_normalize_path( ABSPATH ), '', wrio_get_ngg_galleries_path() );
	$shortpixel_path = str_replace( wp_normalize_path( ABSPATH ), '', wrio_get_shortpixel_path() );
	$ewww_path       = str_replace( wp_normalize_path( ABSPATH ), '', wrio_get_ewww_tools_path() );
	$wc_path         = str_replace( wp_normalize_path( ABSPATH ), '', wrio_get_wc_logs_path() );

	$exclude_dirs = [
		$ngg_path,
		$shortpixel_path,
		$ewww_path,
		$wc_path,
		$wp_content_dir . 'backup',
		$wp_content_dir . 'backups',
		$wp_content_dir . 'cache',
		$wp_content_dir . 'lang',
		$wp_content_dir . 'langs',
		$wp_content_dir . 'languages',
		$upload_dir_path . 'wio_backup',
		$upload_dir_path . 'wrio',
		$upload_dir_path . 'wrio-webp-uploads',
		//'wp-admin',
		//'wp-includes'
	];
	// исключаем все директории /wp-content/uploads/2019 - они уже оптимизируются в медиабиблиотеке.
	// с основания WP в 2003 году до текущего года + 1 на всякий случай.
	$year = date( 'Y' ) + 1;
	for ( $i = 2003; $i <= $year; $i ++ ) {
		$exclude_dirs[] = $upload_dir_path . $i;
	}

	if ( file_exists( $selected_dir ) && is_dir( $selected_dir ) ) {

		$files      = scandir( $selected_dir );
		$return_dir = substr( $selected_dir, strlen( $root ) );

		natcasesort( $files );

		if ( count( $files ) > 2 ) { // The 2 accounts for . and ..
			echo "<ul class='jqueryFileTree'>";
			$counter = 0;
			foreach ( $files as $file ) {
				// если в папке очень много файлов, то показываем не все.
				if ( $counter ++ > 200 ) {
					break;
				}
				// если это папка бекап или другое исключение - пропускаем
				if ( in_array( $return_dir . $file, $exclude_dirs ) ) {
					continue;
				}

				$htmlRel  = str_replace( "'", "&apos;", $return_dir . $file );
				$htmlName = htmlentities( $file );
				$ext      = preg_replace( '/^.*\./', '', $file );

				if ( file_exists( $selected_dir . $file ) && $file != '.' && $file != '..' ) {
					//KEEP the spaces in front of the rel values - it's a trick to make WP Hide not replace the wp-content path
					if ( is_dir( $selected_dir . $file ) && ( ! $only_files || $only_folders ) ) {
						echo "<li class='directory collapsed'>{$checkbox}<a rel=' " . $htmlRel . "/'>" . $htmlName . "</a></li>";
					} else if ( ! $only_folders || $only_files ) {
						echo "<li class='file ext_{$ext}'>{$checkbox}<a rel=' " . $htmlRel . "'>" . $htmlName . "</a></li>";
					}
				}
			}

			echo "</ul>";
		}
	}
	die();
} );

/**
 * AJAX обработчик добавления папки
 */
add_action( 'wp_ajax_wrio-add-custom-folder', function () {
	check_admin_referer( 'bulk_optimization' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$path = WRIO_Plugin::app()->request->request( 'path', null, true );

	if ( empty( $path ) ) {
		wp_die( - 1 );
	}

	$cf     = WRIO_Custom_Folders::get_instance();
	$folder = $cf->addFolder( $path );

	if ( is_wp_error( $folder ) ) {
		wp_send_json_error( [
			'error_message' => $folder->get_error_message(),
			'error_code'    => $folder->get_error_code()
		] );
	}

	wp_send_json_success( $folder->toArray() );
} );

/**
 * AJAX индексация папки
 */
add_action( 'wp_ajax_wrio-scan-folder', function () {
	check_admin_referer( 'bulk_optimization' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$uid    = WRIO_Plugin::app()->request->request( 'uid', null, true );
	$offset = WRIO_Plugin::app()->request->request( 'offset', 0, true );
	$total  = WRIO_Plugin::app()->request->request( 'total', 0, true );

	if ( empty( $uid ) ) {
		wp_die( - 1 );
	}

	$max_process_elements = 100; // сколько элементов за итерацию индексирования

	$cf     = WRIO_Custom_Folders::get_instance();
	$folder = $cf->getFolder( $uid );

	if ( ! $total ) {
		$total = $folder->reCountFiles();
		$cf->saveFolders();
	}

	$processed_count = $folder->indexing( $offset, $max_process_elements );
	$offset          = $offset + $processed_count;

	$results = [
		'offset'   => $offset,
		'total'    => $total,
		'complete' => false,
		'percent'  => 0,
	];

	if ( $total ) {
		$results['percent'] = 100 - ( ( $total - $offset ) * 100 / $total );
		/**
		 * Операция индексирования состоит из двух этапов. Проверка существующих файлов и поиск новых файлов.
		 * Поэтому процент делим на 2 и добавляем 50% т.к. это вторая часть.
		 */
		$results['percent'] = $results['percent'] / 2 + 50;
	}

	if ( $offset >= $total ) {
		$results['percent']  = 100;
		$results['complete'] = true;
	}

	wp_send_json_success( $results );
} );

/**
 * AJAX обработчик удаления папки
 */
add_action( 'wp_ajax_wriop_remove_folder', function () {
	$uid = isset( $_POST['uid'] ) ? $_POST['uid'] : false;
	if ( ! $uid ) {
		die();
	}
	$cf = WRIO_Custom_Folders::get_instance();
	$cf->removeFolder( $uid );
	$cf->saveFolders();

	die();
} );

/**
 * AJAX проверка проиндексированных файлов
 */
add_action( 'wp_ajax_wriop_folder_sync_index', function () {
	$uid                  = isset( $_POST['uid'] ) ? $_POST['uid'] : false;
	$offset               = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
	$total                = isset( $_POST['total'] ) ? intval( $_POST['total'] ) : 0;
	$max_process_elements = 20; // сколько элементов за итерацию индексирования

	$cf              = WRIO_Custom_Folders::get_instance();
	$folder          = $cf->getFolder( $uid );
	$total           = $folder->countIndexedFiles();
	$processed_count = $folder->syncIndex( $offset, $max_process_elements );
	$offset          = $offset + $processed_count;
	$results         = [
		'offset'   => $offset,
		'total'    => $total,
		'complete' => false,
		'percent'  => 0,
	];
	if ( $total ) {
		$results['percent'] = 100 - ( ( $total - $offset ) * 100 / $total );
		/**
		 * Операция индексирования состоит из двух этапов. Проверка существующих файлов и поиск новых файлов.
		 * Поэтому процент делим на 2. Это первая часть.
		 */
		$results['percent'] = $results['percent'] / 2;
	}

	if ( $offset >= $total ) {
		$results['percent']  = 100;
		$results['complete'] = true;
	}

	wp_send_json( $results );
} );

/**
 * AJAX массовая оптимизация
 */
/*add_action( 'wp_ajax_wriop_process_cf_images', function () {
	check_admin_referer( 'wio-iph' );
	$reset_current_error = (bool) WRIO_Plugin::app()->request->request( 'reset_current_errors' );

	// в ajax запросе мы не знаем, получен ли он из мультиадминки или из обычной. Поэтому проверяем параметр, полученный из frontend
	if ( isset( $_POST['multisite'] ) and $_POST['multisite'] ) {
		$multisite = new WIO_Multisite;
		$multisite->initHooks();
	}

	$cf = WRIO_Custom_Folders::get_instance();

	/*$folders = $cf->getFolders();
	
	if ( ! empty( $folders ) ) {
		foreach ( (array) $folders as $folder ) {
			$folder              = $cf->getFolder( $folder->get( 'uid' ) );
			$count_files         = $folder->countFiles();
			$count_indexed_files = $folder->countIndexedFiles();
			$test                = 'fsdf';
		}
	}*/

/*if ( $reset_current_error ) {
	// сбрасываем текущие ошибки оптимизации
	$cf->resetCurrentErrors();
}

$max_process_per_request = 1;
$optimized_data          = $cf->processUnoptimizedImages( $max_process_per_request );

// если изображения закончились - посылаем команду завершения
if ( $optimized_data['remain'] <= 0 ) {
	$optimized_data['end'] = true;
}
wp_send_json( $optimized_data );
} );*/

/**
 * AJAX массовая оптимизация выбранной папки
 */
/*add_action( 'wp_ajax_wriop_process_cf_folder_images', function () {
	check_admin_referer( 'wio-iph' );

	// в ajax запросе мы не знаем, получен ли он из мультиадминки или из обычной. Поэтому проверяем параметр, полученный из frontend
	if ( isset( $_POST['multisite'] ) and $_POST['multisite'] ) {
		$multisite = new WIO_Multisite;
		$multisite->initHooks();
	}

	$cf                      = WRIO_Custom_Folders::get_instance();
	$max_process_per_request = 1;

	add_filter( 'wriop_cf_current_folder', function ( $folder_uid ) {
		$folder_uid = isset( $_POST['uid'] ) ? $_POST['uid'] : false;

		return $folder_uid;
	} );

	$optimized_data = $cf->processUnoptimizedImages( $max_process_per_request );

	// если изображения закончились - посылаем команду завершения
	if ( $optimized_data['remain'] <= 0 ) {
		$optimized_data['end'] = true;
	}
	wp_send_json( $optimized_data );
} );*/

/**
 * Переоптимизация cf_image. AJAX
 *
 */
add_action( 'wp_ajax_wio_cf_reoptimize_image', function () {
	$image_id             = (int) $_POST['id'];
	$backup               = WRIOP_Backup::get_instance();
	$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );
	$cf                   = WRIO_Custom_Folders::get_instance();

	if ( $backup_origin_images and ! $backup->isBackupWritable() ) {
		echo $cf->getMediaColumnContent( $image_id );
		die();
	}

	wp_suspend_cache_addition( true );

	$default_level = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level', 'normal' );
	$level         = isset( $_POST['level'] ) ? sanitize_text_field( $_POST['level'] ) : $default_level;

	$optimized_data = $cf->optimizeImage( $image_id, $level );

	if ( $optimized_data && isset( $optimized_data['processing'] ) ) {
		echo 'processing'; // эту строку не локализировать!
		die();
	}

	echo $cf->getMediaColumnContent( $image_id );
	die();
} );

/**
 * Восстановление
 *
 */
add_action( 'wp_ajax_wio_cf_restore_image', function () {
	wp_suspend_cache_addition( true );
	$image_id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	$cf               = WRIO_Custom_Folders::get_instance();
	$cf_image         = $cf->getImage( $image_id );
	$image_statistics = WRIO_Image_Statistic_Folders::get_instance();
	if ( $cf_image->isOptimized() ) {
		$restored = $cf_image->restore();
		if ( ! is_wp_error( $restored ) ) {
			$optimization_data = $cf_image->getOptimizationData();
			$optimized_size    = $optimization_data->get_final_size();
			$original_size     = $optimization_data->get_original_size();
			$image_statistics->deductFromField( 'optimized_size', $optimized_size );
			$image_statistics->deductFromField( 'original_size', $original_size );
			$image_statistics->save();

			$folder = $cf->getFolder( $cf_image->get( 'folder_uid' ) );
			$folder->reCountOptimizedFiles();
			$cf->saveFolders();
		}
	}

	echo $cf->getMediaColumnContent( $image_id );
	die();
} );

/**
 * AJAX обработчик восстановления из резервной копии
 */
/*add_action( 'wp_ajax_wio_cf_restore_backup', function () {
	check_admin_referer( 'wio-iph' );
	$max_process_per_request = 10; // сколько картинок восстанавливаем за 1 запрос
	$total                   = sanitize_text_field( $_POST['total'] );
	if ( isset( $_POST['blog_id'] ) && $_POST['blog_id'] ) {
		switch_to_blog( intval( $_POST['blog_id'] ) );
	}
	$folder_uid = isset( $_POST['uid'] ) ? sanitize_text_field( $_POST['uid'] ) : false;
	$cf         = WRIO_Custom_Folders::get_instance();
	if ( $total == '?' ) {
		$total = RIO_Process_Queue::count_by_type_status( 'cf_image', 'success' );
	}
	$restored_data = $cf->restoreFolderFromBackup( $folder_uid, $max_process_per_request );
	if ( isset( $_POST['blog_id'] ) && $_POST['blog_id'] ) {
		restore_current_blog();
	}
	$restored_data['total'] = $total;
	if ( $total ) {
		$restored_data['percent'] = 100 - ( $restored_data['remain'] * 100 / $total );
	} else {
		$restored_data['percent'] = 0;
	}
	// если изображения закончились - посылаем команду завершения
	if ( $restored_data['remain'] <= 0 ) {
		$restored_data['end'] = true;
	}
	wp_send_json( $restored_data );
} );*/

/**
 * Загружает шаблон для всплывающий окон
 */
/*add_action( 'wp_ajax_wio_cf_get_template_part', function () {
	$template  = sanitize_text_field( $_POST['template'] );
	$templates = [
		'select_folder'      => 'select-folder.php',
		'restore_folder'     => 'restore-folder.php',
		'sync_folder'        => 'sync-folder.php',
		'optimize_folder'    => 'optimize-folder.php',
		'sync_all_folders'   => 'sync-all-folders.php',
		'folders_table_body' => 'folders-table-body.php',
	];
	if ( isset( $templates[ $template ] ) ) {
		$template_file = WRIOP_PLUGIN_DIR . '/admin/pages/parts/' . $templates[ $template ];
		if ( file_exists( $template_file ) ) {
			include( $template_file );
		}
	}
	die();
} );*/

/**
 * Загружает шаблон для всплывающий окон
 */
add_action( 'wp_ajax_wio_cf_reload_ui', function () {
	$template_file = WRIOP_PLUGIN_DIR . '/admin/pages/parts/folders-table-body.php';
	$folders_table = '';
	if ( is_file( $template_file ) ) {
		ob_start();
		include( $template_file );
		$folders_table = ob_get_contents();
		ob_end_clean();
	}
	$image_statistics = WRIO_Image_Statistic_Folders::get_instance();
	$responce         = [
		'folders_table' => $folders_table,
		'statistic'     => $image_statistics->load(),
	];
	wp_send_json( $responce );
} );
