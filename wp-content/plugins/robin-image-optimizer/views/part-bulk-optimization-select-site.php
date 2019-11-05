<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */

$blogs        = WIO_Multisite::getBlogs( $data['scope'] );
$current_blog = WRIO_Plugin::app()->getPopulateOption( 'current_blog', 1 );

?>
<select style="width:200px;display:inline-block; height: 45px; margin-left:40px;" id="wbcr-rio-current-blog"
        class="factory-dropdown factory-from-control-dropdown form-control"
        data-context="<?php echo esc_attr( $data['scope'] ); ?>"
        data-nonce="<?php echo wp_create_nonce( 'update_blog_id' ); ?>">
	<?php foreach ( (array) $blogs as $blog ) : ?>
		<?php
		$blog_name = $blog->domain . $blog->path;
		if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
			$blog_name = $blog->domain;
		}
		?>
        <option <?php selected( $current_blog, $blog->blog_id ); ?>
                value="<?php echo esc_attr( $blog->blog_id ); ?>"><?php echo esc_attr( $blog_name ); ?></option>
	<?php endforeach; ?>
</select>

