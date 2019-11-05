<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       www.kalathiya.me
 * @since      1.0.0
 *
 * @package    Wp_Gallery_Metabox
 * @subpackage Wp_Gallery_Metabox/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Gallery_Metabox
 * @subpackage Wp_Gallery_Metabox/includes
 * @author     hardik kalathiya <hardikkalathiya93@gmail.com>
 */
class Wp_Gallery_Metabox_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-gallery-metabox',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
