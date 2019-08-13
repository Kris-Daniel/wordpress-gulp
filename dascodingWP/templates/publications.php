<?php
/*
Template Name: Publications
*/
?>
<?php get_header(); ?>
<?php dc()->part('header-img', 'assets/img/bg/femida3.jpg'); ?>
<h1 style="display: none;">Publications</h1>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>

		<section class="flex-grid">
			<div class="sm-8">
				<div class="box-f">
				<div class="f0">
					<div class="center">
						<div class="sm-9 xs-10 s-12">
							<div class="filter">
								<div class="filter_grid">
									<div class="filter_item uppercase gray bold relative filter-all active">
										<div class="a-center">All</div>
									</div>
								</div>
								<div class="filter_grid">
									<div class="filter_item relative filter-grc">
										<div class="y-center">
											<img src="<?php echo dc()->file_url('assets/img/company/grc-gray.png'); ?>" alt="">
										</div>
									</div>
								</div>
								<div class="filter_grid">
									<div class="filter_item relative filter-wpf">
										<div class="y-center">
											<img src="<?php echo dc()->file_url('assets/img/company/wpf-gray.png'); ?>" alt="">
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mb60"></div>

					<div class="publications">
					<?php

					$publications = get_field('publications_sequence', dc()->get_page_id('publications'));
					$allPub = dc()->get_posts('publications', 0, 99999999);
					$viewPub = array();
					foreach ($allPub as $key) {
						$valid = true;
						foreach ($publications as $p) {
							if ($key->ID == $p->ID)
								$valid = false;
						}
						if($valid)
							$viewPub[] = $key;
					}
					$mergedPubl = array_merge($viewPub, $publications);

					foreach ($mergedPubl as $publication) {
						dc()->part('publicate', $publication);
					} ?>
					</div>

				</div>
				</div>
			</div>
			<div class="sm-1 xs-0 s-0"></div>
			<div class="sm-3">
				<?php dc()->part('sidebar', array('ux-side', 'twitter')); ?>
			</div>
		</section>


	</div>
	</div>
</div>
<?php get_footer(); ?>