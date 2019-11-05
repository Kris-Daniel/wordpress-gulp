<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WRIO_Webp_Hash_Src holds hash data about type, source src and normalized src which is mainly used to compare
 * or replace data.
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @copyright (c) 22.09.2018, Webcraftic
 * @version 1.0
 */
class WRIO_Url {
	/**
	 * Check whether URI is valid or not.
	 *
	 * @param string $src Image path.
	 * @param bool $decode Whether to decode src.
	 *
	 * @return null|string NULL on failure to get valid uri.
	 */
	public static function normalize ( $src, $decode = true ) {
		$url_parts = wp_parse_url( $src );

		// Unsupported scheme.
		if ( isset( $url_parts['scheme'] ) && 'http' !== $url_parts['scheme'] && 'https' !== $url_parts['scheme'] ) {
			return null;
		}

		// This is a relative path, try to get the URL.
		if ( ! isset( $url_parts['host'] ) && ! isset( $url_parts['scheme'] ) ) {
			$src = site_url( $src );
		}

		if ( false === strpos( $src, content_url() ) ) {
			return null;
		}

		if($decode) {
			return urldecode($src);
		}

		return $src;
	}
}
