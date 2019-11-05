<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

use WRIO\WEBP\HTML\Delivery as WEBP_Delivery;

/**
 * @var array $data
 */
?>
<div class="form-group">
    <label class="col-sm-4 control-label"></label>
    <div class="control-group col-sm-8">
        <div id="wrio-webp-options">
            <h3><?php _e( 'Select delivery mode', 'robin-image-optimizer' ) ?>:</h3>
            <p class="wrio-webp-options-info">
				<?php printf( __( 'Deliver the WebP versions of the images in the front-end <a href="%s" target="_blank">Read more</a>', 'robin-image-optimizer' ), $data['docs_url'] ) ?>
            </p>
            <ul>
                <li>
                    <label for="wrio-webp-options-radio-redirection"><input type="radio" id="wrio-webp-options-radio-redirection" name="wrio_webp_delivery_mode" class="wrio-webp-options-radio" value="<?php echo WEBP_Delivery::REDIRECT_DELIVERY_MODE ?>"<?php disabled( ! $data['allow_redirection_mode'] ) ?><?php checked( WEBP_Delivery::REDIRECT_DELIVERY_MODE, $data['delivery_mode'] ) ?>>
						<?php _e( 'Redirection (via .htaccess)', 'robin-image-optimizer' ) ?>
                    </label>
                    <p class="wrio-webp-options-info">
						<?php printf( __( 'This will add rules in the .htaccess that redirects directly to existing converted files. Best performance is achieved by redirecting in .htaccess. Based on testing your particular hosting configuration, we determined that your server <img width="30" src="%s"> serve the WEBP versions of the JPEG files seamlessly, via .htaccess.', 'robin-image-optimizer' ), WRIOP_PLUGIN_URL . '/assets/img/test.jpg' ) ?>
                        <br>
						<?php _e( 'Server', 'robin-image-optimizer' ) ?>: <?php
						if ( "apache" === $data['server'] ) {
							echo "<span style='color:green'>" . $data['server'] . "</span>";
						} else {
							echo "<span style='color:red'>" . $data['server'] . " (unsupported)</span>";
						}
						?>
                    </p>
                </li>
                <li>
                    <label for="wrio-webp-options-radio-picture"><input type="radio" id="wrio-webp-options-radio-picture" name="wrio_webp_delivery_mode" class="wrio-webp-options-radio" value="<?php echo WEBP_Delivery::PICTURE_DELIVERY_MODE ?>"<?php checked( WEBP_Delivery::PICTURE_DELIVERY_MODE, $data['delivery_mode'] ) ?>>
						<?php _e( 'Replace &lt;img&gt; tags with &lt;picture&gt; tags, adding the webp to srcset.', 'robin-image-optimizer' ) ?>
                    </label>
                    <p class="wrio-webp-options-info">
						<?php _e( ' Each &lt;img&gt; will be replaced with a &lt;picture&gt; tag that will also provide the WebP image as a choice for browsers that support it. Also loads the picturefill.js for browsers that don\'t support the &lt;picture&gt; tag. You don\'t need to activate this if you\'re using the Cache Enabler plugin because your WebP images are already handled by this plugin. <strong>Please make a test before using this option</strong>, as if the styles that your theme is using rely on the position of your &lt;img&gt; tag, you might experience display problems. <strong>You can revert anytime to the previous state by just deactivating the option.</strong>', 'robin-image-optimizer' ) ?>
                    </p>
                </li>
                <li>
                    <label for="wrio-webp-options-radio-url"><input type="radio" id="wrio-webp-options-radio-url" name="wrio_webp_delivery_mode" class="wrio-webp-options-radio" value="<?php echo WEBP_Delivery::URL_DELIVERY_MODE ?>"<?php checked( WEBP_Delivery::URL_DELIVERY_MODE, $data['delivery_mode'] ) ?>>
						<?php _e( 'Replace image URLs', 'robin-image-optimizer' ) ?>
                    </label>
                    <p class="wrio-webp-options-info">
						<?php _e( '"Image URLs" replaces the image URLs to point to the webp <i>rather than</i> the original. Handles src, srcset, common lazy-load attributes and even inline styles. Note that you will have to do something for the browsers that does not support webp. And that something is in most cases to enable the <i>Only do the replacements in webp enabled browsers</i> option, which will show up when you enable this option.', 'robin-image-optimizer' ) ?>
                    </p>
                </li>
                <li>
                    <label for="wrio-webp-options-radio-none"><input type="radio" id="wrio-webp-options-radio-none" name="wrio_webp_delivery_mode" class="wrio-webp-options-radio" value="<?php echo WEBP_Delivery::DEFAULT_DELIVERY_MODE ?>"<?php checked( WEBP_Delivery::DEFAULT_DELIVERY_MODE, $data['delivery_mode'] ) ?>>
						<?php _e( 'No delivery', 'robin-image-optimizer' ) ?>
                    </label>
                    <p class="wrio-webp-options-info">
						<?php _e( ' Plugin will not replace the source JPEG, PNG image on Webp version of the image in
                        front-end.', 'robin-image-optimizer' ) ?>
                    </p>
                </li>
            </ul>
        </div>
    </div>
</div>
