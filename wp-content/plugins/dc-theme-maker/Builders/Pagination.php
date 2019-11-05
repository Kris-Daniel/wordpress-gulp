<?php

namespace Builders;

/**
 * 
 */
class Pagination
{
	
	public function __construct()
	{
		add_filter('navigation_markup_template', array($this, 'setPagionationTemplate'));
	}

	public function setPagionationTemplate($template)
    {
        return '
        <nav class="navigation %1$s" role="navigation">
            <div class="nav-links">%3$s</div>
        </nav>    
        ';
    }

    public function createPagination()
    {
    	the_posts_pagination(array(
			'show_all'     => true,
			'end_size'     => 1,
			'mid_size'     => 1,
			'prev_next'    => true,
			'prev_text'    => __('<svg width="40" height="24" viewBox="0 0 40 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<g>
			<path d="M12.3921 1.13432L1.6236 11.6342L12.3921 22.3475" stroke="#202427" stroke-width="2" stroke-linejoin="round"/>
			<line y1="-1" x2="37.5" y2="-1" transform="matrix(-1 0 0 1 39.75 12.3843)" stroke="#202427" stroke-width="2"/>
			</g>
			</svg>
			'),
			'next_text'    => __('<svg width="40" height="24" viewBox="0 0 40 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M27.6059 1.13432L38.3745 11.6342L27.6059 22.3475" stroke="#202427" stroke-width="2" stroke-linejoin="round"/>
			<line x1="0.25" y1="11.3843" x2="37.75" y2="11.3843" stroke="#202427" stroke-width="2"/>
			</svg>
			'),
			'add_args'     => false,
			'add_fragment' => '',
			'screen_reader_text' => false,
		));
		wp_reset_query();
    }
}