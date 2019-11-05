<?php

namespace Builders;

/**
 * 
 */
class Menu
{
	private $wp_menu;
	private $dc_menu;

	public function __construct()
	{
		
	}
	public function createMenu($scope, $css = false)
	{
		try {
			$css = !$css ? $scope : $css;
			$locations = get_nav_menu_locations();
			$this->dc_menu = array();
			if(isset($locations[ $scope ])) {
				$this->wp_menu = wp_get_nav_menu_items($locations[ $scope ]);
				foreach ($this->wp_menu as $item) {
					$this->itemCheck($item);
				}
				$this->render($css);
			}
		} catch (Exception $e) { die($e); }
	}

	public function itemCheck($item)
	{
		try {
			if(!$item->menu_item_parent) {
				$this->dc_menu[] = $this->menuInstance($item);
			}else {
				$this->findParent($item, $this->dc_menu);
			}
		} catch (Exception $e) { die($e); }
	}

	public function findParent($item, &$menu)
	{
		try {
			$dcMenu = &$menu;
			$len = count($dcMenu);
			for($i = 0; $i < $len; $i++) {
				if($dcMenu[$i]['id'] == $item->menu_item_parent) {
					$dcMenu[$i]['children'][] = $this->menuInstance($item);
					return false;
				} elseif(isset($dcMenu[$i]['children'])) {
					$this->findParent($item, $dcMenu[$i]['children']);
					return false;
				}
			}
		} catch (Exception $e) { die($e); }
	}

	public function menuInstance($item)
	{
		try {
			return array(
				'id' => $item->ID,
				'page_id' => $item->object_id,
				'name' => $item->post_title != '' ? $item->post_title : get_the_title($item->object_id) ,
				'parent' => $item->menu_item_parent
			);
		} catch (Exception $e) { die($e); }
	}

	public function render($css) {
		$this->drawDom($this->dc_menu, 0, $css);
	}

	public function drawDom($dcMenu, $count, $css) {
		try {
			$helpCount = $count;
			if($count == 0) { ?>
				<ul class="<?= $css; ?>_parentMenu">
			<?php
			} else { ?>
				<ul class="<?= $css; ?>_childMenu <?= $css; ?>_childMenu-<?= $count;?>">
			<?php
			}
			foreach ($dcMenu as $item) {
				$childs = isset($item['children']) ? 'has_childs' : 'no_childs';
				$current = get_the_permalink(get_the_ID()) == get_the_permalink($item['page_id']) ? 'current' : '';

				if($count == 0) {
				?>
					<li class="<?= $css; ?>_parentLi <?= $childs; ?> <?= $current; ?>">
						<a href="<?= get_the_permalink($item['page_id']); ?>" class="<?= $css; ?>_parentLink">
							<?= $item['name']; ?>
						</a>
				<?php

				}else { ?>
					<li class="<?= $css; ?>_childLi <?= $css; ?>_childLi-<?= $count;?>">
						<a href="<?= get_the_permalink($item['page_id']); ?>" class="<?= $css; ?>_childLink <?= $css; ?>_childLink-<?= $count;?>">
							<?= $item['name']; ?>
						</a>
				<?php
				}

				if(isset($item['children'])) {
					$this->drawDom($item['children'], ++$helpCount, $css);
				}
				echo '</li>';
			}
			echo '</ul>';

		} catch (Exception $e) { die($e); }
	}
}