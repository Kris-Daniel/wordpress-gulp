jQuery(function($) {
	var ajaxUrl = ajaxurl;
	
	$(document).on('click', '.wio-reoptimize', function() {
		var ai_data = {
			'action' : $(this).attr('data-action'),
			'id' : $(this).attr('data-id'),
			'level' : $(this).attr('data-level')
		};
		var td = $(this).closest('td');
		var msg = $(this).attr( 'data-waiting-label' );
		td.html('<p>'+msg+'</p>');
		wio_reoptimize( ai_data, td );
		return false;
	});
	
	function wio_reoptimize( ai_data, td ) {
		$.post(ajaxUrl, ai_data, function(response) {
			if ( response === 'processing' ) {
				wio_reoptimize( ai_data, td );
				return false;
			}
			td.html(response);
			var btn = $('.wio-reoptimize').first();

			if ( btn.closest('.media-frame-content').length ) {
				if ( btn.closest('table').find('.wio-datas-list').length ) {
					var diminsionName = $('.dimensions').find('strong').clone();
					var fileSizeName = $('.file-size').find('strong').clone();
					var diminsionSize = btn.closest('table').find('.wio-datas-list').data('dimensions');
					var fileSize = btn.closest('table').find('.wio-datas-list').data('size');
					$('.dimensions').html(diminsionName.get(0).outerHTML + ' ' + diminsionSize);
					$('.file-size').html(fileSizeName.get(0).outerHTML + ' ' + fileSize);
				}
			}
		});
	}
	
	$(document).on('click', '.button-wio-restore', function() {
		var ai_data = {
			'action' : $(this).attr('data-action'),
			'id' : $(this).attr('data-id')
		};
		var td = $(this).closest('td');
		var msg = $(this).attr( 'data-waiting-label' );
		td.html('<p>'+msg+'</p>');
		$.post(ajaxUrl, ai_data, function(response) {
			td.html(response);
			var btn = $('.wio-reoptimize');
			if ( btn.closest('.media-frame-content').length ) {
				if ( btn.length ) {
					btn = btn.first();
					var diminsionName = $('.dimensions').find('strong').clone();
					var fileSizeName = $('.file-size').find('strong').clone();
					var diminsionSize = btn.data('dimensions');
					var fileSize = btn.data('size');
					$('.dimensions').html(diminsionName.get(0).outerHTML + ' ' + diminsionSize);
					$('.file-size').html(fileSizeName.get(0).outerHTML + ' ' + fileSize);
				}
			}
		});
		return false;
	});
});
