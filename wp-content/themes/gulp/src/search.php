<?php get_header(); ?>
<?php the_post(); ?>
<?php global $trans; ?>
<div class="page">
	<?php
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	$search = get_query_var('s');
	$results = new WP_Query(array(
		'post_type' => array('page', 'news'),
    	's' => $search,
        'paged' => $paged,
        'posts_per_page' => 9
	));

	foreach ($results->posts as $p) {
		echo $p->post_title . '<br>';
	}

	dc()->createPagination();
	?>
</div>
<?php get_footer(); ?>