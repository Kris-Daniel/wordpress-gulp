<?php
/*Template Name: News and Events*/
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
					Our <span class="green text-underline">News</span>
				</h1>
			</div>
		</section>

		<section class="flex-grid">
			<div class="sm-8">
				<div class="box-f">
				<div class="flex-grid">
					<?php
						$articles = dc()->get_posts('news-and-events', 0, 9);
						$counter  = 0;
						foreach ($articles as $article) {
							$counter++;
							dc()->part('article', array('post' => $article, 'counter' => $counter));
						?>
						<?php
						}
					?>
					<div class="xs-12 s-12 mb40">
						<div class="articles pagination center">
							<?php dc()->createPagination(); ?>
						</div>
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