<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */

$cf             = WRIO_Custom_Folders::get_instance();
$custom_folders = $cf->getFolders();
?>
<div class="wrio-folders-wrapper">
    <div class="wbcr-factory-page-group-header" style="margin:0;">
        <strong><?php _e( 'Additional media folders', 'robin-image-optimizer' ); ?></strong>
        <p><?php _e( 'Use the add folder... button to select site folders. Robin image optimizer will optimize images from the specified folders and their subfolders. The optimization status for each image in these folders can be seen in the Other Media list, under the Media menu.', 'robin-image-optimizer' ) ?></p>
        <a id="wrio-add-new-folder" href="#" class="btn btn-default"><?php _e( 'Add custom folder', 'robin-image-optimizer' ); ?></a>
    </div>
    <div class="wbcr-factory-page-group-body">
        <table class="wrio-table wbcr-rio-folders-table">
            <thead>
            <tr>
                <th style="width: 80px"></th>
                <th style="width: 200px"><?php _e( 'Status', 'robin-image-optimizer' ); ?></th>
                <th style="text-align: left;"><?php _e( 'Folder', 'robin-image-optimizer' ); ?></th>
                <th style="width: 100px"><?php _e( 'Cancel', 'robin-image-optimizer' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php if ( ! empty( $custom_folders ) ): ?>
				<?php foreach ( $custom_folders as $folder ): ?>
                    <tr class="wrio-table-item">
                        <td></td>
                        <td>
                            &nbsp;<?php printf( __( 'Compressed %d of %s<br>images', 'robin-image-optimizer' ), $folder->get( 'optimized_count' ), $folder->get( 'files_count' ) ); ?>
                        </td>
                        <td style="text-align: left">
                            <span class="wrio-table-highlighter"><?php echo esc_attr( $folder->get( 'path' ) ); ?></span>
                        </td>
                        <td data-uid="<?php echo esc_attr( $folder->get( 'uid' ) ); ?>">
                            <!--<button class="wbcr-rio-optimize-folder btn btn-default"><?php _e( 'Optimize', 'robin-image-optimizer' ); ?></button>
                            <button class="wbcr-rio-scan-folder btn btn-default"><?php _e( 'Sync', 'robin-image-optimizer' ); ?></button>
                            <button class="wbcr-rio-restore-folder btn btn-default" data-confirm="<?php _e( 'Recover images from backup?', 'robin-image-optimizer' ); ?>"><?php _e( 'Restore', 'robin-image-optimizer' ); ?></button>-->
                            <button class="wbcr-rio-remove-folder btn btn-default" data-confirm="<?php _e( 'Exclude directory from optimization?', 'robin-image-optimizer' ); ?>">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </td>
                    </tr>
				<?php endforeach; ?>
			<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>