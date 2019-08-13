<?php
$text = '';
$imgSrc = locate_template('assets/img/text/' . strtolower(get_the_title()) . '.png');
if (is_page() && !is_single()) {
	$text = get_the_title();
	if($text == 'About') $text = 'About us';
}

$parents = get_ancestors(get_the_ID(), 'page');
$firstParentIndex = count($parents) - 1;
$parent = $parents[$firstParentIndex];
if(isset($parent)) {
	$text = get_the_title($parent);
}
if(is_single()) {
	$postType = get_post_type();
	if($postType == 'news-and-events') $text = 'News & Events';
	if($postType == 'about') $text = 'About us';
	if($postType == 'publications') $text = 'Publications';
	//if($postType == 'resources') $text = 'Resources';
}

$headImg = '<div class="head_text white uppercase">' . $text . '</div>';
?>
<div class="head_box rel">
	<div class="head_img cover_img2" style="background-image: url(<?php echo dc()->file_url($part); ?>);"></div>
	<?= $headImg; ?>
	<?php dc()->part('breadcrumbs'); ?>
</div>