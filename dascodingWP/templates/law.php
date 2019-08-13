<?php
/*
Template Name: Law
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
				<h1 class="caption jo light uppercase"><?php the_title(); ?></h1>
				<div class="mb60"></div>
				<?php
				$lawLinks = dc()->get_posts('law-links', array('law-content' => 'link'));
				foreach ($lawLinks as $lawLink) { ?>
					<a href="<?= get_field('law_url', $lawLink->ID); ?>" class="shadowHover lawLink mb20 db">
						<div class="caption-law caption-lawLink ul-angle green"><?= get_the_title($lawLink->ID); ?></div>
					</a>
				<?php } ?>
				<?php
				$lawContents = dc()->get_posts('law-links', array('law-content' => 'text'));
				foreach ($lawContents as $lawContent) { ?>
					<div class="shadowHover lawText">
						<div class="lawContentWrapper the_content">
							<div class="lawHead">
								<div class="caption-law ul-angle active black"><?= get_the_title($lawContent->ID); ?></div>
								<div class="hr-gray lawHr"></div>
							</div>
							<div class="lawContent the_content justify">
								<?= get_field('law_content', $lawContent->ID); ?>
							</div>
						</div>
					</div>
				<?php } ?>
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