jQuery(function($) {
	var bulkOptimization = {

		inprogress: false,

		serverDown: false,

		i18n: {},

		settings: {},

		init: function() {
			if( wrio_l18n_bulk_page === undefined || wrio_settings_bulk_page === undefined ) {
				console.log('[Error]: Required global variables are not declared.');
				return;
			}

			this.i18n = wrio_l18n_bulk_page;
			this.settings = wrio_settings_bulk_page;
			this.startOptButton = $('#wrio-start-optimization');

			this.registerEvents();
			this.checkServerStatus();
		},

		registerEvents: function() {
			var self = this;

			$('#wrio-change-optimization-server').on('change', function() {
				$(this).prop('disabled', true);
				self.checkServerStatus();
			});

			this.startOptButton.on('click', function() {
				self.startOptButton = $(this);

				if( $(this).hasClass('wio-running') ) {
					self.stop();
					return;
				}

				if( self.serverDown ) {
					$.wrio_modal.showErrorModal(self.i18n.server_down_warning);
					return;
				}

				if( "1" === self.settings.need_migration ) {
					$.wrio_modal.showErrorModal(self.i18n.need_migrations);
					return;
				}

				if( "0" === self.settings.images_backup ) {
					$.wrio_modal.showWarningModal(self.i18n.process_without_backup, function() {
						self.showModal();
					});
					return;
				}

				self.showModal();

				return false;
			});
		},

		checkServerStatus: function() {
			var self = this,
				serverStatus = $('.wrio-server-status'),
				data = {
					'action': 'wbcr-rio-check-servers-status',
					'_wpnonce': self.settings.nonce
				};

			self.serverDown = false;

			data['server_name'] = $('#wrio-change-optimization-server').val();

			serverStatus.addClass('wrio-server-check-proccess');
			serverStatus.text('');
			serverStatus.removeClass('wrio-down').removeClass('wrio-stable');

			self.startOptButton.prop('disabled', true);

			$.post(ajaxurl, data, function(response) {
				serverStatus.removeClass('wrio-server-check-proccess');

				if( !response || !response.data || !response.success ) {
					if( !response || !response.data ) {
						console.log('[Error]: Response error');
						console.log(response);
						return;
					}
					serverStatus.addClass('wrio-down');
					console.log(self.i18n.server_status_down);
					serverStatus.text(self.i18n.server_status_down);
					self.serverDown = true;
				} else {
					serverStatus.addClass('wrio-stable');
					serverStatus.text(self.i18n.server_status_stable);
				}

				$('#wrio-change-optimization-server').prop('disabled', false);
				self.startOptButton.prop('disabled', false);

			}).fail(function(xhr, status, error) {
				console.log(xhr);
				console.log(status);
				console.log(error);

				self.throwError(error);
			});
		},

		showModal: function() {
			var self = this;
			var infosModal = $('#wrio-tmpl-bulk-optimization');

			if( !infosModal.length ) {
				console.log('[Error]: Html template for modal not found.');
				return;
			}

			// Swal Information before loading the optimize process.
			swal({
				title: this.i18n.modal_optimization_title,
				html: infosModal.html(),
				type: '',
				customClass: 'wrio-modal wrio-modal-optimization-way',
				showCancelButton: true,
				showCloseButton: true,
				padding: 0,
				width: 654,
				confirmButtonText: this.i18n.modal_optimization_monual_button,
				cancelButtonText: this.i18n.modal_optimization_cron_button,
				reverseButtons: true,
			}).then(function(result) {

				self.process();

				window.onbeforeunload = function() {
					return self.i18n.leave_page_warning;
				}

			}, function(dismiss) {
				if( dismiss === 'cancel' ) { // you might also handle 'close' or 'timer' if you used those
					self.process('cron');
				} else {
					throw dismiss;
				}
			});

		},

		/**
		 * Start optimization
		 * @param {string} type
		 */
		process: function(type) {

			this.inprogress = true;

			var sendData = {
				'action': 'wrio-bulk-optimization-process',
				'scope': this.settings.scope,
				'multisite': 0,
				'_wpnonce': this.settings.nonce,
			};

			this.setButtonStyleRun(type);

			if( 'cron' === type ) {
				this.startOptButton.addClass('wrio-cron-mode');

				sendData['action'] = 'wrio-cron-start';

				$.post(ajaxurl, sendData, function(response) {
					if( !response || !response.success ) {
						console.log('[Error]: Failed ajax request (Start cron).');
						console.log(sendData);
						console.log(response);

						if( response.data && response.data.error_message ) {
							self.throwError(response.data.error_message);
						}
					}
				}).fail(function(xhr, status, error) {
					console.log(xhr);
					console.log(status);
					console.log(error);

					self.throwError(error);
				});

				return;
			}

			this.showMessage(this.i18n.optimization_inprogress.replace("%s", parseInt($('#wio-unoptimized-num').text())));

			// show message: Optimization remined
			/*if( "1" === this.settings.is_network_admin ) {
				sendData['multisite'] = 1;
			}*/

			sendData['reset_current_errors'] = 1;

			this.sendRequest(sendData);
		},

		stop: function() {
			var self = this;

			this.inprogress = false;

			window.onbeforeunload = null;
			self.setButtonStyleStop();
			self.destroyMessages();

			if( this.startOptButton.hasClass('wrio-cron-mode') ) {
				this.startOptButton.removeClass('wrio-cron-mode');

				$.post(ajaxurl, {
					'action': 'wrio-cron-stop',
					'_wpnonce': self.settings.nonce,
					'type': self.settings.scope
				}, function(response) {
					if( !response || !response.success ) {
						console.log('[Error]: Failed ajax request (Stop cron).');
						console.log(response);

						if( response.data && response.data.error_message ) {
							self.throwError(response.data.error_message);
						}
					}
				}).fail(function(xhr, status, error) {
					console.log(xhr);
					console.log(status);
					console.log(error);

					self.throwError(error);
				});
			}

		},

		complete: function() {
			this.inprogress = false;
			window.onbeforeunload = null;
			this.setButtonStyleComplete();
		},

		setButtonStyleRun: function(mode) {

			this.startOptButton.addClass('wio-running');

			if( "cron" === mode ) {
				this.startOptButton.text(this.i18n.button_stop_cron);
				return;
			}

			this.startOptButton.text(this.i18n.button_stop);
		},

		setButtonStyleComplete: function() {
			this.showMessage(this.i18n.optimization_complete);
			this.startOptButton.text(this.i18n.button_completed);
			this.startOptButton.removeClass('wio-running');
			this.startOptButton.prop('disabled', true);
		},

		setButtonStyleStop: function() {
			this.startOptButton.removeClass('wio-running');
			this.startOptButton.text(this.i18n.buttom_start);
		},

		showMessage: function(text) {
			var contanier = $('.wio-page-statistic'),
				message;

			if( contanier.find('.wrio-statistic-message').length ) {
				message = contanier.find('.wrio-statistic-message');
			} else {
				message = $('<div>');
				message.addClass('wrio-statistic-message');
				contanier.append(message);
			}

			message.html(text);
		},

		throwError: function(error_message) {
			this.stop();

			var noticeId = $.wbcr_factory_clearfy_209.app.showNotice(error_message, 'danger');

			setTimeout(function() {
				$.wbcr_factory_clearfy_209.app.hideNotice(noticeId);
			}, 10000);
		},

		destroyMessages: function() {
			$('.wio-page-statistic').find('.wrio-statistic-message').remove();
		},

		sendRequest: function(data) {
			var self = this;

			if( !this.inprogress ) {
				return;
			}

			$.post(ajaxurl, data, function(response) {
				if( !self.inprogress ) {
					return;
				}

				if( !response || !response.success ) {
					console.log('[Error]: Failed ajax request (Try to optimize images).');
					console.log(response);

					if( response.data && response.data.error_message ) {
						self.throwError(response.data.error_message);
					}

					return;
				}

				data.reset_current_errors = 0;

				if( !response.data.end ) {
					$('#wio-total-unoptimized').text(parseInt(response.data.remain));
					self.showMessage(self.i18n.optimization_inprogress.replace("%s", parseInt(response.data.remain)));
					self.sendRequest(data);
				} else {
					$('#wio-total-unoptimized').text(response.data.remain);
					self.complete();

					// если мультисайт режим, то не скрываем кнопку запуска оптимизации
					/*if( $('#wbcr-rio-current-blog').length ) {
						$('#wio-start-optimization').toggleClass('wio-running');
					} else {
						$('#wio-start-optimization').hide();
					}*/
				}

				redraw_statistics(response.data.statistic);

				self.updateLog(response.data.last_optimized);
			}).fail(function(xhr, status, error) {
				console.log(xhr);
				console.log(status);
				console.log(error);

				self.throwError(error);
			});
		},

		updateLog: function(new_item_data) {
			var self = this;

			var limit = 100,
				tableEl = $('.wrio-optimization-progress .wrio-table');

			if( !tableEl.length || !new_item_data ) {
				return;
			}

			// если таблица была пустая
			if( $('.wrio-table-container-empty').length ) {
				$('.wrio-table-container-empty').addClass('wrio-table-container').removeClass('wrio-table-container-empty');
				if( tableEl.find('tbody').length ) {
					tableEl.find('tbody').empty();
				}
			}

			$.each(new_item_data, function(index, value) {
				var trEl = $('<tr>'),
					tdEl = $('<td>'),
					webpSize = value.webp_size ? value.webp_size : '-';

				if( tableEl.find('.wrio-row-id-' + value.id).length ) {
					tableEl.find('.wrio-row-id-' + value.id).remove();
				}

				trEl.addClass('flash').addClass('wrio-table-item').addClass('wrio-row-id-' + value.id);

				if( 'error' === value.type ) {
					trEl.addClass('wrio-error');
				}

				var preview = $('<img width="40" height="40" src="' + value.thumbnail_url + '" alt="">'),
					previewUrl = $('<a href="' + value.url + '" target="_blank">' + value.file_name + '</a>');

				tableEl.prepend(trEl);

				trEl.append(tdEl.clone().append(preview));
				trEl.append(tdEl.clone().append(previewUrl));

				if( 'error' === value.type ) {
					var colspan = value.scope !== 'custom-folders' ? '6' : '5';
					trEl.append(tdEl.clone().attr('colspan', colspan).text("Error: " + value.error_msg));
				} else {
					trEl.append(tdEl.clone().text(value.original_size));
					trEl.append(tdEl.clone().text(value.optimized_size));
					trEl.append(tdEl.clone().text(webpSize));
					trEl.append(tdEl.clone().text(value.original_saving));

					if( "custom-folders" !== self.settings.scope ) {
						trEl.append(tdEl.clone().text(value.thumbnails_count));
					}

					trEl.append(tdEl.clone().text(value.total_saving));
				}
			});

			if( tableEl.find('tr').length > limit ) {
				var diff = tableEl.find('tr').length - limit;

				for( var i = 0; i < diff; i++ ) {
					tableEl.find('tr:last').remove();
				}
			}
		}

	};

	$(document).ready(function() {
		bulkOptimization.init();
	});

	var ajaxUrl = ajaxurl;
	var ai_data;

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

		if( $('.wrio-statistic-nav li.active').length ) {
			$('.wrio-statistic-nav li.active').find('span.wio-statistic-tab-percent').text(statistic.optimized_percent + '%');
		}

		window.wio_chart.data.datasets[0].data[0] = statistic.unoptimized; // unoptimized
		window.wio_chart.data.datasets[0].data[1] = statistic.optimized; // optimized
		window.wio_chart.data.datasets[0].data[2] = statistic.error; // errors
		window.wio_chart.update();
		if( $('#wio-overview-chart-percent').text() == '100%' ) {
			window.onbeforeunload = null;
		}
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

	/*$('#wbcr-rio-current-blog').on('change', function() {
		var self = $(this);
		$('#wio-start-msg-complete').hide();
		$(this).attr('disabled', true);
		$('#wio-start-optimization').attr('disabled', true);
		var ai_data = {
			'action': 'wbcr_rio_update_current_blog',
			'wpnonce': $(this).data('nonce'),
			'current_blog_id': $(this).find('option:selected').val(),
			'context': $(this).attr('data-context')
		};
		$.post(ajaxUrl, ai_data, function(response) {
			self.removeAttr('disabled');
			$('#wio-start-optimization').removeAttr('disabled');
			redraw_statistics(response.data.statistic);
		});
	});*/

});
