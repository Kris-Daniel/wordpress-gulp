<?php
$counter = $part['counter'];
$part = $part['post'];
$link = get_the_permalink($part->ID); ?>
<div class="xs-6 s-12 mb30 <?php if($counter == 1) echo 'article_grid-full'; ?>">
	<div class="article shadowHover">
		<div class="article_marks uppercase">
			<?php
				$terms = get_the_terms($part->ID, 'category');
				foreach ($terms as $term) {
					echo '<div class="article_mark gradient white bold">' . $term->slug .'</div>';
				}
			?>
		</div>
		<a href="<?php echo $link; ?>" class="article_img cover_img">
			<img src="<?php echo get_the_post_thumbnail_url($part->ID, 'full'); ?>" alt="">
		</a>
		<div class="article_text">
			<a href="<?php echo $link; ?>">
				<div class="text bold mb20 black lineh-big"><?php echo $part->post_title; ?></div>
			</a>
			<?php if($counter == 1) : ?>
			<div class="text mb20 lineh-big justify"><?php echo stripContent($part->post_content, 350); ?></div>
			<?php endif; ?>
			<a href="<?php echo $link; ?>" class="article_link bold green uppercase">
				Read more
				<span style="background-image: url(<?php echo dc()->file_url('assets/img/icons/sprite/sprite.png');?>)" class="tab_all_angle"></span>
			</a>
		</div>
	</div>
</div>