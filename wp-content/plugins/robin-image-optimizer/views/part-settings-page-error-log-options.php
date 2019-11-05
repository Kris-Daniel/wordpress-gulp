<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array $data
 */
?>
<div class="form-group">
    <label class="col-sm-4 control-label"></label>
    <div class="control-group col-sm-8">
        <div id="wrio-error-log-options">
            <p class="wrio-error-log-options-info">
				<?php _e( 'Additional logging levels. Enable these options only when the plugin support service asks for it. If you use logging always, it can slow down your site.', 'robin-image-optimizer' ) ?>
            </p>
            <ul>
                <li>
                    <label for="wrio-error-log-options-checkbox-frontend"><input type="checkbox" id="wrio-error-log-options-checkbox-frontend" name="wrio_keep_error_log_on_frontend" class="wrio-error-log-options-checkbox" value="1"<?php checked( $data['keep_error_log_on_frontend'] ) ?>>
						<?php _e( 'Keep an error log on frontend', 'robin-image-optimizer' ) ?>
                    </label>
                    <p class="wrio-webp-options-info">
						<?php _e( 'Option enables error logging on frontend. If for some reason webp images are not displayed on the front-end, you can use this option to catch errors and send this report to the plugin support service.', 'robin-image-optimizer' ) ?>
                    </p>
                </li>
            </ul>
        </div>
    </div>
</div>