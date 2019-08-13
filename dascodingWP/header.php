<?php
global $theID;
$theID = get_the_ID();
?>
<!doctype html>
<html id="HTML" <?php language_attributes(); ?> style="font-size: 16px;">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i" rel="stylesheet">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!--[if IE 9]>
		<style>
			
		</style>
	<![endif]-->
	<?php wp_head();
	$time = time();?>
	<!-- <link rel="stylesheet" href="https://www.webdesign7.co.uk/css/grc.css?v=<?= $time;?>"> -->
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-131898049-2"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-131898049-2');
	</script>
</head>
<div class="site">

<header>
	<div class="header_wrapper page_box clearfix">
		<a href="http://www.globalrightscompliance.com/" class="header_logo header_logo-left">
			<img src="<?php echo dc()->file_url('assets/img/company/grc-logo1.png'); ?>" alt="">
		</a>
		<div class="header_menu">
			<?php dc()->createMenu('header', 'header'); ?>
		</div>
		<div class="bar">
			<?php dc()->file('assets/img/icons/custom/menu1.svg'); ?>
		</div>
		<a href="https://sites.tufts.edu/wpf/" class="header_logo header_logo-right text-right">
			<img src="<?php echo dc()->file_url('assets/img/company/wpf-logo1.png'); ?>" alt="">
		</a>
	</div>
</header>
<input type="hidden" value="<?= get_template_directory_uri(); ?>/" id="templateUri">
<input type="hidden" value="<?php echo get_the_permalink(); ?>" id="currentLink">
<div class="siteBody clearfix">