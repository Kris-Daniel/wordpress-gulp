<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Переоптимизация аттачмента
 */
function wbcr_riop_reoptimizeImage() {
	$image_id             = (int) $_POST['id'];
	$backup               = WRIOP_Backup::get_instance();
	$backup_origin_images = WRIO_Plugin::app()->getPopulateOption( 'backup_origin_images', false );
	$nextgen_gallery      = WRIO_Nextgen_Gallery::get_instance();
	if ( $backup_origin_images && ! $backup->isBackupWritable() ) {
		echo $nextgen_gallery->getMediaColumnContent( $image_id );
		die();
	}
	wp_suspend_cache_addition( true );
	$default_level = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_level', 'normal' );
	$level         = isset( $_POST['level'] ) ? sanitize_text_field( $_POST['level'] ) : $default_level;

	$optimized_data = $nextgen_gallery->optimizeNextgenImage( $image_id, $level );

	if ( $optimized_data && isset( $optimized_data['processing'] ) ) {
		echo 'processing';
		die();
	}

	echo $nextgen_gallery->getMediaColumnContent( $image_id );
	die();
}

/**
 * Восстановление
 */
function wbcr_riop_restoreImage() {
	wp_suspend_cache_addition( true );
	$image_id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	$nextgen_gallery  = WRIO_Nextgen_Gallery::get_instance();
	$nextgen_image    = $nextgen_gallery->getNextgenImage( $image_id );
	$image_statistics = WRIO_Image_Statistic_Nextgen::get_instance();
	if ( $nextgen_image->isOptimized() ) {
		$restored = $nextgen_image->restore();

		if ( ! is_wp_error( $restored ) ) {
			$optimization_data = $nextgen_image->getOptimizationData();
			$optimized_size    = $optimization_data->get_final_size();
			$original_size     = $optimization_data->get_original_size();
			$image_statistics->deductFromField( 'optimized_size', $optimized_size );
			$image_statistics->deductFromField( 'original_size', $original_size );
			$image_statistics->save();
		}
	}

	$nextgen_gallery = WRIO_Nextgen_Gallery::get_instance();
	echo $nextgen_gallery->getMediaColumnContent( $image_id );
	die();
}
