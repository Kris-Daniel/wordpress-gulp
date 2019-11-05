jQuery(function($) {
	$('#wbcr-wio-meta-migration-action').on('click', function() {

		var data = {
			'action': 'wrio_meta_migrations',
			'_wpnonce': $(this).data('nonce'),
		};

		$(this).addClass('disabled').text('Please wait...');

		send_request($(this), data);
	});

	function send_request(button, data) {
		$.post(window.ajaxurl, data, function(response) {

			console.log(response);

			if( !response || !response.data ) {
				console.log('An unknown server error has occurred.');
				console.log(response);
				return false;
			}

			if( !response.data.need_more_time ) {
				if( button.closest('.notice').length ) {
					button.closest('.notice').remove();
				}
				if( button.closest('.alert').length ) {
					button.closest('.alert').remove();
				}

				return false;
			}

			button.text(response.data.message);

			send_request(button, data);
		}).fail(function(xhr, status, error) {
			console.log(xhr);
			console.log(status);
			console.log(error);

			data.limit = 5;
			data.error = 1;

			setTimeout(function() {
				send_request(button, data);
			}, 2000);
		});
	}
});
