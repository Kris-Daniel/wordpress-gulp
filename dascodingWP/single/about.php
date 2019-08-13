<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/about2.jpg'); ?>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>

		<article class="flex-grid jo">
			<div class="xs-8">
				<h1 class="caption light mb20"><?php the_title(); ?></h1>
				<div class="text lightergray mb60"><?php the_field('position'); ?></div>
				<div class="the_content justify"><?php the_content(); ?></div>
			</div>
			<div class="md-1 xs-0 s-0"></div>
			<div class="md-3 xs-4 s-12">
				<div class="mem_block">
					<div class="mem_img mb30">
						<img src="<?= get_the_post_thumbnail_url(); ?>" alt="">
					</div>
					<div class="mem_orgBox">
						<div class="mem_orgText">Organisation</div>
						<div class="mem_orgLogo">
							<?php
							$logo = '';
							$terms = get_the_terms(get_the_ID(), 'organizations');
							foreach ($terms as $term) {
								if($term->slug == 'grc') {$logo = 'assets/img/company/grc-gray.png';}
								else if ($term->slug == 'wpf') {$logo = 'assets/img/company/wpf-gray.png';}
							}
							?>
							<img src="<?= dc()->file_url($logo); ?>" alt="">
						</div>
					</div>
				</div>
			</div>
		</article>

	</div>
	</div>
</div>
<?php get_footer(); ?>