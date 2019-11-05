<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */

$tab_target  = ! $data['is_premium'] ? ' target="_blank"' : '';
$tab_classes = ! $data['is_premium'] ? ' wrio-statistic-tab-premium-label' : '';

$purchase_url   = WRIO_Plugin::app()->get_support()->get_pricing_url( true, 'statistic' );
$media_page_url = $page->getBaseUrl( 'rio_general' );
$cf_page_url    = ! $data['is_premium'] ? $purchase_url : $page->getBaseUrl( 'io_folders_statistic' );
$ngg_page_url   = ! $data['is_premium'] ? $purchase_url : $page->getBaseUrl( 'io_nextgen_gallery_statistic' );
?>
<div class="wrio-statistic-nav">
    <ul>
        <li style="z-index: 9;"<?php echo( $page->id == 'rio_general' ? ' class="active"' : '' ) ?>>
            <a class="wrio-statistic-tab wio-media-library-tab" href="<?php echo $media_page_url; ?>">
                <span class="dashicons dashicons-admin-media"></span>
				<?php _e( 'Media library', 'robin-image-optimizer' ); ?>
                <span class="wrio-statistic-tab-percent">
                     <?php echo apply_filters( 'wbcr/rio/optimize_template/optimized_percent', 0, 'media-library' ); ?>%
                 </span>
            </a>
        </li>
        <li style="z-index: 8;"<?php echo( $page->id == 'io_folders_statistic' ? ' class="active"' : '' ) ?>>
            <a class="wrio-statistic-tab wio-custom-folders-tab<?php echo $tab_classes ?>" href="<?php echo $cf_page_url ?>"<?php echo $tab_target ?>>
                <span class="dashicons dashicons-portfolio"></span>
				<?php _e( 'Custom folders', 'robin-image-optimizer' ); ?>
                <span class="wrio-statistic-tab-percent">
                      <?php echo apply_filters( 'wbcr/rio/optimize_template/optimized_percent', 0, 'custom-folders' ); ?>%
                </span>
            </a>
        </li>
		<?php if ( wrio_is_active_nextgen_gallery() ): ?>
            <li style="z-index: 7;"<?php echo( $page->id == 'io_nextgen_gallery_statistic' ? ' class="active"' : '' ) ?>>
                <a class="wrio-statistic-tab wio-nextgen-gallery-tab<?php echo $tab_classes ?>" href="<?php echo $ngg_page_url ?>"<?php echo $tab_target ?>>
                    <span class="dashicons dashicons-images-alt2"></span>
					<?php _e( 'Nextgen gallery', 'robin-image-optimizer' ); ?>
                    <span class="wrio-statistic-tab-percent">
                       <?php echo apply_filters( 'wbcr/rio/optimize_template/optimized_percent', 0, 'nextgen' ); ?>%
                    </span>
                </a>
            </li>
		<?php endif; ?>
    </ul>
</div>

