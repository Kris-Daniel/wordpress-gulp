<?php

namespace WRIO\WEBP\HTML;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WRIO\WEBP\HTML\Delivery converts and replace JPEG & PNG images within HTML doc.
 *
 * Images converted via third-party service, saved locally and then replaced based on parsed DOM <img>, or other elements.
 *
 * @author        Webcraftic <wordpress.webraftic@gmail.com>
 * @author        Alexander Teshabaev <sasha.tesh@gmail.com>
 * @link          https://css-tricks.com/using-webp-images/
 * @link          https://dev.opera.com/articles/responsive-images/#different-image-types-use-case
 * @link          https://ru.wordpress.org/plugins/webp-express/
 * @link          https://github.com/rosell-dk/
 * @version       1.0
 */
class Delivery {

	const DEFAULT_DELIVERY_MODE = 'none';
	const PICTURE_DELIVERY_MODE = 'picture';
	const URL_DELIVERY_MODE = 'url';
	const REDIRECT_DELIVERY_MODE = 'redirect';

	/**
	 * WRIO_Webp constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initiate the class.
	 */
	public function init() {
		if ( static::is_webp_enabled() ) {

			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::info( sprintf( "WebP option enabled and browser \"%s\" is supported, ready to process buffer", isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '*undefined*' ) );
				\WRIO_Logger::info( sprintf( "WebP delivery mode: %s", static::get_delivery_mode() ) );
			}

			//if ( ! is_admin() && $this->is_supported_browser() ) {
			//add_action( 'template_redirect', [ $this, 'process_buffer' ], 1 );
			//}

			//if (get_option('webp-express-alter-html-hooks', 'ob') == 'ob') {
			/* TODO:
			   Which hook should we use, and should we make it optional?
			   - Cache enabler uses 'template_redirect'
			   - ShortPixes uses 'init'

			   We go with template_redirect now, because it is the "innermost".
			   This lowers the risk of problems with plugins used rewriting URLs to point to CDN.
			   (We need to process the output *before* the other plugin has rewritten the URLs,
				if the "Only for webps that exists" feature is enabled)
			*/

			if ( ! static::is_default_delivery_mode() || ! static::is_redirect_delivery_mode() ) {
				add_action( 'init', [ $this, 'process_buffer' ], 1 );
			}

			if ( static::is_picture_delivery_mode() ) {
				add_action( 'wp_head', [ $this, 'add_picture_fill_js' ] );
			}

			//} else {
			//add_filter( 'the_content', 'webPExpressAlterHtml', 10000 ); // priority big, so it will be executed last
			//add_filter( 'the_excerpt', 'webPExpressAlterHtml', 10000 );
			//add_filter( 'post_thumbnail_html', 'webPExpressAlterHtml');
			//}
		}
	}

	/**
	 * Check whether WebP options enabled or not.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 *
	 * @return bool
	 */
	public static function is_webp_enabled() {
		return (bool) \WRIO_Plugin::app()->getPopulateOption( 'convert_webp_format' );
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_redirect_delivery_mode() {
		return self::REDIRECT_DELIVERY_MODE === static::get_delivery_mode();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_picture_delivery_mode() {
		return self::PICTURE_DELIVERY_MODE === static::get_delivery_mode();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_url_delivery_mode() {
		return self::URL_DELIVERY_MODE === static::get_delivery_mode();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_default_delivery_mode() {
		return self::DEFAULT_DELIVERY_MODE === static::get_delivery_mode();
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return string
	 */
	public static function get_delivery_mode() {
		$delivery_mode = \WRIO_Plugin::app()->getPopulateOption( 'webp_delivery_mode' );

		if ( ! empty( $delivery_mode ) ) {
			return $delivery_mode;
		}

		return self::DEFAULT_DELIVERY_MODE;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 */
	public function add_picture_fill_js() {
		// Don't do anything with the RSS feed.
		// - and no need for PictureJs in the admin
		if ( is_feed() || is_admin() ) {
			return;
		}

		echo '<script>' . 'document.createElement( "picture" );' . 'if(!window.HTMLPictureElement && document.addEventListener) {' . 'window.addEventListener("DOMContentLoaded", function() {' . 'var s = document.createElement("script");' . 's.src = "' . WRIOP_PLUGIN_URL . '/assets/js/picturefill.min.js' . '";' . 'document.body.appendChild(s);' . '});' . '}' . '</script>';
	}

	/**
	 * Process HTML template buffer.
	 */
	public function process_buffer() {
		if ( ! is_admin() || ( function_exists( "wp_doing_ajax" ) && wp_doing_ajax() ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			ob_start( [ $this, 'process_alter_html' ] );
		}
	}

	/**
	 * Process tags to replace those elements which match converted to WebP within buffer.
	 *
	 * @param string $content   HTML buffer.
	 *
	 * @return string
	 */
	public function process_alter_html( $content ) {
		$raw_content = $content;

		// Don't do anything with the RSS feed.
		if ( is_feed() || empty( $content ) ) {
			//WRIO_Logger::info( "Buffer content is empty, skipping processing" );
			return $content;
		}

		if ( static::is_picture_delivery_mode() ) {
			if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
				//for AMP pages the <picture> tag is not allowed
				return $content;
			}

			require_once WRIOP_PLUGIN_DIR . '/includes/classes/webp/class-webp-html-picture-tags.php';
			$content = Picture_Tags::replace( $content );
		} else if ( static::is_url_delivery_mode() ) {
			require_once( WRIOP_PLUGIN_DIR . '/includes/classes/webp/class-webp-html-image-urls-replacer.php' );
			$content = Urls_Replacer::replace( $content );
		}

		// If the search and replacement are completed with an error, then return the raw content.
		// If this is not prevented, in case of an error the user will receive a white screen.
		if ( empty( $content ) ) {
			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::warning( sprintf( "Failed search and replace urls. Empty result received (%s).", base64_encode( $content ) ) );
			}

			return $raw_content;
		}

		return $content;
	}

	/**
	 *  Get url for webp
	 *  returns second argument if no webp
	 *
	 * @param string $source_url   (ie http://example.com/wp-content/image.jpg)
	 * @param string $return_value_on_fail
	 *
	 * @return string
	 */
	public static function get_webp_url( $source_url, $return_value_on_fail ) {

		// Currently we do not handle relative urls - so we skip
		if ( ! preg_match( '#^https?://#', $source_url ) ) {
			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::warning( sprintf( "Failed getting webp url. Invalid image url\r\nSource url: %s", $source_url ) );
			}

			return $return_value_on_fail;
		}

		if ( ! preg_match( '#(jpe?g|png)$#', $source_url ) ) {
			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::warning( sprintf( "Failed getting webp url. Unsupported image format\r\nSource url: %s", $source_url ) );
			}

			return $return_value_on_fail;
		}

		// If the image is stored on a remote server, need to skip it
		if ( strpos( $source_url, get_site_url() ) === false ) {
			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::warning( sprintf( "Failed getting webp url. Image is on a remote server\r\nSource url: %s", $source_url ) );
			}

			return $return_value_on_fail;
		}

		if ( static::is_url_delivery_mode() && ! static::is_supported_browser() ) {
			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::warning( sprintf( "Failed getting webp url. Browser does not support webp images\r\nBrowser: %s", $_SERVER['HTTP_USER_AGENT'] ) );
			}

			return $return_value_on_fail;
		}

		$relative_file_path = wrio_convert_url_to_abs_path( $source_url );

		// If you could not find original image, skip it. Perhaps an error
		// in absolute path formation to the directory where the
		// image is stored.
		if ( empty( $relative_file_path ) || ! file_exists( $relative_file_path ) ) {
			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::warning( sprintf( "Failed getting webp url. Unable to find origin image\r\nRelative path: (%s)\r\nSource url: (%s)", $relative_file_path, $source_url ) );
			}

			return $return_value_on_fail;
		}

		$webp_file_url  = $source_url . '.webp';
		$webp_file_path = $relative_file_path . '.webp';

		if ( ! file_exists( $webp_file_path ) ) {
			if ( \WRIO_Logger::is_keep_error_log_on_frontend() ) {
				\WRIO_Logger::warning( sprintf( "Failed getting webp url. Webp image is not found\r\nSource url: %s\r\nWebP url: %s\r\nWebP path: %s", $source_url, $webp_file_url, $webp_file_path ) );
			}

			return $return_value_on_fail;
		}

		return $webp_file_url;
	}

	/**
	 * Check whether browser supports WebP or not.
	 *
	 * @return bool
	 */
	public static function is_supported_browser() {
		if ( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false || isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'], ' Chrome/' ) !== false ) {
			return true;
		}

		return false;
	}
}
