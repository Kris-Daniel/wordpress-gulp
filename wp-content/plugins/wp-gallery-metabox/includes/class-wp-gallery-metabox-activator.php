<?php

/**
 * Fired during plugin activation
 *
 * @link       www.kalathiya.me
 * @since      1.0.0
 *
 * @package    Wp_Gallery_Metabox
 * @subpackage Wp_Gallery_Metabox/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Gallery_Metabox
 * @subpackage Wp_Gallery_Metabox/includes
 * @author     hardik kalathiya <hardikkalathiya93@gmail.com>
 */
class Wp_Gallery_Metabox_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        $add_post_default = array('post');
        update_post_meta(1, 'wp_gallery_metabox_allow_post', serialize($add_post_default));
    }

    
}
