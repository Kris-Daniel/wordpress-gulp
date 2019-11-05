<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helps to convert log file content into easy-to-read HTML.
 *
 * Usage example:
 *
 * ```php
 * $log_content = WIO_Log_Reader::prettify();
 * ```
 *
 * @see WRIO_Logger for further information about logging.
 * @see WRIO_Logger::get_content() for method which is used to get file content.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class WIO_Log_Reader {

	/**
	 * Prettify log content.
	 *
	 * @see WRIO_Logger::get_content()
	 * @return bool|mixed|string
	 */
	public static function prettify () {
		$content = WRIO_Logger::get_content();


		$search = [
			"\r\n",
			"\n\r",
			"\025",
			"\n",
			"\r",
			"\t",
		];

		$replacement = [
			'<br>',
			'<br>',
			'<br>',
			'<br>',
			'<br>',
			str_repeat( '&nbsp;', 4 ),
		];

		$content = str_replace( $search, $replacement, $content );


		$color_map = [
			WRIO_Logger::LEVEL_INFO    => [ 'color' => '#fff', 'bg' => '#52d130' ],
			WRIO_Logger::LEVEL_ERROR   => [ 'color' => '#fff', 'bg' => '#ff5e5e' ],
			WRIO_Logger::LEVEL_WARNING => [ 'color' => '#fff', 'bg' => '#ef910a' ],
			WRIO_Logger::LEVEL_DEBUG   => [ 'color' => '#fff', 'bg' => '#8f8d8b' ],
		];

		/**
		 * Highlight log levels
		 */
		foreach ( $color_map as $level => $item ) {
			$content = preg_replace( "/\[([\d\w]{6})\]\[($level)\]/",
				"[$1][<span style=\"color: {$item['color']};background-color: {$item['bg']}\">$2</span>]", $content );
		}

		return $content;
	}
}