<?php
/*
Template Name: Default with Sidebar
*/
?>
<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/about.jpg'); ?>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>
		<div class="box-f">

			<article class="flex-grid">
				<div class="sm-8">
					<section class="f0 mb60">
						<div class="xs-12">
							<h1 class="caption">
								<?php the_title(); ?>
							</h1>
						</div>
					</section>

					<section class="f0">
						<div class="xs-12">
							<div class="the_content justify clearfix">
								<?php if(get_the_post_thumbnail_url() != '') : ?>
								<div class="member_img">
									<img src="<?php the_post_thumbnail_url(); ?>" alt="">
								</div>
								<?php endif; ?>
								<?php the_content(); ?>
							</div>
						</div>
					</section>
				</div>
				<div class="sm-1 xs-0 s-0"></div>
				<div class="sm-3">
					<?php dc()->part('sidebar', array('ux-side', 'twitter')); ?>
				</div>
			</article>

		</div>
	</div>
	</div>
</div>
<?php get_footer(); ?>