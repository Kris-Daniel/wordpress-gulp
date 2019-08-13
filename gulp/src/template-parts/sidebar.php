<div class="sidebar">
<div class="mb40"></div>
<?php
foreach ($part as $item) {
	dc()->part($item);
}
?>
<div class="mv40 text light" style="font-size: 24px;">Latest News</div>
<div class="hr-gray mb20"></div>
<?php
$newses = dc()->get_posts('news-and-events', 0, 5);
foreach ($newses as $news) {
?>
<a href="<?= get_the_permalink($news->ID); ?>" class="text db mb10 lightgray"><?= get_the_title($news->ID); ?></a>
<div class="text-min lightergray mb10"><?php echo get_the_date('M d Y'); ?></div>
<div class="hr-gray mb10"></div>
<?php
}

?>
<div class="mb60"></div>
<div class="twitter">
	<a class="twitter-timeline" href="https://twitter.com/grc_humanrights?ref_src=twsrc%5Etfw&hashtag=Russia">Tweets by grc_humanrights</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
</div>
</div>