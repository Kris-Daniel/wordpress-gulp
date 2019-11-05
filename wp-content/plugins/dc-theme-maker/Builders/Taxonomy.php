<?php

namespace Builders;

/**
 * 
 */
class Taxonomy
{
	
	public function __construct()
	{
		global $jsonParser;
		$this->taxes = $jsonParser->getData('taxes');
		$this->templates = array();
		$this->radioTaxes = array();
		add_filter('wp_terms_checklist_args', array($this, 'taxesToRadio'));
	}

	public function startRegTaxes()
	{
		$taxesType = gettype($this->taxes);
		if($taxesType == 'array' || $taxesType == 'object')
		{
			foreach ($this->taxes as $tax) {
				$this->templates[] = $tax['slug'];
				foreach ($tax['scope'] as $scope) {
					$this->registerTaxonomy($tax, $scope);
				}
			}
		}
	}

	private function registerTaxonomy($tax, $scope = 0)
	{
		try {
			$tax_for_insert = array(
				'label' => '',
				'labels' => array(
					'name' => $tax['slug'],
					'singular_name' => $tax['title'],
					'menu_name' => $tax['title']

				),
				'hierarchical' => true,
				'public' => true,
				'singular_label' => $tax['title'],
				'show_admin_column' => true,
				'rewrite' => array('hierarchical' => true)
			);
			if(isset($tax['edit']) && $tax['edit'] == false) {
				$tax_for_insert['capabilities'] = array(
					'assign_terms' => 'manage_options',
					'edit_terms' => 'god',
					'manage_terms' => 'god',
				);
			}
			if(isset($tax['radio']) && $tax['radio'] != false) {
				$this->radioTaxes[] = $tax['slug'];
			}
			register_taxonomy(
				$tax['slug'],
				$tax['scope'],
				$tax_for_insert
			);

			if(isset($tax['terms']))
				$this->insertTerms($tax['slug'], $tax['terms']);

		} catch (Exception $e) { die($e); }
	}

	private function insertTerms($taxSlug, $children, $parent = 0)
	{
		try {

			foreach ($children as $term) {
				if (term_exists($term['slug'], $taxSlug) == NULL) {
					$insertTerm = wp_insert_term(
						$term['slug'],
						$taxSlug,
						array(
							'description' => 'Some descr',
							'slug' => $term['slug'],
							'parent' => $parent
						)
					);
				}
				if(isset($term['children'])) {
					$currentTerm = get_term_by('slug', $term['slug'], $taxSlug);
					$this->insertTerms($taxSlug, $term['children'], $currentTerm->term_id);
				}
			}
		} catch (Exception $e) { die($e); }
	}

	private function insertChildTerms($parent = 0, $terms, $tax)
	{

	}

	public function taxesToRadio($args)
	{
		try {
			if (!empty($args['taxonomy']) && $this->forEachIf($args['taxonomy'])) {
		        if (empty($args['walker']) || is_a($args['walker'], 'Walker'))
		        {
		            $args['walker'] = new CategoryToRadio;
		        }
			}
		    return $args;
		} catch (Exception $e) { die($e); }
	}
	private function forEachIf($tax)
	{
		foreach ($this->radioTaxes as $radioTax) {
			if($tax === $radioTax) return true;
		}
		return false;
	}

}

require_once( ABSPATH . 'wp-admin/includes/class-walker-category-checklist.php' );
namespace Builders;

/**
 * 
 */
class CategoryToRadio extends \Walker_Category_Checklist
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