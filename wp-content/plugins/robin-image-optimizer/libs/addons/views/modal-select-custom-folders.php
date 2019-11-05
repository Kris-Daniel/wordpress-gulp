<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */
?>
<p><?php _e( 'Select a directory for optimization. All nested images and folders will be optimized recursively.', 'robin-image-optimizer' ) ?></p>
<div id="wrio-file-tree"></div>
<input id="wbcr-rio-selected-path" disabled type="text" style="width:100%;">
<p id="wbcr-rio-indexing-text" style="display:none;"><?php _e( 'The selected directory is being indexed. Found', 'robin-image-optimizer' ); ?>
    <span class="wbcr-rio-indexing-counter">0</span> <?php _e( 'images.', 'robin-image-optimizer' ); ?></p>
<p id="wbcr-rio-indexing-finish-text" style="display:none;"><?php _e( 'Indexing complete. Directory successfully added and ready for optimization.', 'robin-image-optimizer' ); ?></p>

