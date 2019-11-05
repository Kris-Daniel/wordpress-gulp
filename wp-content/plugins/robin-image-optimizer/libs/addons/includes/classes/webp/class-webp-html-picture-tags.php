<?php

namespace WRIO\WEBP\HTML;

/**
 * Class AlterHtmlPicture - convert an <img> tag to a <picture> tag and add the webp versions of the images
 * Based this code on code from the ShortPixel plugin, which used code from Responsify WP plugin
 */

use DOMUtilForWebP\PictureTags;

class Picture_Tags extends PictureTags {

	public function replaceUrl( $url ) {
		return Delivery::get_webp_url( $url, null );
	}
}
