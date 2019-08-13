</div><!-- End siteBody -->

<footer class="bg-lightgray">
	<div class="page_box">
	<div class="f0">
		<div class="md-6 xs-12 footer_left">
			<div class="caption-mid caption-mid-footer white mb60">
				Get the latest information right <span class="green">to your inbox</span>
			</div>
			<div class="footer_inputBox">
				<?php dc()->part('mailchimp'); ?>
			</div>
		</div>
		<div class="md-6 xs-12 footer_right">
			<div class="text uppercase bold lightergray mb40 footer-box footer-box-follow text-left">Follow us</div>
			<br>
			<div class="footer_socials mb40 footer-box clearfix">
				<a href="http://www.globalrightscompliance.com/" class="footer_logo">
					<img src="<?php echo dc()->file_url('assets/img/company/grc-logo1.png'); ?>" alt="">
				</a>
				<a href="https://twitter.com/grc_humanrights" class="footer_social">
					<?php dc()->file('assets/img/icons/social/twitter.svg') ?>
				</a>
				<a href="https://www.facebook.com/GlobalRightsCompliance/" class="footer_social">
					<?php dc()->file('assets/img/icons/social/facebook.svg') ?>
				</a>
			</div>
			<br>
			<div class="footer_socials footer-box clearfix">
				<a href="https://sites.tufts.edu/wpf/" class="footer_logo">
					<img src="<?php echo dc()->file_url('assets/img/company/wpf-logo1.png'); ?>" alt="">
				</a>
				<a href="https://twitter.com/WorldPeaceFdtn" class="footer_social">
					<?php dc()->file('assets/img/icons/social/twitter.svg') ?>
				</a>
				<a href="https://www.facebook.com/WorldPeaceFoundation/" class="footer_social">
					<?php dc()->file('assets/img/icons/social/facebook.svg') ?>
				</a>
			</div>
		</div>

		<div class="mb100"></div>
		
		<div class="xs-12">
			<div class="footer_terms uppercase center mb10">
				<a href="https://www.iatiregistry.org/dataset/grc2018-activities" class="footer_termBox" target="_blank">
					<div class="footer_term text-min bold">IATI Reporting</div>
				</a>
				<a href="<?= dc()->get_permalink('terms-and-conditions'); ?>" class="footer_termBox">
					<div class="footer_term text-min bold">Terms & Conditions</div>
				</a>
				<a href="<?= dc()->get_permalink('privacy-policy'); ?>" class="footer_termBox">
					<div  class="footer_term text-min bold">Privacy Policy</div>
				</a>
			</div>
			<div class="footer_hr"></div>
			<div class="center">
				<a href="https://www.webdesign7.co.uk/" class="footer_designed">Designed by Webdesign7</a>
			</div>
		</div>
	</div>
	</div>
</footer>

</div><!-- End Site -->
<?php wp_footer(); ?>
</body>
</html>
