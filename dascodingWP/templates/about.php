<?php
/*
Template Name: About
*/
?>
<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/femida3.jpg'); ?>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>

		<section class="f0">
			<div class="xs-12">
				<h1 class="caption mb60">
					<span class="text-underline green">About</span>
				</h1>
				<div class="the_content mb80 lineh-big justify">
					<?php the_content(); ?>
				</div>
			</div>
		</section>

		<section class="flex-grid">
			<div class="lg-5 sm-6 relative">
				<div class="people_gray-bg"></div>
				<a href="http://www.globalrightscompliance.com/" class="people_logo mv60">
					<img src="<?php echo dc()->file_url('assets/img/company/grc-gray.png'); ?>" alt="">
				</a>
				<?php
					$grc = dc()->get_posts('about', array('organizations' => 'grc'));
					foreach ($grc as $grcItem) {
						dc()->part('people', $grcItem);
					}
				?>
			</div>
			<div class="lg-2 sm-0"></div>
			<div class="lg-5 sm-6 bg-white people_block-bg-silver">
				<a href="https://sites.tufts.edu/wpf/" class="people_logo mv60">
					<img src="<?php echo dc()->file_url('assets/img/company/wpf-gray.png'); ?>" alt="">
				</a>
				<?php
					$grc = dc()->get_posts('about', array('organizations' => 'wpf'));
					foreach ($grc as $grcItem) {
						dc()->part('people', $grcItem);
					}
				?>
			</div>
		</section>

	</div>
	</div>
</div>
<?php get_footer(); ?>
