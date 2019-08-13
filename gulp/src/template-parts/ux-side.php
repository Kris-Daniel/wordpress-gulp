<div class="ux uppercase gradient bold">
	<div class="text bold white ux_pad">Resources</div>
	<?php
	global $theID;
	$resources = get_field('sequence', dc()->get_page_id('resources'));
	foreach ($resources as $res) {
		$highlight = false;
		if($theID == $res->ID)
			$highlight = true;
	?>
		<a href="<?= get_the_permalink($res->ID); ?>" class="text-min bl white ux_link ux_pad <?php if($highlight) echo 'ux_link-active' ?>">
			<?= get_the_title($res->ID); ?>
		</a>
	<?php		
	}
	?>
</div>