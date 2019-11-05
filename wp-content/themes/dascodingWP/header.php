<?php global $trans; ?>
<!doctype html>
<html id="HTML" <?php language_attributes(); ?> style="font-size: 16px;margin-top: 0 !important;">

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<!-- TODO comment if is not used -->
	<link href="https://fonts.googleapis.com/css?family=Lato:400,400i,700,700i,900,900i&display=swap&subset=latin-ext" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
	<link href="https://cdn.rawgit.com/sachinchoolur/lightgallery.js/master/dist/css/lightgallery.css" rel="stylesheet">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!--[if IE 9]>
		<style>
			
		</style>
	<![endif]-->
	<?php wp_head(); ?>
</head>

<body>
	<div class="site">
		<header>
			<div class="header">
				
			</div>
		</header>

		<input type="hidden" value="<?= get_template_directory_uri(); ?>/" id="templateUri">
		<input type="hidden" value="<?= get_the_permalink(); ?>" id="currentLink">
		<input type="hidden" value="<?= str_replace( home_url(), "", get_permalink() ); ?>" id="permalink">
		<input type="hidden" value="<?php echo function_exists('qtrans_getLanguage') ? qtrans_getLanguage() : ''; ?>" id="currentLang">
		<div class="siteBody clearfix">