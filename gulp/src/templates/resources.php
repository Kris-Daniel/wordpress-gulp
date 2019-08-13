<?php
/*
Template Name: Resources
*/
?>
<?php get_header(); ?>
<?php dc()->part('header-img', 'assets/img/bg/femida3.jpg'); ?>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>

		<section class="f0 mb60">
			<div class="xs-12">
				<h1 class="caption">
					<?php the_title(); ?>
				</h1>
			</div>
		</section>

		<section class="flex-grid">
			<?php
			$resources = get_field('sequence', dc()->get_page_id('resources'));
			foreach ($resources as $res) {
				$post_slug = get_post_field( 'post_name', get_post($res->ID) );
				if($post_slug == 'support-official-organisations')
					$title = 'Support to Official Organisations';
				else
					$title = get_the_title($res->ID);
			?>
			<div class="xs-6 s-12 mb20">
				<a href="<?= get_the_permalink($res->ID); ?>" class="newres shadowHover rel db black">
				<div class="f0 y-center">
					<div class="newres_icon">
						<?= file_get_contents(get_field('icon', $res->ID)); ?>
					</div>
					<div class="newres_text">
						<div class="caption-mid mb10"><?= $title; ?></div>
						<div class="text lineh-big"><?= get_field('post_intro', $res->ID); ?></div>
					</div>
				</div>
				</a>
			</div>
			<?php } ?>
		</section>

	</div>
	</div>
</div>
<?php get_footer(); ?>