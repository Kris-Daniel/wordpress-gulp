<?php
$publication = $part;
$fileFlag = false;
	$terms = get_the_terms($publication->ID, 'type');
	foreach ($terms as $term) {
		if($term->slug == 'file') $fileFlag = true;
	}


	$terms2 = get_the_terms($publication->ID, 'organizations');
	$termClass = '';
	foreach ($terms2 as $term) {
		if($term->slug == 'grc') $termClass .= ' grcFlag ';
		else if($term->slug == 'wpf') $termClass .= ' wpfFlag ';
	}
	if($fileFlag && get_the_post_thumbnail_url($publication->ID) != '') :
?>
	<div class="xs-12 s12 mb30 <?php echo $termClass; ?>">
	<div class="box-f">
	<div class="flex-grid publication_floatbg publication_withimg">
		<div class="xs-0 s-3 mb20"></div>
		<div class="xs-3 s-6 mb20">
			<a href="<?php echo get_the_permalink($publication->ID); ?>" class="bl publication_img cover_img">
				<img src="<?php echo get_the_post_thumbnail_url($publication->ID); ?>" alt="">
			</a>
		</div>
		<div class="xs-9">
			<div class="publication_withimg-text">
				<div class="text bold uppercase mb20 black publicate-caption"><?php echo get_the_title($publication->ID); ?></div>
				<div class="light lightgray lineh-big mb20 justify os"><?php echo stripContent($publication->post_content, 300); ?></div>
				<a href="<?php echo get_the_permalink($publication->ID); ?>" class="green uppercase text-min bold people_link">
					View more
					<span style="background-image: url(<?php echo dc()->file_url('assets/img/icons/sprite/sprite.png'); ?>)" class="tab_all_angle"></span>
				</a>
			</div>
		</div>
	</div>
	</div>
	</div>
<?php elseif(!$fileFlag &&  get_the_post_thumbnail_url($publication->ID) != '') : ?>
	<div class="xs-12 s12 mb30 <?php echo $termClass; ?>">
	<div class="box-f">
	<div class="flex-grid publication_floatbg publication_withimg">
		<div class="xs-0 s-3 mb20"></div>
		<div class="xs-3 s-6 mb20">
			<a href="<?php echo get_field('link', $publication->ID); ?>" class="bl publication_img cover_img" target="_blank">
				<img src="<?php echo get_the_post_thumbnail_url($publication->ID); ?>" alt="">
			</a>
		</div>
		<div class="xs-9">
			<div class="publication_withimg-text">
				<div class="text bold uppercase mb20 black publicate-caption"><?php echo get_the_title($publication->ID); ?></div>
				<div class="light lightgray lineh-big mb20 justify os"><?php echo stripContent($publication->post_content, 300); ?></div>
				<a href="<?php echo get_field('link', $publication->ID); ?>" class="green uppercase text-min bold people_link" target="_blank">
					View more
					<span style="background-image: url(<?php echo dc()->file_url('assets/img/icons/sprite/sprite.png'); ?>)" class="tab_all_angle"></span>
				</a>
			</div>
		</div>
	</div>
	</div>
	</div>
<?php elseif($fileFlag &&  get_the_post_thumbnail_url($publication->ID) == '') : ?>
	<div class="xs-12 s12 mb30 <?php echo $termClass; ?>">
		<a href="<?php echo get_the_permalink($publication->ID); ?>" class="publication_noimg bg-silver shadowHover" target="_blank">
			<div class="text bold uppercase mb20 black publicate-caption"><?php echo get_the_title($publication->ID); ?></div>
			<div class="light lightgray lineh-big justify os"><?php echo stripContent($publication->post_content, 300); ?></div>
		</a>
	</div>
<?php elseif(!$fileFlag &&  get_the_post_thumbnail_url($publication->ID) == '') : ?>
	<div class="xs-12 s12 mb30 <?php echo $termClass; ?>">
		<a href="<?php echo get_field('link', $publication->ID); ?>" class="publication_noimg bg-silver shadowHover" target="_blank">
			<div class="text bold uppercase mb20 black publicate-caption"><?php echo get_the_title($publication->ID); ?></div>
			<div class="light lightgray lineh-big justify os"><?php echo stripContent($publication->post_content, 300); ?></div>
		</a>
	</div>
<?php endif; ?>