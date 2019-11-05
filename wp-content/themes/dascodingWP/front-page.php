<?php get_header(); ?>
<?php the_post(); ?>
<?php $postName = get_page(get_the_ID())->post_name; ?>
<div class="page <?= $postName; ?>">
	<div class="box">
		<h2><?php the_title(); ?></h2>
		<div class="the_content">
			<?php the_content(); ?>
		</div>
	</div>
</div>
<?php get_footer(); ?>