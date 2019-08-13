<?php
/*
Template Name: Project
*/
?>
<?php get_header(); ?>
<?php the_post(); ?>
<?php dc()->part('header-img', 'assets/img/bg/about3.jpg'); ?>
<div class="page light">
	<div class="page_box">
	<div class="pt60">
		<div class="page-bg page-bg-full"></div>

			<section class="f0 mb60">
				<div class="xs-12 center">
						<h1 class="caption mb20">
							Accountability for Mass Starvation:
							<br>
							Testing the Limits of the Law
						</h1>
						<div class="text uppercase pro_descr lineh">
							INTRODUCING A NEW OUTREACH, RESEARCH, AND TRAINING PROGRAMME ON CONFLICT-INDUCED HUNGER
						</div>
						<div class="pro_logos">
							<a href="http://www.globalrightscompliance.com/" class="pro_logo">
								<img src="<?php echo dc()->file_url('assets/img/company/grc-gray.png'); ?>" alt="">
							</a>
							<a href="https://sites.tufts.edu/wpf/" class="pro_logo">
								<img src="<?php echo dc()->file_url('assets/img/company/wpf-gray.png'); ?>" alt="">
							</a>
						</div>
				</div>
			</section>

			<section class="f0">
				<div class="xs-12">
						<div class="hr-green mb20"></div>
						<div class="caption mv40 uppercase">The objective</div>
						<div class="text lineh justify the_content"><?php the_field('objective'); ?></div>
				</div>
			</section>

	</div>
	</div>
	<div class="pro_img mv40" style="background-image: url(<?= dc()->file_url('assets/img/bg/femida.jpg'); ?>);"></div>
	<div class="page_box">

			<section class="f0 mb60">
				<div class="xs-12">
						<div class="caption mb40 uppercase">Accounting for mass starvation</div>
						<div class="text lineh mb40 justify the_content"><?php the_field('accounting'); ?></div>

						<div class="green_note green bold">
							"Starvation of civilians as a method of warfare may constitute a war crime."
						</div>

						<div class="text lineh justify the_content"><?php the_field('accounting_2'); ?></div>
				</div>
			</section>
	</div>
	<div class="pro_img mv40" style="background-image: url(<?= dc()->file_url('assets/img/bg/peoples.jpg'); ?>);"></div>
	<div class="page_box">
	<div class="pb60">
			<section class="f0 mb60">
				<div class="xs-12">
						<div class="caption mb40 uppercase">The five components of the project</div>
						<div class="text lineh the_content-list justify mb80"><?php the_field('list'); ?></div>

						<div class="caption mv40 uppercase">The Leaders</div>
						<div class="text lineh mb20 justify the_content"><?php the_field('leaders'); ?></div>
						<?php dc()->part('more', array('link' => dc()->get_permalink('about'), 'text' => 'More information', 'state' => 'mid')); ?>
						<div class="mb80"></div>
						<div class="caption mb40 uppercase">How to get involved</div>
						<div class="text lineh mb20 justify the_content"><?php the_field('involved'); ?></div>
						<?php dc()->part('more', array('link' => dc()->get_permalink('contact'), 'text' => 'Contact us', 'state' => 'mid')); ?>
				</div>
			</section>
	</div>
	</div>
</div>
<?php get_footer(); ?>