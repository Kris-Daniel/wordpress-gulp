jQuery(function($){
	var ajaxUrl = ajaxurl;
	
	$('#wio-restore-backup-btn').on('click', function() {
		if ( $('#wio-multisite-mode').length ) {
			$('#wio-multisite-mode').toggle();
			$('#wio-multisite-confirm').attr('data-action', 'restore');
			$('#wio-multisite-restore-progress').empty();
			return false;
		}
		result = confirm( $(this).attr('data-confirm') );
		if ( ! result ) {
			return false;
		}
		$(this).hide();
		$('#wio-restore-backup-progress').show();
		var ai_data = {
			'total' : '?',
			'action': 'wio_restore_backup',
			'_wpnonce': $('#wio-iph-nonce').val()
		};
		send_post_data(ai_data);
		return false;
	});
	
	$('#wio-clear-backup-btn').on('click', function() {
		$('#wio-restore-backup-msg').hide();
		if ( $('#wio-multisite-mode').length ) {
			$('#wio-multisite-mode').toggle();
			$('#wio-multisite-confirm').attr('data-action', 'clear');
			$('#wio-multisite-restore-progress').empty();
			return false;
		}
		result = confirm( $(this).attr('data-confirm') );
		if ( ! result ) {
			return false;
		}
		var data = {
			'action': 'wio_clear_backup',
			'_wpnonce': $('#wio-iph-nonce').val()
		};
		$.post(ajaxUrl, data, function(response) {
			$('#wio-clear-backup-msg').show();
		});
	});
	
	$('#wio-multisite-confirm').on('click', function() {
		var action = $(this).attr('data-action');
		// если запущена очистка резервных копий
		if ( action == 'clear' ) {
			result = confirm( $('#wio-clear-backup-btn').attr('data-confirm') ); // берём сообщение из основной кнопки
			if ( ! result ) {
				return false;
			}
			var blogs = [];
			$('.wbcr_io_multisite_blogs:checked').each(function() {
				blogs.push( $(this).val() );
			});
			var data = {
				'action': 'wio_clear_backup',
				'_wpnonce': $('#wio-iph-nonce').val(),
				'blogs': blogs
			};
			
			$.post(ajaxUrl, data, function(response) {
				$('#wio-clear-backup-msg').show();
				$('#wio-multisite-mode').toggle();
			});
			return false;
		}
		
		// если запущено восстановление из резервных копий
		if ( action == 'restore' ) {
			result = confirm( $('#wio-restore-backup-btn').attr('data-confirm') ); // берём сообщение из основной кнопки
			if ( ! result ) {
				return false;
			}
			$('#wio-multisite-mode').toggle();
			$('#wio-multisite-restore-progress').empty();
			$('.wbcr_io_multisite_blogs:checked').each(function() {
				$('#wio-multisite-restore-progress').append('\
					<label>'+$(this).attr('data-name')+'</label>\
					<div class="progress">\
						<div id="wio-restore-backup-progress-'+$(this).val()+'" class="wio-restore-backup-progressbar progress-bar progress-bar-success" data-id="'+$(this).val()+'" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">\
						</div>\
					</div>\
				');
			});
			$('#wio-multisite-restore-progress').show();
			if ( ! $('.wio-restore-backup-progressbar').length ) {
				$('#wio-restore-backup-msg').show();
				return false;
			}
			var ai_data = {
				'total' : '?',
				'action': 'wio_restore_backup',
				'_wpnonce': $('#wio-iph-nonce').val(),
				'blog_id': $('.wio-restore-backup-progressbar:eq(0)').attr('data-id')
			};
			send_multisite_post_data(ai_data);
			return false;
		}
	});
	
	$('#wbcr_io_multisite_blog_all').on('change', function() {
		if ( $(this).attr('checked') == 'checked' ) {
			$('.wbcr_io_multisite_blogs').attr('checked', true);
		} else {
			$('.wbcr_io_multisite_blogs').removeAttr('checked');
		}
	});
	
	$('.wbcr_io_multisite_blogs').on('change', function() {
		var all_checked = true;
		$('.wbcr_io_multisite_blogs').each(function() {
			if ( $(this).attr('checked') != 'checked' ) {
				all_checked = false;
			}
		});
		if ( all_checked ) {
			$('#wbcr_io_multisite_blog_all').attr('checked', true);
		} else {
			$('#wbcr_io_multisite_blog_all').removeAttr('checked');
		}
	});
	
	function send_post_data(data){
		$.post(ajaxUrl, data, function(response) {
			if ( ! response.end ) {
				data.total = response.total;
				send_post_data(data);
				$('#wio-restore-backup-progress').find('.progress-bar').css( 'width', response.percent + '%' );
			} else {
				$('#wio-restore-backup-progress').find('.progress-bar').css( 'width', '100%' );
				$('#wio-restore-backup-msg').show();
			}
		});
	}
	
	function send_multisite_post_data(data){
		$.post(ajaxUrl, data, function(response) {
			if ( ! response.end ) {
				data.total = response.total;
				send_multisite_post_data(data);
				$('#wio-restore-backup-progress-' + data.blog_id).css( 'width', response.percent + '%' );
			} else {
				$('#wio-restore-backup-progress-' + data.blog_id).css( 'width', '100%' ).removeClass('wio-restore-backup-progressbar');
				if ( $('.wio-restore-backup-progressbar').length ) {
					var ai_data = {
						'total' : '?',
						'action': 'wio_restore_backup',
						'_wpnonce': $('#wio-iph-nonce').val(),
						'blog_id': $('.wio-restore-backup-progressbar:eq(0)').attr('data-id')
					};
					send_multisite_post_data(ai_data);
				} else {
					$('#wio-restore-backup-msg').show();
				}
			}
		});
	}
});
