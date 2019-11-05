<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       www.kalathiya.me
 * @since      1.0.0
 *
 * @package    Wp_Gallery_Metabox
 * @subpackage Wp_Gallery_Metabox/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Gallery_Metabox
 * @subpackage Wp_Gallery_Metabox/admin
 * @author     hardik kalathiya <hardikkalathiya93@gmail.com>
 */
class Wp_Gallery_Metabox_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', array($this, 'add_gallery_metabox_setting_menu'));
        add_shortcode('gallery_metabox', array($this, 'gallery_metabox'));
        add_action('add_meta_boxes', array($this, 'add_gallery_metabox_to_posts'));
        add_action('save_post', array($this, 'gallery_meta_save'));
    }

    public function add_gallery_metabox_setting_menu() {
        add_options_page('My Options', 'WP gallery metabox', 'manage_options', 'gallery_metabox', array($this, 'gallery_metabox'), 'gallery_metabox');
    }

    public function add_gallery_metabox_to_posts($post_type) {
        $custom_post_data = get_post_meta(1, 'wp_gallery_metabox_allow_post', true);
        $posts_from_db = unserialize($custom_post_data);

        if (in_array($post_type, $posts_from_db)) {
            add_meta_box(
                    'gallery-metabox', 'Gallery', array($this, 'gallery_meta_callback'), $post_type, 'normal', 'high'
            );
        }
    }

    function gallery_meta_callback($post) {
        wp_nonce_field(basename(__FILE__), 'gallery_meta_nonce');
        $ids = get_post_meta($post->ID, 'vdw_gallery_id', true);
        ?>
        <table class="form-table">
            <tr>
                <td>
                    <a class="gallery-add button" href="#" data-uploader-title="Add image(s) to gallery" data-uploader-button-text="Add image(s)">Add image(s)</a>
                    <ul id="gallery-metabox-list">
                        <?php if ($ids) : foreach ($ids as $key => $value) : $image = wp_get_attachment_image_src($value); ?>
                                <li>
                                    <input type="hidden" name="vdw_gallery_id[<?php echo $key; ?>]" value="<?php echo $value; ?>">
                                    <img class="image-preview" src="<?php echo $image[0]; ?>">
                                    <a class="change-image button button-small" href="#" data-uploader-title="Change image" data-uploader-button-text="Change image">Change image</a><br>
                                    <small><a class="remove-image" href="#">Remove image</a></small>
                                </li>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    </ul>
                </td>
            </tr>
        </table>
        <?php
    }

    function gallery_meta_save($post_id) {
        if (!isset($_POST['gallery_meta_nonce']) || !wp_verify_nonce($_POST['gallery_meta_nonce'], basename(__FILE__)))
            return;

        if (!current_user_can('edit_post', $post_id))
            return;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (isset($_POST['vdw_gallery_id'])) {
            update_post_meta($post_id, 'vdw_gallery_id', $_POST['vdw_gallery_id']);
        } else {
            delete_post_meta($post_id, 'vdw_gallery_id');
        }
    }

    public function gallery_metabox() {
        if (isset($_POST['save_gal_box'])) {
            $wp_gallery_post = serialize($_POST['wp_gallery_posts']);
            update_post_meta(1, 'wp_gallery_metabox_allow_post', $wp_gallery_post);
        }

        $custom_post_data = get_post_meta(1, 'wp_gallery_metabox_allow_post', true);
        if (!empty($custom_post_data)) {
            $posts_from_db = unserialize($custom_post_data);
        }

        $checked = '';
        $checked_post = '';
        $post_types = get_post_types(array('_builtin' => FALSE), 'objects');

        if (!empty($posts_from_db)) {
            if (in_array('post', $posts_from_db)) {
                $checked_post = 'checked';
            } else {
                $checked_post = '';
            }
        }
        ?>
        <div class="main-wrap">
            <h1>Select post type to add gallery metabox</h1>
            <div class="outer-gallery-box">
                <form method="POST" class="gallery_meta_form" id="gallery_meta_form_id">
                    <label class="wp_gallery_container">Post
                        <input  class="styled-checkbox" <?php echo $checked_post; ?> id="post" name="wp_gallery_posts[]" type="checkbox" value="post">
                        <span class="checkmark"></span>
                    </label>
                    <?php
                    foreach ($post_types as $post_type => $properties) {
                        if (!empty($posts_from_db)) {
                            if (in_array($properties->name, $posts_from_db)) {
                                $checked = 'checked';
                            } else {
                                $checked = '';
                            }
                        }
                        ?>
                        <label class="wp_gallery_container"><?php echo $properties->labels->name; ?>
                            <input  class="styled-checkbox" <?php echo $checked; ?> id="<?php echo $properties->name; ?>" name="wp_gallery_posts[]" type="checkbox" value="<?php echo $properties->name; ?>">
                            <span class="checkmark"></span>
                        </label>
                        <?php
                    }
                    ?>
                    <div class="save_btn_wrapper">
                        <input type="submit" name="save_gal_box" id="save_post_gallery_box_id" class="save_post_gallery_box_cls" value="Save"> 
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wp_Gallery_Metabox_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wp_Gallery_Metabox_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-gallery-metabox-admin.css', array(), $this->version, 'all');
        wp_enqueue_style('custm_wp_gallery_metabox', plugin_dir_url(__FILE__) . 'css/custm_wp_gallery_metabox.css', '', time());
        wp_enqueue_style('gallery-metabox_cstm_css', plugin_dir_url(__FILE__) . 'css/gallery-metabox.css', '', time());
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wp_Gallery_Metabox_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wp_Gallery_Metabox_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-gallery-metabox-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script('gallery_metabox_cstm_js', plugin_dir_url(__FILE__) . 'js/gallery-metabox.js', '', time());
    }

}
