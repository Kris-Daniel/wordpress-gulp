<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */
?>
<div class="wio-columns wio-page-statistic">
    <div>
        <div class="wio-chart-container wio-overview-chart-container">
            <canvas id="wio-main-chart" width="180" height="180"
                    data-unoptimized="<?php echo esc_attr( $data['stats']['unoptimized'] ); ?>"
                    data-optimized="<?php echo esc_attr( $data['stats']['optimized'] ); ?>"
                    data-errors="<?php echo esc_attr( $data['stats']['error'] ); ?>"
                    style="display: block;">
            </canvas>
            <div id="wio-overview-chart-percent"
                 class="wio-chart-percent"><?php echo esc_attr( $data['stats']['optimized_percent'] ); ?>
                <span>%</span>
            </div>
            <p class="wio-global-optim-phrase wio-clear">
				<?php _e( 'You optimized', 'robin-image-optimizer' ); ?>
                <span class="wio-total-percent">
                        <?php echo esc_attr( $data['stats']['optimized_percent'] ); ?>%
                    </span>
				<?php _e( "of your website's images", 'robin-image-optimizer' ); ?>
            </p>
        </div>
        <div style="margin-left:200px;">
            <div id="wio-overview-chart-legend">
                <ul class="wio-doughnut-legend">
                    <li>
                        <span style="background-color:#d6d6d6"></span>
						<?php _e( 'Unoptimized', 'robin-image-optimizer' ); ?>-
                        <span class="wio-num" id="wio-unoptimized-num">
                                <?php echo esc_attr( $data['stats']['unoptimized'] ); ?>
                            </span>
                    </li>
                    <li>
                        <span style="background-color:#8bc34a"></span>
						<?php _e( 'Optimized', 'robin-image-optimizer' ); ?>-
                        <span class="wio-num" id="wio-optimized-num">
                                 <?php echo esc_attr( $data['stats']['optimized'] ); ?>
                            </span>
                    </li>
                    <li>
                        <span style="background-color:#f1b1b6"></span>
						<?php _e( 'Error', 'robin-image-optimizer' ); ?>-
                        <span class="wio-num" id="wio-error-num">
                                 <?php echo esc_attr( $data['stats']['error'] ); ?>
                            </span>
                    </li>
                </ul>
            </div>
            <h3 class="screen-reader-text"><?php _e( 'Statistics', 'robin-image-optimizer' ); ?></h3>
            <div class="wio-bars" style="width: 90%">
                <p><?php _e( 'Original size', 'robin-image-optimizer' ); ?></p>
                <div class="wio-bar-negative base-transparent wio-right-outside-number">
                    <div id="wio-original-bar" class="wio-progress" style="width: 100%">
                             <span class="wio-barnb" id="wio-original-size">
                                 <?php echo esc_attr( wrio_convert_bytes( $data['stats']['original_size'] ) ); ?>
                             </span>
                    </div>
                </div>
                <p><?php _e( 'Optimized size', 'robin-image-optimizer' ); ?></p>
                <div class="wio-bar-primary base-transparent wio-right-outside-number">
                    <div id="wio-optimized-bar" class="wio-progress"
                         style="width: <?php echo ( $data['stats']['percent_line'] ) ? esc_attr( $data['stats']['percent_line'] ) : 100; ?>%">
                        <span class="wio-barnb" id="wio-optimized-size">
                            <?php echo esc_attr( wrio_convert_bytes( $data['stats']['optimized_size'] ) ); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="wio-number-you-optimized">
                <p>
                    <span id="wio-total-optimized-attachments-pct" class="wio-number">
                        <?php echo esc_attr( $data['stats']['save_size_percent'] ); ?>%
                    </span>
                    <span class="wio-text">
						<?php _e( "that's the size you saved <br>by using Image Optimizer", 'robin-image-optimizer' ); ?>
					</span>
                </p>
            </div>
        </div>
    </div>
    <div class="wrio-statistic-buttons-wrap">
		<?php $this->print_template( 'part-bulk-optimization-button', $data, $page ); ?>
		<?php
		/*if ( WRIO_Plugin::app()->isNetworkAdmin() ) {
			$this->print_template( 'part-bulk-optimization-select-site', $data, $page );
		}*/
		?>
    </div>
</div>
