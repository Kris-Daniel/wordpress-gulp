<div class="people_block clearfix">
	<div class="people_block-bg"></div>
	<a href="<?php the_permalink($part->ID); ?>" class="people_img cover_img">
		<img src="<?php echo get_the_post_thumbnail_url($part->ID); ?>" alt="">
	</a>
	<div style="overflow: hidden; padding-top: 1px;">
		<div class="uppercase text-min bold lightgray mb20">
			<a href="<?php the_permalink($part->ID); ?>" class="black">
				<?php echo $part->post_title; ?>
			</a>
		</div>
		<div class="text-min lightgray people_content mb20 light justify"><?php echo stripContent($part->post_content, 220); ?></div>
		<a href="<?php the_permalink($part->ID); ?>" class="uppercase people_link green bold" style="font-size: 14px;">
			Read more
			<span style="background-image: url(<?php echo dc()->file_url('assets/img/icons/sprite/sprite.png');?>)" class="tab_all_angle"></span>
		</a>
	</div>
</div>



