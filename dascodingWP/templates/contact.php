<?php
/*Template Name: Contact*/
?>
<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/femida3.jpg'); ?>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>


		<section class="flex-grid">
			<div class="sm-8">
			<h1 class="caption mb60">
				Contact
			</h1>
			<div class="box-f box-f-contact pv40 float_gray_bg f0">
					<div class="md-5 sm-6 mb60">
						<div class="the_content mb60">
							<?php the_field('contact_post'); ?>
						</div>
						<a href="<?php the_field('map_link'); ?>" class="contact_img" target="_blank">
							<img src="<?= get_the_post_thumbnail_url(); ?>" alt="">
						</a>
					</div>
					<div class="md-1 xs-0 s-0"></div>
					<div class="sm-6">
						<div class="the_content the_content-contact">
							<?php the_field('contact_email'); ?>
						</div>
					</div>
			</div>
			</div>
			<div class="sm-1 xs-0 s-0"></div>
			<div class="sm-3">
				<div class="fix_contact_sidebar"></div>
				<?php dc()->part('sidebar', array('twitter')); ?>
			</div>
		</section>

	</div>
	</div>
</div>
<?php get_footer(); ?>