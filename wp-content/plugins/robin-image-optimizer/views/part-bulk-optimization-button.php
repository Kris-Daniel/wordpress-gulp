<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */

$cron_running = WRIO_Plugin::app()->getPopulateOption( 'cron_running', false );

if ( ! $cron_running || $cron_running != $data['scope'] ) {
	$cron_running = false;
}

$button_classes = [
	'wio-optimize-button'
];

$button_name = __( 'Run', 'robin-image-optimizer' );

if ( $cron_running ) {
	$button_classes[] = 'wrio-cron-mode wio-running';
	if ( $cron_running ) {
		$button_name = __( 'Stop shedule optimization', 'robin-image-optimizer' );
	} else {
		$button_name = __( 'Run', 'robin-image-optimizer' );
	}
}

?>
<button type="button" id="wrio-start-optimization" class="<?php echo join( ' ', $button_classes ); ?>">
	<?php echo esc_attr( $button_name ); ?>
</button>