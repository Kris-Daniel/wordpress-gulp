<?php

namespace Builders;

//use WP_Query;

class Page extends \DC
{
	
	public function __construct()
	{
		global $jsonParser;
		$this->pages = $jsonParser->getData('pages');
		$this->nonEditorPages = array();
		add_action('admin_head', array($this, 'excludeEditor'));
	}
	
	public function startRegPages()
	{
		$pagesType = gettype($this->pages);
		if($pagesType == 'array' || $pagesType == 'object')
			$this->regPages($this->pages, 0);
	}

	private function regPages($posts, $parent = 0)
	{
		try {
			$parent = $parent == 0 ? 0 : $this->get_page_id($parent['slug']);
			foreach ($posts as $page) {
				$this->insertPage($page, $parent);
				if(isset($page['children'])) {
					$this->regPages($page['children'], $page);
				}
			}
		} catch (Exception $e) { die($e); }
	}

	private function insertPage($post, $parent)
	{
		try {
			$forInsert = wp_slash(
				array(
					'post_name'     => $post['slug'],
					'post_title'    => $post['title'],
					'post_content'  => '',
					'post_type'     => 'page',
					'post_status'   => 'publish',
					'post_parent'   => $parent
				)
			);
			if(!$this->slug_exists($post['slug'])) {
				$post_insert = wp_insert_post($forInsert);
			}else{
				//$post_insert = wp_update_post($forInsert);
			}

			if(isset($post['editor']) && $post['editor'] == false) {
				array_push($this->nonEditorPages, $post['slug']);
			}

			$postId = $this->get_page_id($post['slug']);
			if(isset($post['template'])){
				$templateFile = 'templates/' . $post['template'] .'.php';
				update_post_meta($postId, '_wp_page_template', $templateFile);
			}
			update_post_meta($postId, 'dc_slug', $post['slug'], true);
		} catch (Exception $e) { die($e); }
	}
	
	public function excludeEditor()
    {
        foreach ($this->nonEditorPages as $page) {
            if($this->get_page_id($page) == get_the_ID()) {
                remove_post_type_support('page', 'editor');
            }
        }
    }

}