<?php

function dc(){
    global $dc;
    return $dc;
}
/*=== Front-End ===*/

add_action('after_setup_theme', 'dc_theme_support');
add_action('wp_enqueue_scripts', 'dc_reg_scripts');

function dc_theme_support()
{
    add_theme_support('custom-logo');
    add_theme_support('post-thumbnails');
    add_image_size( 'news-photo',  360,  200, array( 'center', 'center' )  );
    add_image_size( 'news-490',    490,  490, array( 'center', 'center' )  );
    add_image_size( 'news-1010',   1010, 400, array( 'center', 'center' )  );

    add_theme_support('title-tag');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    add_theme_support('automatic-feed-links');
    add_theme_support('custom-background');
    add_theme_support('customize-selective-refresh-widgets');

    register_nav_menu( 'header', 'Header Menu' );
}

function dc_reg_scripts()
{
    //css
    $time = time();
    wp_enqueue_style('dc-connect-style', get_stylesheet_directory_uri() . '/style.css');

    //js
    wp_enqueue_script('script-js', get_template_directory_uri()   . '/assets/js/script.js');
}

/*=== Back-end ===*/

add_action('admin_head', 'my_custom_fonts');
add_action('admin_head', 'my_custom_js');

function my_custom_fonts()
{
    echo '<link rel="stylesheet" href="' . get_template_directory_uri() . '/assets/css/admin.css" type="text/css" media="all" />';
}

function my_custom_js()
{
    echo '<script src="' . get_template_directory_uri() . '/assets/js/admin.js"></script>';
}

/*add_action( 'init', 'remove_page_attribute_support' );
function remove_page_attribute_support() {
     if( is_admin() ) {
        if( current_user_can('editor') ) {
            remove_meta_box('pageparentdiv', 'page', 'normal');
        }
    }
}*/


## Добавляем блоки в основную колонку на страницах постов и пост. страниц
/*add_action('add_meta_boxes', 'chosen_post_add_field');*/
function chosen_post_add_field(){
    if(get_the_ID() == dc()->get_page_id('home')) {
        $screens = array( 'post', 'page' );
        add_meta_box( 'postChoser', 'Chose News', 'chosen_post_render_field', $screens );
    }
}

// HTML код блока
function chosen_post_render_field( $post, $meta ){
    $screens = $meta['args'];

    // Используем nonce для верификации
    //wp_nonce_field( plugin_basename(__FILE__), 'myplugin_noncename' );

    // Поля формы для введения данных
    $news = dc()->get_posts('news-and-events');
    $counter = 0;
    $postMeta = get_post_meta(dc()->get_page_id('home'));
    echo '<div class="postChoser_box">';
    foreach($news as $newsItem) {
        $counter++;
        $radioFlag = false;
        if($newsItem->ID == $postMeta['chosen_post'][0]) $radioFlag = true;
        ?>
        <div>
            <input
                id="postChoser-<?php echo $counter; ?>"
                name="postChoser"
                value="<?php echo $newsItem->ID; ?>"
                type="radio"
                <?php if($radioFlag) echo "checked"; ?>
            />
            <label for="postChoser-<?php echo $counter; ?>"><?php echo $newsItem->post_title; ?></label>
        </div>
        <?php
    }
    echo "</div>";
}

## Сохраняем данные, когда пост сохраняется
add_action( 'save_post', 'myplugin_save_postdata' );
function myplugin_save_postdata( $post_id ) {
    // Убедимся что поле установлено.
    if ( ! isset( $_POST['postChoser'] ) )
        return;

    // проверяем nonce нашей страницы, потому что save_post может быть вызван с другого места.
    //if ( ! wp_verify_nonce( $_POST['myplugin_noncename'], plugin_basename(__FILE__) ) )
        //return;

    // если это автосохранение ничего не делаем
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
        return;

    // проверяем права юзера
    if( ! current_user_can( 'edit_post', $post_id ) )
        return;

    // Все ОК. Теперь, нужно найти и сохранить данные
    // Очищаем значение поля input.
    $my_data = sanitize_text_field( $_POST['postChoser'] );

    // Обновляем данные в базе данных.
    update_post_meta( $post_id, 'chosen_post', $my_data );
}

function stripContent($content, $len) {
    $content = strip_tags($content);
    if (strlen($content) > $len) {
        $shortContent = mb_substr($content, 0, $len, "utf-8") . '...';
        echo($shortContent);
    } else {
        print_r($content);
    }
}

add_filter('wp_terms_checklist_args', 'category_to_radio');
function category_to_radio($args)
{
    if (
        !empty($args['taxonomy'])        &&
        $args['taxonomy'] === 'category' ||
        $args['taxonomy'] === 'type'     ||
        $args['taxonomy'] === 'law-content'
    ) {
        if (empty($args['walker']) || is_a($args['walker'], 'Walker')) {
                class CategoryToRadio extends Walker_Category_Checklist
                {
                    function walk($elements, $max_depth, $args = array())
                    {
                        $output = parent::walk($elements, $max_depth, $args);
                        $output = str_replace(
                            array('type="checkbox"', "type='checkbox'"),
                            array('type="radio" required="required"', "type='radio' required='required'"),
                            $output
                        );
                        return $output;
                    }
                }
            $args['walker'] = new CategoryToRadio;
        }
    }
    return $args;
}

//add custom styles to the WordPress editor
function my_custom_styles( $init_array ) {  
 
    $style_formats = array(  
        // These are the custom styles
        array(  
            'title' => 'Gray block',  
            'block' => 'div',  
            'classes' => 'gray-block',
            'wrapper' => true,
        )
    );  
    // Insert the array, JSON ENCODED, into 'style_formats'
    $init_array['style_formats'] = json_encode( $style_formats );  
    
    return $init_array;  
  
} 
// Attach callback to 'tiny_mce_before_init' 
add_filter( 'tiny_mce_before_init', 'my_custom_styles' );
add_editor_style(get_template_directory_uri() . '/assets/css/admin.css');