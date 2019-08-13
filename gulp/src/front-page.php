<?php get_header(); ?>
<?php the_post(); ?>
<div class="rel">
<div class="welcome_reverse">
<div class="rel">
		<div class="welcome_img welcome_img-anim welcome_img-1" style="background-image: url(<?php echo dc()->file_url('assets/img/bg/femida.jpg'); ?>);"></div>
		<div class="welcome_img welcome_img-anim welcome_img-2 mask" style="background-image: url(<?php echo dc()->file_url('assets/img/bg/peoples.jpg'); ?>);"></div>
		<div class="welcome_img welcome_img-mobile cover_img">
			<img src="<?php echo dc()->file_url('assets/img/bg/femida.jpg'); ?>">
		</div>
	</div>
</div>
</div>
<div class="welcome_header-margin"></div>
<div class="welcome_prop relative">
	<div class="welcome_title white box flex-grid">
		<div class="sm-1 xs-0 s-0"></div>
		<div class="md-9 sm-6 xs-12 s-12">
			<h1 class="welcome_title-text">
				Accountability for Mass Starvation: Testing the Limits of the Law
			</h1>
		</div>
	</div>
</div>
<div class="page">
	<div class="circle circle_front-1 circle-green"></div>
	<div class="circle circle_front-2 circle-green"></div>
	<div class="page_left">
	</div>
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg"></div>

		<section class="flex-grid relative jo">
			<div class="md-1 s-0"></div>
			<div class="md-11 relative">
				<div class="">
					<div class="caption mb10 regular">The Project</div>
					<div class="lightgray entry_text text lineh-max light theEntry_text mb20">
						<?php the_content(); ?>
					</div>
					<?php dc()->part('more', array('link' => dc()->get_permalink('project'), 'text' => 'More information', 'state' => 'mid')); ?>
				</div>
			</div>
		</section>

		<div class="mb160"></div>
		
		<div class="animate_left animate1">
			<div class="flex-grid">
				<div class="xs-12 s-12 mb-20">
					<img src="<?php echo dc()->file_url('assets/img/text/resources.png') ?>" alt="">
				</div>
			</div>

			<section class="flex-grid">
					<?php
						$counter = 0;
						$cardsHr = dc()->get_posts('resources', 0, 4);
						$resources = get_field('resources', dc()->get_page_id('home'));
						foreach ($resources as $res) {
							$counter++;
					?>
				<div class="md-3 xs-6 s-12 mb20">
					<div class="cardHR shadowHover">
						<div class="resBoxTop">
							<div class="cardHR_icon cardHR_icon-<?php echo $counter;?> mb40 text-left">
								<?= file_get_contents(get_field('icon', $res)); ?>
							</div>
							<a href="<?= get_the_permalink($res); ?>" class="text black db bold mb20"><?= get_the_title($res); ?></a>
						</div>
						<div class="text cardHR_descr lineh lightgray light"><?php the_field('post_intro', $res); ?></div>
						<?php dc()->part('more', array('link' => get_the_permalink($res), 'text' => 'read more', 'state' => 'big')); ?>
					</div>
				</div>
					<?php
						}
					?>
			</section>
		</div>

		</div>
		</div>
		<div class="pro_img pro_img-front mv40" style="background-image: url(<?= dc()->file_url('assets/img/bg/peoples.jpg'); ?>);"></div>
		<div class="page_box">
		<div class="pb60">

		<div class="animate_right animate2">

		<section>
			<div class="tabs_box">
				<div class="tabs">
					<div class="tab">
						<a href="<?php echo dc()->get_permalink('news-and-events'); ?>" class="caption black active">News & Events</a>
					</div>
					<div class="tab_all_box">
						<a href="<?php echo dc()->get_permalink('news-and-events'); ?>" class="tab_all bold green">
							view all
							<span style="background-image: url(<?php echo dc()->file_url('assets/img/icons/sprite/sprite.png');?>)" class="tab_all_angle"></span>
						</a>
					</div>
				</div>
			</div>
		</section>

		<div class="mb60"></div>

		<section class="flex-grid cardsVr relative">
			<div class="circle circle-green circle_front-3 z-1"></div>
			<?php
				wp_reset_postdata();
				$news = dc()->get_posts('news-and-events', 0, 3);
				foreach($news as $newsItem) {
					$dateFlag = false;
					$terms = get_the_terms($newsItem->ID, 'category');
					foreach ($terms as $term) {
						if($term->slug == 'event') $dateFlag = true;
					}
			?>
				<div class="md-12 xs-6 s-12 cardVr mb30">
				<div class="shadowHover relative cardVr_block">
				<div class="box-f">
				<div class="flex-grid">
					<div class="md-3 xs-12 line0">
						<a href="<?php echo get_the_permalink($newsItem->ID); ?>" class="cardVr_img relative">
							<?php if($dateFlag) : ?>
								<?php
									$date = get_field('event_date', $newsItem->ID, false);
									$date = new DateTime($date);
								?>
							<span class="cardVr_date white bg-green">
								<span class="text bold"><?php echo $date->format('d'); ?></span>
								<span class="text-min uppercase bold"><?php echo $date->format('M'); ?></span>
							</span>
							<?php endif; ?>
							<img src="<?php echo get_the_post_thumbnail_url($newsItem->ID, 'news-photo'); ?>">
						</a>
					</div>
					<div class="md-6 xs-12 relative cardVr_text_box">
						<a href="<?php echo get_the_permalink($newsItem->ID); ?>" class="cardVr_text box-p y-center lineh-big text bold black"><?php echo $newsItem->post_title; ?></a>
					</div>
					<div class="md-3 xs-12 relative cardVr_more_box">
						<?php dc()->part('more', array('link' => get_the_permalink($newsItem->ID), 'text' => 'view', 'state' => 'mid')); ?>
					</div>
				</div>
				</div>
				</div>
				</div>
			<?php } ?>
		</section>

		</div>

		<div class="mb200"></div>

		<section class="f0 frontBottom relative">
			<?php
				$newsID = get_field('chose_news', dc()->get_page_id('home'))[0];
			?>
			<div class="xs-7 s-12">
				<div class="animate_left animate4">
				<div class="mb60 text green uppercase bold frontBottom_note">Story in focus</div>
				<div class="frontBottom_text">
					<div class="caption-mid-front lightgray mb60 lineh-big uppercase">
						<?php echo get_the_title($newsID); ?>
					</div>
					<?php dc()->part('more', array('link' => get_the_permalink($newsID), 'text' => 'Read more', 'state' => 'mid')); ?>
				</div>
				</div>
			</div>
			<div class="xs-1 s-0"></div>
			<div class="xs-4 s-0">
				<div class="animate_right animate5">
				<div class="mb60"></div>
				<div class="frontBottom_img relative">
					<div class="circle circle-white circle_front-4 z-1"></div>
					<div class="circles circles-white circles_topleft z-1"></div>
					<img src="<?php echo get_field('story_img', $newsID) != '' ? get_field('story_img', $newsID) : get_the_post_thumbnail_url($newsID);?>" alt="">
				</div>
				</div>
			</div>
			<div class="frontBottom_bg bg-green"></div>
		</section>

	</div>
	</div>
</div>
<?php get_footer(); ?>