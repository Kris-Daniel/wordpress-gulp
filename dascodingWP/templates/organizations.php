<?php
/*
Template Name: Organizations
*/
?>
<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/femida3.jpg'); ?>

<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>

		<section class="flex-grid">
			<div class="lg-8 sm-9">
				<h1 class="caption jo light uppercase mb20"><?php the_title(); ?></h1>
				<div class="text"><?php the_content(); ?></div>
				<div class="mb60"></div>
				<div class="box-f">
				<div class="flex-grid organs">
					<?php
					$organizations = get_field('organizations_sequence', dc()->get_page_id('organizations'));
					foreach ($organizations as $organ) {
					?>
						<div class="xs-6 s-12 organ_grid">
						<div class="organ shadowHover p10 flex-grid jo">
							<div class="organ_grid-img">
								<div class="organ_img">
									<img src="<?= get_the_post_thumbnail_url($organ->ID); ?>" alt="">
								</div>
							</div>
							<div class="organ_grid-text">
								<div class="organ_text-box">
									<div class="organ_title"><?= get_the_title($organ->ID); ?></div>
									<div class="mb20"></div>
									<a href="<?= get_field('organizations_link', $organ->ID); ?>" class="organ_link">View Website</a>
								</div>
							</div>
						</div>
						</div>
					<?php
					}
					?>
				</div>
				</div>
			</div>
			<div class="lg-1 xs-0 s-0"></div>
			<div class="sm-3">
				<?php dc()->part('sidebar', array('ux-side', 'twitter')); ?>
			</div>
		</section>


	</div>
	</div>
</div>
<?php get_footer(); ?>