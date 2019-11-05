<?php

namespace WRIO\WEBP;

/**
 * @author        Webcraftic <wordpress.webraftic@gmail.com>, Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 20.04.2019, Webcraftic
 * @version       1.0
 */
class Server {

	/**
	 * return the server home path
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 */
	public static function get_home_path() {
		$home    = set_url_scheme( get_option( 'home' ), 'http' );
		$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );

		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos                 = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );

			if ( $pos !== false ) {
				$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
				$home_path = trim( $home_path, '/\\' ) . DIRECTORY_SEPARATOR;;
			} else {
				$wp_path_rel_to_home = DIRECTORY_SEPARATOR . trim( $wp_path_rel_to_home, '/\\' ) . DIRECTORY_SEPARATOR;

				$real_apth = realpath( ABSPATH ) . DIRECTORY_SEPARATOR;

				$pos       = strpos( $real_apth, $wp_path_rel_to_home );
				$home_path = substr( $real_apth, 0, $pos );
				$home_path = trim( $home_path, '/\\' ) . DIRECTORY_SEPARATOR;
			}
		} else {
			$home_path = ABSPATH;
		}

		$home_path = trim( $home_path, '\\/ ' );

		//not for windows
		if ( DIRECTORY_SEPARATOR != '\\' ) {
			$home_path = DIRECTORY_SEPARATOR . $home_path;
		}

		return $home_path;
	}

	/**
	 * Return if the server run Apache
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_apache() {
		$is_apache = ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false || strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== false );

		return $is_apache;
	}

	/**
	 * Return if the server run on nginx
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_nginx() {
		$is_nginx = ( strpos( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) !== false );

		return $is_nginx;
	}

	/**
	 * Return if the server run on IIS
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_iss() {
		$is_IIS = ! static::is_apache() && ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) !== false || strpos( $_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer' ) !== false );

		return $is_IIS;
	}

	/**
	 * Return if the server run on IIS version 7 and up
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_iis7() {
		$is_iis7 = static::is_iss() && intval( substr( $_SERVER['SERVER_SOFTWARE'], strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS/' ) + 14 ) ) >= 7;

		return $is_iis7;
	}

	/**
	 * Is permalink enabled?
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 * @global \WP_Rewrite $wp_rewrite
	 */
	public static function is_permalink() {
		global $wp_rewrite;

		if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) || ! $wp_rewrite->using_permalinks() ) {
			return false;
		}

		return true;
	}

	/**
	 * Return whatever the htaccess config file is writable
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function is_writable_htaccess( $htaccess_file ) {
		if ( ( ! file_exists( $htaccess_file ) && static::is_permalink() ) || is_writable( $htaccess_file ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return whatever the web.config config file is writable
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 */
	public static function is_writable_webconfig_file() {
		$home_path       = static::get_home_path();
		$web_config_file = $home_path . 'web.config';

		if ( ( ! file_exists( $web_config_file ) && self::is_permalink() ) || win_is_writable( $web_config_file ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function got_mod_rewrite() {
		if ( self::apache_mod_loaded( 'mod_rewrite', true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Does the specified module exist in the Apache config?
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 *
	 * @param string $mod       The module, e.g. mod_rewrite.
	 * @param bool   $default   Optional. The default return value if the module is not found. Default false.
	 *
	 * @return bool Whether the specified module is loaded.
	 * @global bool  $is_apache
	 *
	 */
	public static function apache_mod_loaded( $mod, $default = false ) {
		if ( ! static::is_apache() ) {
			return false;
		}

		if ( function_exists( 'apache_get_modules' ) ) {
			$mods = apache_get_modules();
			if ( in_array( $mod, $mods ) ) {
				return true;
			}
		} else if ( getenv( 'HTTP_MOD_REWRITE' ) !== false ) {
			$mod_found = getenv( 'HTTP_MOD_REWRITE' ) == 'On' ? true : false;

			return $mod_found;
		} else if ( function_exists( 'phpinfo' ) && false === strpos( ini_get( 'disable_functions' ), 'phpinfo' ) ) {
			ob_start();
			phpinfo( 8 );
			$phpinfo = ob_get_clean();
			if ( false !== strpos( $phpinfo, $mod ) ) {
				return true;
			}
		}

		return $default;
	}

	/**
	 * Return whatever server using the .htaccess config file
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 * @return bool
	 */
	public static function server_use_htaccess() {
		$home_path     = static::get_home_path();
		$htaccess_file = $home_path . DIRECTORY_SEPARATOR . '.htaccess';

		if ( ( ! file_exists( $htaccess_file ) && is_writable( $home_path ) && static::is_permalink() ) || is_writable( $htaccess_file ) ) {
			if ( static::got_mod_rewrite() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cleans webp rules from htaccess file. Use when deactivating a plugin
	 * or turn off webp support option.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.0.4
	 */
	public static function htaccess_clear_webp_rules() {
		$wp_upload_dir                 = wp_upload_dir();
		$root_htaccess_file_path       = static::get_home_path() . DIRECTORY_SEPARATOR . '.htaccess';
		$wp_content_htaccess_file_path = trailingslashit( WP_CONTENT_DIR ) . '.htaccess';

		static::insert_with_markers( $root_htaccess_file_path, '' );

		if ( isset( $wp_upload_dir['error'] ) && $wp_upload_dir['error'] !== false ) {
			\WRIO_Logger::error( 'It is not possible to update webp rules for htaccess, because upload dir is not writable.' );
		} else {

			$upload_base                = $wp_upload_dir['basedir'];
			$uploads_htaccess_file_path = trailingslashit( $upload_base ) . '.htaccess';

			static::insert_with_markers( $uploads_htaccess_file_path, '' );
		}

		static::insert_with_markers( $wp_content_htaccess_file_path, '' );
	}

	/**
	 * Add webp rules in htaccess file. Use when activating a plugin
	 * or turn on webp support option.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  @since  1.0.4
	 *
	 * @param bool $clear
	 */
	public static function htaccess_update_webp_rules() {
		// [BS] Backward compat. 11/03/2019 - remove possible settings from root .htaccess
		$wp_upload_dir                 = wp_upload_dir();
		$root_htaccess_file_path       = static::get_home_path() . DIRECTORY_SEPARATOR . '.htaccess';
		$wp_content_htaccess_file_path = trailingslashit( WP_CONTENT_DIR ) . '.htaccess';

		$rules = '
<IfModule mod_rewrite.c>
  RewriteEngine On

  ##### TRY FIRST the file appended with .webp (ex. test.jpg.webp) #####
  # Does browser explicitly support webp?
  RewriteCond %{HTTP_USER_AGENT} Chrome [OR]
  # OR Is request from Page Speed
  RewriteCond %{HTTP_USER_AGENT} "Google Page Speed Insights" [OR]
  # OR does this browser explicitly support webp
  RewriteCond %{HTTP_ACCEPT} image/webp
  # AND is the request a jpg or png?
  RewriteCond %{REQUEST_URI} ^(.+)\.(?:jpe?g|png)$
  # AND does a .ext.webp image exist?
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.webp -f
  # THEN send the webp image and set the env var webp
  RewriteRule ^(.+)$ $1.webp [NC,T=image/webp,E=webp,L]

  ##### IF NOT, try the file with replaced extension (test.webp) #####
  RewriteCond %{HTTP_USER_AGENT} Chrome [OR]
  RewriteCond %{HTTP_USER_AGENT} "Google Page Speed Insights" [OR]
  RewriteCond %{HTTP_ACCEPT} image/webp
  # AND is the request a jpg or png? (also grab the basepath %1 to match in the next rule)
  RewriteCond %{REQUEST_URI} ^(.+)\.(?:jpe?g|png)$
  # AND does a .ext.webp image exist?
  RewriteCond %{DOCUMENT_ROOT}/%1.webp -f
  # THEN send the webp image and set the env var webp
  RewriteRule (.+)\.(?:jpe?g|png)$ $1.webp [NC,T=image/webp,E=webp,L]

</IfModule>
<IfModule mod_headers.c>
  # If REDIRECT_webp env var exists, append Accept to the Vary header
  Header append Vary Accept env=REDIRECT_webp
</IfModule>

<IfModule mod_mime.c>
  AddType image/webp .webp
</IfModule>
        ';

		static::insert_with_markers( $root_htaccess_file_path, $rules );

		if ( isset( $wp_upload_dir['error'] ) && $wp_upload_dir['error'] !== false ) {
			\WRIO_Logger::error( 'It is not possible to update webp rules for htaccess, because upload dir is not writable.' );
		} else {

			$upload_base                = $wp_upload_dir['basedir'];
			$uploads_htaccess_file_path = trailingslashit( $upload_base ) . '.htaccess';

			static::insert_with_markers( $uploads_htaccess_file_path, $rules );
		}

		static::insert_with_markers( $wp_content_htaccess_file_path, $rules );
	}

	public static function insert_with_markers( $file_path, $content ) {
		if ( ! static::is_writable_htaccess( $file_path ) ) {
			\WRIO_Logger::error( sprintf( "It is not possible to update webp rules for htaccess, because file (%s) is not writable.", $file_path ) );
		} else {
			if ( ! static::got_mod_rewrite() ) {
				\WRIO_Logger::error( "It isn't possible to update webp rules for htaccess, because mode rewrite is unsupported." );

				return;
			}

			if ( ! insert_with_markers( $file_path, 'Robin Image Optimizer Webp', $content ) ) {
				\WRIO_Logger::error( 'Failed write webp rules to htaccess file (%s)' );
			}
		}
	}
}