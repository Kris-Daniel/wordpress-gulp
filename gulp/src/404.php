<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/femida3.jpg'); ?>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>
		<div class="box-f">

			<section class="flex-grid">
				<div class="xs-12">
					<section class="f0 mb60">
						<div class="xs-12">
							<h1 class="caption">
								404 Page not Found
							</h1>
						</div>
					</section>

					<section class="f0">
						<div class="xs-12">
							<div class="the_content clearfix justify">
								<?php if(get_the_post_thumbnail_url() != '') : ?>
								<div class="member_img">
									<img src="<?php the_post_thumbnail_url(); ?>" alt="">
								</div>
								<?php
									endif;
									the_content();
								?>
							</div>
						</div>
					</section>
				</div>
			</section>

		</div>
	</div>
	</div>
</div>
<?php get_footer(); ?>