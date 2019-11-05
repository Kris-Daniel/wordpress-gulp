<?php
/**
 * Used to process different filters.
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 2018 Webraftic Ltd
 * @version 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processing restore back-up of custom folder and Nextgen.
 *
 * @return array {
 *     Processing result.
 * @type int $remane Count of remained images to be processed.
 * @type int $total Count of total processed items.
 * }
 * @since 1.0.4
 */
add_filter( 'wbcr/rio/backup/restore_filter', function ( $limit ) {
	
	$remane_count = 0;
	$total        = 0;
	
	$premium_backup = WRIOP_Backup::get_instance();
	
	if ( wrio_is_active_nextgen_gallery() ) {
		/* NextGen*/
		$nextgen_restored = $premium_backup->restoreAllNextGen( $limit );
		
		if ( isset( $nextgen_restored['remane'] ) ) {
			$remane_count += $nextgen_restored['remane'];
		}
		
		$nextgen_count = RIO_Process_Queue::count_by_type_status( 'nextgen', 'success' );
		
		if ( is_numeric( $nextgen_count ) && (int) $nextgen_count > 0 ) {
			$total += $nextgen_count;
		}
		
		unset( $nextgen_count );
	}
	
	/* Custom folders */
	$cf_restored = $premium_backup->restoreAllCustomFolders( $limit );
	
	if ( isset( $cf_restored['remane'] ) ) {
		$remane_count += $cf_restored['remane'];
	}
	
	$cf_count = RIO_Process_Queue::count_by_type_status( 'cf_image', 'success' );
	
	if ( is_numeric( $cf_count ) && (int) $cf_count > 0 ) {
		
		$total += $cf_count;
	}
	
	unset( $cf_count );
	
	return [
		'remane' => $remane_count,
		'total'  => $total,
	];
} );
