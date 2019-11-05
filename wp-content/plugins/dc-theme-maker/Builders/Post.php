<?php

namespace Builders;

/**
 * 
 */
class Post extends \DC
{	
	public function __construct()
	{
		global $jsonParser;
		$this->links = array();
		$this->posts = $jsonParser->getData('posts');
	}

	public function startRegPosts()
	{
		$postsType = gettype($this->posts);
		if($postsType == 'array' || $postsType == 'object')
		{
			foreach ($this->posts as $post) {
				$this->regPost($post);
			}
		}
	}

	private function regPost($post)
	{
		try {
			$labels = array(
				'name' => $post['title'],
				'singular_name' => $post['title'],
			);

			if (isset($post['admin_name']) && $post['admin_name'] != '')
			{
				$labels['name_admin_bar'] = $post['admin_name'];
				$labels['menu_name'] = $post['admin_name'];
			}

			$arguments = array(
				'label' => $post['slug'],
				'labels' => $labels,
				'description' => '',
				'has_archive' => true,
				'public' => true,
				'menu_icon' => $post['icon'],
				'hierarchical' => true,
				'supports' => array('title', 'editor', 'thumbnail'),
				'publicly_queryable' => true
			);
			
			if (isset($post['url'])) {

				if($post['url'] === true)
					$arguments['rewrite'] = array('slug' => $post['slug'], 'with_front' => false);
				else
					$arguments['rewrite'] = array('slug' => $post['url'], 'with_front' => false);

				$this->links[] = $post['slug'];
			}

			if (isset($post['index']) && $post['index'] == false)
				$arguments['publicly_queryable'] = $post['index'];

			if (isset($post['taxes']) && $post['taxes'] != NULL)
				$arguments['taxonomies'] = $post['taxes'];

			register_post_type($post['slug'], $arguments);

			if (isset($post['editor']) && $post['editor'] == false)
				remove_post_type_support($post['slug'], 'editor');

			if (isset($post['hierarchical']) && $post['hierarchical'] == true)
				add_post_type_support($post['slug'], 'page-attributes');

		} catch (Exception $e) { die($e); }
	}

	public function setArchiveAsPage(&$query)
	{
		try {
			$query = &$query;
			foreach ($this->links as $link)
			{
				if ($query->query['post_type'] == $link && is_archive() && !is_admin() && empty($query->query['exit_archive']))
				{
					$query->query_vars['pagename'] = $link;
					$query->query_vars['post_type'] = 'page';
					$query->is_singular = 1;
					$query->is_page = 1;
					$query->queried_object = $this->get_page($link);
					$query->queried_object_id = $this->get_page_id($link);
				}
			}

			if (!empty($query->query['exit_archive']) && !is_admin())
			{
				unset($query->query['exit_archive']);
				unset($query->query_vars['exit_archive']);
			}
		} catch (Exception $e) { die($e); }
	}

	public function setArchiveTemplate(&$template)
	{
		try {
			$template   = &$template;
			$queriedObj = get_queried_object();

			$templateSrc = get_post_meta($queriedObj->ID)['_wp_page_template'][0];
            if($templateSrc != 'default' && $templateSrc != NULL)
                $template = locate_template(array($templateSrc));

			foreach ($this->links as $link)
			{
				$singleName = $queriedObj->post_type;
				$slug = $queriedObj->post_name;
				$page = get_page($queriedObj->ID);

				if (get_post_type() == 'page' && $page->post_name == $link)
				{
					if(file_exists(locate_template(array('templates/' . $link . '.php'))))
						$template = locate_template(array('templates/' . $link . '.php'));
				}
			}

			if(get_post_type() != 'page' && is_singular())
			{
				$singleSrc = 'single/' . $singleName . '-' . $slug . '.php';
				$commonSrc = 'single/' . $singleName . '.php';

				if($templateSrc == NULL || $templateSrc == 'default')
				{
					if(file_exists(locate_template(array($singleSrc))))
						$template = locate_template(array($singleSrc));
					
					else if(file_exists(locate_template(array($commonSrc))))
						$template = locate_template(array($commonSrc));
				}
			}

			if(is_tax())
			{
				$tax = get_queried_object()->taxonomy;
				if(file_exists(locate_template(array('taxonomy/' . $tax . '.php'))))
					$template = locate_template(array('taxonomy/' . $tax . '.php'));
			}

			return $template;
		} catch (Exception $e) { die($e); }
	}

}