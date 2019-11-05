<?php

/**
 * 
 */
class DC
{

	public function __construct()
	{
		$this->menu = new Builders\Menu;
		$this->pagination = new Builders\Pagination;
	}

	public function get_page($page_slug)
	{
		$args = array(
			'name' => $page_slug,
			'post_type' => 'page'
		);
		return $this->return_post($args);
	}

	public function get_page_id($page_slug)
	{
		return $this->get_page($page_slug)->ID;
	}
	public function get_permalink($page_slug)
	{
		return get_the_permalink($this->get_page_id($page_slug));
	}

	public function get_page_s($page_slug)
	{
		$args = array(
			'post_type' => 'page',
		);
		$result = array();
		$query = new WP_Query($args);
		if ($query->have_posts()) {
			$dc_pages_s = $query->posts;
			foreach ($dc_pages_s as $dc_page_s) {
				$dc_meta = get_post_meta($dc_page_s->ID);
				if ($dc_meta['dc_slug'][0] == $page_slug) {
					$result = $dc_page_s;
				}
			}
			//var_dump($result);
		} else {
			return false;
		}
		return $result;
	}

	public function get_posts($post_name, $taxes = 0, $pagination = 0, $except = 0)
	{
		$args = array(
			'post_type' => $post_name
		);
		if ($taxes != 0) {
			$args['tax_query'] = array('relation' => 'OR');


			foreach ($taxes as $tax => $value) {
				if (is_string($value)) {
					array_push(
						$args['tax_query'],
						array(
							'taxonomy' => $tax,
							'field'    => 'slug',
							'terms'    => $value
						)
					);
				} else if (is_array($value)) {
					foreach ($value as $term) {
						array_push(
							$args['tax_query'],
							array(
								'taxonomy' => $tax,
								'field'    => 'slug',
								'terms'    => $term
							)
						);
					}
				}
			}
		}
		if ($except != 0) {
			$args['post__not_in'] = $except;
		}
		/*if($exit_archive != 0) {
		}*/
		$args['exit_archive'] = true;

		if ($pagination != 0) {
			$args['posts_per_page'] = $pagination;
			$args['paged'] = (get_query_var('paged')) ? get_query_var('paged') : 1;
			global $wp_query;
			$wp_query = new WP_Query($args);
			while (have_posts()) {
				the_post();
			}
			return $wp_query->posts;
		} else {
			$query = new WP_Query($args);
			return $query->posts;
		}
	}

	public function part($name, $part = 0)
	{
		include(locate_template('/template-parts/' . $name . '.php'));
	}

	public function file_url($file)
	{
		return get_template_directory_uri() . '/assets/img/' . $file;
	}
	public function file($file)
	{
		try {
			$fileContent = file_get_contents($this->file_url($file));
			echo $fileContent ? $fileContent : "";
			
		} catch (Exception $e) {}
	}

	public function createPagination()
	{
		$this->pagination->createPagination();
	}

	public function createMenu($scope, $css = false)
	{
		$this->menu->createMenu($scope, $css);
	}

	public function stripContent($content, $len)
	{
		$content = strip_tags($content);
		if (strlen($content) > $len) {
			$shortContent = mb_substr($content, 0, $len, "utf-8") . '...';
			echo ($shortContent);
		} else {
			print_r($content);
		}
	}

	/* Helpfull public functions */

	public function slug_exists($post_name)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		if ($wpdb->get_row("SELECT post_name FROM " . $prefix . "posts WHERE post_name = '" . $post_name . "'", 'ARRAY_A')) {
			return true;
		} else {
			return false;
		}
	}

	public function return_post($args)
	{
		$query = new WP_Query($args);
		if ($query->have_posts()) {
			//wp_reset_postdata();
			return $query->posts[0];
		} else {
			//wp_reset_postdata();
			return false;
		}
	}
}
