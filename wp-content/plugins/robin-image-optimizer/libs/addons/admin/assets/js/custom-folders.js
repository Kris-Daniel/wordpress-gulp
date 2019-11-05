/**
 * General
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 10.09.2017, Webcraftic
 * @version 1.0
 */
(function($) {

	var customFolders = {

		selectedFolder: null,

		init: function() {
			if( wrio_l18n_bulk_page === undefined || wrio_settings_bulk_page === undefined ) {
				console.log('[Error]: Required global variables are not declared.');
				return;
			}

			this.i18n = wrio_l18n_bulk_page;
			this.settings = wrio_settings_bulk_page;
			this.startOptButton = $('#wrio-start-optimization');

			this.registerEvents();
		},

		registerEvents: function() {
			var self = this;

			$("#wrio-add-new-folder").on('click', function() {
				swal({
					title: self.i18n.modal_cf_title,
					html: $('#wrio-tmpl-select-custom-folders').html(),
					type: '',
					customClass: 'wrio-modal wrio-modal-optimization-way',
					showCancelButton: true,
					showCloseButton: true,
					padding: 0,
					width: 654,
					confirmButtonText: self.i18n.button_select,
					cancelButtonText: self.i18n.button_cancel,
					reverseButtons: false,
					onOpen: function(modal) {

						$(modal).find("#wrio-file-tree").fileTree({
							script: ajaxurl + '?action=wriop_browse_dir',
							multiFolder: false,
							onlyFolders: true
						});

						$(document).on('click', '#wrio-file-tree .directory > a', function() {
							var subPath = $(this).attr("rel").trim();
							if( subPath ) {
								var fullPath = subPath;
								if( fullPath.slice(-1) === '/' ) {
									fullPath = fullPath.slice(0, -1);
								}
								$("#wbcr-rio-selected-path").val(fullPath);
								self.selectedFolder = fullPath;
							}
						});

					}
				}).then(function() {
					self.addNewFolder();
				}).catch(swal.noop);

				return false;
			});
		},

		addNewFolder: function() {
			var self = this;

			var data = {
				action: 'wrio-add-custom-folder',
				path: self.selectedFolder,
				_wpnonce: self.settings.nonce,
			};

			$.post(ajaxurl, data, function(response) {
				if( !response || !response.success ) {
					console.log('[Error]: Failed ajax request (Add new folder).');
					console.log(data);
					console.log(response);

					if( response.data && response.data.error_message ) {
						// todo: так как фреймворк не используется в аддоне, нужно доработать этот кусок кода. Он не
						// может быть скомпилирован.
						var noticeId = $.wbcr_factory_clearfy_209.app.showNotice(response.data.error_message, 'danger');

						setTimeout(function() {
							$.wbcr_factory_clearfy_209.app.hideNotice(noticeId);
						}, 5000);
					}
					return;
				}

				var tr = $('<tr>'),
					td = $('<td>'),
					path = $('<span>'),
					button = $('<button>'),
					compressed_msg;

				path.addClass('wrio-table-highlighter').text(response.data.path);

				button.addClass('wbcr-rio-remove-folder')
					.addClass('btn')
					.addClass('btn-default')
					.attr('data-confirm', self.i18n.alert_remove_folder)
					.html(' <span class="dashicons dashicons-no"></span>');

				compressed_msg = self.i18n.compressed_in_folder.replace('%d', 0);
				compressed_msg = compressed_msg.replace('%s', '<span id="wrio-cf-total-' + response.data.uid + '">0</span>');

				tr.addClass('wrio-table-item')
					.append(td.clone().addClass('wrio-table-spinner'))
					.append(td.clone().html(compressed_msg))
					.append(td.clone().append(path))
					.append(td.clone().attr('data-uid', response.data.uid).append(button));

				$('.wbcr-rio-folders-table tbody').append(tr);

				self.scanFolder({
					action: 'wrio-scan-folder',
					uid: response.data.uid,
					total: 0,
					offset: 0,
					_wpnonce: self.settings.nonce,
				}, function(result) {

					tr.find('td').eq(0).removeClass('wrio-table-spinner');
					tr.find('td').eq(1).find('span').text(result.total);

					reload_ui(); // обновляем интерфейс
				});
			});
		},

		scanFolder: function(data, callback) {
			var self = this;

			$.post(ajaxurl, data, function(response) {
				console.log(response);

				if( !response || !response.success ) {
					console.log('[Error]: Failed ajax request (Scan folder).');
					console.log(data);
					console.log(response);

					if( response.data && response.data.error_message ) {
						// todo: так как фреймворк не используется в аддоне, нужно доработать этот кусок кода. Он не
						// может быть скомпилирован.
						var noticeId = $.wbcr_factory_clearfy_209.app.showNotice(response.data.error_message, 'danger');

						setTimeout(function() {
							$.wbcr_factory_clearfy_209.app.hideNotice(noticeId);
						}, 5000);
					}
					return;
				}

				data.total = response.data.total;
				data.offset = response.data.offset;

				$('#wrio-cf-total-' + data.uid).text(data.offset);

				if( response.data.complete ) {
					callback && callback(response.data);
				} else {
					self.scanFolder(data, callback);
				}
			}).fail(function(xhr, status, error) {
				// error handling
			});
		}
	};

	$(document).ready(function() {
		customFolders.init();
	});

	$(document).on('click', '.wbcr-rio-scan-folder', function() {
		$('#wbcr-rio-popup').empty();
		tb_show('Sync Folder', '#TB_inline?&width=500&height=200&inlineId=wbcr-rio-popup');
		$('#TB_ajaxContent').html('Loading...');
		var data = {
			action: 'wio_cf_get_template_part',
			template: 'sync_folder'
		};
		var self = $(this);
		$.post(ajaxurl, data, function(response) {
			$('#TB_ajaxContent').html(response);
			var ai_data = {
				'action': 'wriop_folder_sync_index',
				'uid': self.closest('td').data('uid'),
				'total': 0,
				'offset': 0,
			};
			send_indexing_data(ai_data);
		});
	});

	$('.wbcr-rio-scan-all-folders').on('click', function() {
		$('#wbcr-rio-popup').empty();
		tb_show('Sync All Folders', '#TB_inline?&width=500&height=200&inlineId=wbcr-rio-popup');
		$('#TB_ajaxContent').html('Loading...');
		var data = {
			action: 'wio_cf_get_template_part',
			template: 'sync_all_folders'
		};
		var self = $(this);
		$.post(ajaxurl, data, function(response) {
			$('#TB_ajaxContent').html(response);

		});
	});

	$(document).on('click', '.wbcr-rio-optimize-folder', function() {
		$('#wbcr-rio-popup').empty();
		tb_show('Optimize Folder', '#TB_inline?&width=500&height=200&inlineId=wbcr-rio-popup');
		$('#TB_ajaxContent').html('Loading...');
		var data = {
			action: 'wio_cf_get_template_part',
			template: 'optimize_folder'
		};
		var self = $(this);
		$.post(ajaxurl, data, function(response) {
			$('#TB_ajaxContent').html(response);
			var ai_data = {
				'uid': self.closest('td').data('uid'),
				'action': 'wriop_process_cf_folder_images',
				'_wpnonce': $('#wio-iph-nonce').val()
			};
			send_optimize_post_data(ai_data);
		});
	});

	$(document).on('click', '.wbcr-rio-restore-folder', function() {
		if( !confirm($(this).data('confirm')) ) {
			return false;
		}
		$('#wbcr-rio-popup').empty();
		tb_show('Restore Folder', '#TB_inline?&width=500&height=200&inlineId=wbcr-rio-popup');
		$('#TB_ajaxContent').html('Loading...');
		var data = {
			action: 'wio_cf_get_template_part',
			template: 'restore_folder'
		};
		var self = $(this);
		$.post(ajaxurl, data, function(response) {
			$('#TB_ajaxContent').html(response);
			var ai_data = {
				'total': '?',
				'uid': self.closest('td').data('uid'),
				'action': 'wio_cf_restore_backup',
				'_wpnonce': $('#wio-iph-nonce').val()
			};
			send_backup_post_data(ai_data);
		});
		return false;
	});

	$(document).on('click', '.wbcr-rio-remove-folder', function() {
		if( !confirm($(this).data('confirm')) ) {
			return false;
		}
		var data = {
			action: 'wriop_remove_folder',
			uid: $(this).closest('td').data('uid')
		};
		$(this).closest('tr').remove();
		$.post(ajaxurl, data, function(response) {
			reload_ui(); // обновляем интерфейс
		});
	});

	function reload_ui() {
		var data = {
			action: 'wio_cf_reload_ui',
		};
		$.post(ajaxurl, data, function(response) {
			if( response.folders_table ) {
				$('.wbcr-rio-folders-table tbody').html(response.folders_table);
			}
			if( response.statistic ) {
				redraw_statistics(response.statistic);
			}
		});
	}

	function redraw_statistics(statistic) {
		$('#wio-main-chart').attr('data-unoptimized', statistic.unoptimized)
			.attr('data-optimized', statistic.optimized)
			.attr('data-errors', statistic.error);
		$('#wio-total-optimized-attachments').text(statistic.optimized); // optimized
		$('#wio-original-size').text(bytesToSize(statistic.original_size));
		$('#wio-optimized-size').text(bytesToSize(statistic.optimized_size));
		$('#wio-total-optimized-attachments-pct').text(statistic.save_size_percent + '%');
		$('#wio-overview-chart-percent').html(statistic.optimized_percent + '<span>%</span>');
		$('.wio-total-percent').text(statistic.optimized_percent + '%');
		$('#wio-optimized-bar').css('width', statistic.percent_line + '%');

		$('#wio-unoptimized-num').text(statistic.unoptimized);
		$('#wio-optimized-num').text(statistic.optimized);
		$('#wio-error-num').text(statistic.error);

		window.wio_chart.data.datasets[0].data[0] = statistic.unoptimized; // unoptimized
		window.wio_chart.data.datasets[0].data[1] = statistic.optimized; // optimized
		window.wio_chart.data.datasets[0].data[2] = statistic.error; // errors
		window.wio_chart.update();
	}

	function bytesToSize(bytes) {
		var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		if( bytes == 0 ) {
			return '0 Byte';
		}
		var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
		if( i == 0 ) {
			return bytes + ' ' + sizes[i];
		}
		return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
	}

	function send_backup_post_data(data) {
		$.post(ajaxurl, data, function(response) {
			if( !response.end ) {
				data.total = response.total;
				send_backup_post_data(data);
				$('#wio-restore-backup-progress').find('.progress-bar').css('width', response.percent + '%');
			} else {
				$('#wio-restore-backup-progress').find('.progress-bar').css('width', '100%');
				$('#wio-restore-backup-success-msg').show();
				$('#wio-restore-backup-progress-msg').hide();
				reload_ui(); // обновляем интерфейс
			}
		});
	}

	function send_optimize_post_data(data) {
		$.post(ajaxurl, data, function(response) {
			if( !response.end ) {
				send_optimize_post_data(data);
				$('#wio-optimize-progress').find('.progress-bar').css('width', response.statistic.optimized_percent + '%');
			} else {
				$('#wio-optimize-progress').find('.progress-bar').css('width', '100%');
				$('#wio-optimize-success-msg').show();
				$('#wio-optimize-progress-msg').hide();
				reload_ui(); // обновляем интерфейс
			}
		});
	}

	function send_indexing_data(data) {
		$.post(ajaxurl, data, function(response) {
			data.total = response.total;
			data.offset = response.offset;
			if( response.complete ) {
				$('#wio-sync-progress').find('.progress-bar').css('width', '50%');
				data.action = 'wriop_folder_indexing';
				data.offset = 0;
				data.total = 0;
				send_check_index_data(data);
			} else {
				send_indexing_data(data);
				$('#wio-sync-progress').find('.progress-bar').css('width', response.percent + '%');
			}
		});
	}

	function send_check_index_data(data) {
		$.post(ajaxurl, data, function(response) {
			data.total = response.total;
			data.offset = response.offset;
			if( response.complete ) {
				$('#wio-sync-progress').find('.progress-bar').css('width', '100%');
				$('#wio-sync-success-msg').show();
				$('#wio-sync-progress-msg').hide();
				reload_ui(); // обновляем интерфейс
			} else {
				send_check_index_data(data);
				$('#wio-sync-progress').find('.progress-bar').css('width', response.percent + '%');
			}
		});
	}

	function first_indexing(data) {
		$.post(ajaxurl, data, function(response) {
			data.total = response.total;
			data.offset = response.offset;
			$('.wbcr-rio-indexing-counter').text(data.offset);
			if( response.complete ) {
				$("#wbcr-rio-add-folder").show();
				$('#wbcr-rio-indexing-text').hide();
				$('#wbcr-rio-indexing-finish-text').show();
				reload_ui(); // обновляем интерфейс
			} else {
				first_indexing(data);
			}
		});
	}

})(jQuery);
