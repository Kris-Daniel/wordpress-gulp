<?php
get_header();
the_post();
global $theID;
dc()->part('header-img', 'assets/img/bg/about2.jpg');
?>
<div class="page">
	<div class="page_box">
	<div class="pv60">
		<div class="page-bg page-bg-full"></div>

		<article class="flex-grid">
			<div class="xs-12">
				<h1 class="caption mb20">
					<?php the_title(); ?>
				</h1>
				<div class="text-min mb60 bold uppercase lightergray">
					Date: <?php echo get_the_date('d-m-Y'); ?>
				</div>
			</div>
			<div class="sm-8">
				<div class="box-f">
				<div class="f0">
					<div class="the_content lineh-big xs-12 s-12 mb160 justify">
						<?php the_content();?>

					</div>

					<div class="caption mb60">
						Similar Publications
					</div>

					<div class="publications">
					<?php



					$publications = dc()->get_posts('publications', 0, 2, array(get_the_ID()));
					foreach ($publications as $publication) {
						dc()->part('publicate', $publication);
					} ?>
					</div>

				</div>
				</div>
			</div>
			<div class="sm-1 xs-0 s-0"></div>
			<div class="sm-3 xs-12 s-12 center">
				<?php if(get_the_post_thumbnail_url($theID)) : ?>
				<div class="side_img center">
					<img src="<?= get_the_post_thumbnail_url($theID); ?>" alt="">
				</div>
				<?php endif; ?>
				<div class="share_box center side_share">
					<span class="text-min lightergray share_text">Share:</span>
					<div class="share_item share_item-twitter"><?php dc()->file('assets/img/icons/social/twitter.svg'); ?></div>
					<div class="share_item share_item-facebook"><?php dc()->file('assets/img/icons/social/facebook.svg'); ?></div>
					<div class="share_item share_item-google"><?php dc()->file('assets/img/icons/social/google.svg'); ?></div>
				</div>
				<div class="center">
					<?php
					if(get_field('file', $theID) != '')
						dc()->part('more', array('link' => get_field('file', $theID), 'text' => 'More info', 'state' => 'min', 'target' => '_blank'));
					?>
					<div class="mb20"></div>
					<?php
					if(get_field('link_our', $theID) != '')
						dc()->part('more', array('link' => get_field('link_our', $theID), 'text' => 'More info', 'state' => 'min', 'target' => '_blank'));
					?>
				</div>
			</div>
		</article>


	</div>
	</div>
</div>
<?php get_footer(); ?>