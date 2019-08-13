<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/about2.jpg'); ?>

<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>

		<section class="flex-grid">
			<div class="sm-8">
				<div class="box-f">
				<div class="flex-grid">
					<article class="xs-12">
						<h1 class="caption mb60">
							<?php the_title(); ?>
						</h1>
						<div class="hr-gray"></div>

						<div class="share_box">
							<span class="text-min lightergray share_text">Share:</span>
							<div class="share_item share_item-twitter"><?php dc()->file('assets/img/icons/social/twitter.svg'); ?></div>
							<div class="share_item share_item-facebook"><?php dc()->file('assets/img/icons/social/facebook.svg'); ?></div>
							<div class="share_item share_item-google"><?php dc()->file('assets/img/icons/social/google.svg'); ?></div>
						</div>

						<div class="newsEntry mb40">
							<div class="newsEntry_bg"></div>
							<!-- <div class="newsEntry_img cover_img"> -->
								<img src="<?php the_post_thumbnail_url(); ?>" alt="">
							<!-- </div> -->
							<?php
								$dateFlag = false;
								$terms = get_the_terms($newsItem->ID, 'category');
								foreach ($terms as $term) {
									if($term->slug == 'event') $dateFlag = true;
								}
							?>
							<?php if($dateFlag) : ?>
							<div class="newsEntry_textBox clearfix">
								<div class="newsEntry_info mb20">
									<div class="text bold uppercase newsEntry_info-date">
										<?php
											$date = get_field('event_date', false, false);
											$date = new DateTime($date);
											$now  = new DateTime();
										?>
										Date: <span class="green"><?php echo $date->format('d F Y'); ?></span>
									</div>
									<div class="text bold uppercase">
										Location: <span class="green"><?php the_field('event_location'); ?></span>
									</div>
								</div>
								<?php
								if($now < $date) {
									dc()->part('more', array('link' => get_field('event_link'), 'text' => 'register', 'state' => 'min'));
								}?>
							</div>
							<?php else : ?>
							<div class="newsEntry_text text lightgray justify">
								<?php the_field('intro'); ?>
							</div>
							<?php endif; ?>
						</div>

						<div class="the_content lineh-big mb160 justify">
							<?php the_content(); ?>
						</div>

						<?php if($dateFlag) {
							//dc()->part('interest');
						}?>

						<div class="mb160"></div>

						<div class="caption mb60">
							Related <span class="green text-underline">News</span>
						</div>

						<div class="box-f">
						<div class="flex-grid">
							<?php
								$articles = dc()->get_posts('news-and-events', 0, 2, array(get_the_ID()));
								$counter  = 3;
								foreach ($articles as $article) {
									dc()->part('article', array('post' => $article, 'counter' => $counter));
								?>
								<?php
								}
							?>
						</div>
						</div>

					</article>
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