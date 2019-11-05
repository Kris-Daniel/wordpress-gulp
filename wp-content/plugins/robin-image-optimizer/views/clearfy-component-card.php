<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */

if ( ! class_exists( 'WCL_Plugin' ) ) {
	return;
}

$install_button = WCL_Plugin::app()->getInstallComponentsButton( "wordpress", 'robin-image-optimizer/robin-image-optimizer.php' );
$delete_button  = WCL_Plugin::app()->getDeleteComponentsButton( "wordpress", 'robin-image-optimizer/robin-image-optimizer.php' );

$license_page_url = WRIO_Plugin::app()->getPluginPageUrl( 'rio_license' );
?>
<div class="plugin-card">
    <div class="plugin-card-top">
        <div class="name column-name">
            <h3>
                <a href="https://wordpress.org/plugins/robin-image-optimizer/" class="thickbox open-plugin-details-modal">
					<?php _e( 'Robin image optimizer', 'clearfy' ) ?>
                    <img src="<?php echo WCL_PLUGIN_URL ?>/admin/assets/img/rio-icon-128x128.png" class="plugin-icon" alt="">
                </a>
            </h3>
        </div>
        <div class="desc column-description">
            <p><?php _e( 'Automatic image optimization without any quality loss. No limitations, no paid plans. The best Wordpress image optimization plugin allows optimizing any amount of images for free!', 'clearfy' ) ?></p>
        </div>
    </div>
    <div class="plugin-card-bottom" style="text-align: right">
		<?php if ( ! wrio_is_license_activate() ): ?>
            <a href="<?php echo $license_page_url; ?>" class="button">
                <span class="dashicons dashicons-lock" style="font-size: 15px;line-height: 1.7;color: #ff5722;"></span>
				<?php _e( 'License', 'robin-image-optimizer' ) ?>
            </a>
		<?php endif; ?>
		<?php $delete_button->renderButton(); ?><?php $install_button->renderButton(); ?>
    </div>
</div>
