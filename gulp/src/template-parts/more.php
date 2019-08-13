<?php if($part['link'] != 'func()') : ?>
<a href="<?php echo $part['link'];?>" class="more clearfix white gradient more-<?php echo $part['state'];?>" <?php if(isset($part['target'])) echo 'target="_blank"';?>>
	<div class="more_angleBox relative">
		<div style="background-image: url(<?php echo dc()->file_url('assets/img/icons/sprite/sprite.png');?>)" class="more_angle a-center"></div>
	</div>
	<div class="more_text center text-min bold uppercase line1">
		<?php echo $part['text']; ?>
	</div>
</a>
<?php else : ?>
<div onclick="<?= $part['func']; ?>" class="more clearfix white gradient more-<?php echo $part['state'];?>">
	<div class="more_angleBox relative">
		<div style="background-image: url(<?php echo dc()->file_url('assets/img/icons/sprite/sprite.png');?>)" class="more_angle a-center"></div>
	</div>
	<div class="more_text center text-min bold uppercase line1">
		<?php echo $part['text']; ?>
	</div>
</div>
<?php endif; ?>