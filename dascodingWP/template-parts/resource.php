<a href="<?php the_permalink($part->ID); ?>" class="bl resource bg-silver shadowHover">
	<div class="box-f">
	<div class="flex-grid">
		<div class="md-3 xs-4 s-12 line0">
			<div class="resource_img cover_img cover_img-min100">
				<img src="<?php echo get_the_post_thumbnail_url($part->ID); ?>" alt="">
			</div>
		</div>
		<div class="md-8 xs-8 s-12 relative">
			<div class="y-center box-p">
			<div class="box-p">
				<div class="text bold lightgray mb20"><?php echo get_the_title($part->ID); ?></div>
				<div class="text light lineh lightgray"><?php echo stripContent(get_field('post_intro', $part->ID), 120); ?></div>
			</div>
			</div>
		</div>
	</div>
	</div>
</a>
