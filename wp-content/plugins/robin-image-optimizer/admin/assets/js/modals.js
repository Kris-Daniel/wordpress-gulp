/**
 * A set of tools for creating pop-ups. You can create a popup
 * using a global method call.
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 05.04.2019, Webcraftic
 * @version 1.0
 */


(function($) {
	'use strict';

	if( !$.wrio_modal ) {
		$.wrio_modal = {};
	}

	$.wrio_modal = $.wrio_popup || {

		showErrorModal: function(text) {
			if( !text ) {
				console.log('[Error]: Text required.');
				return;
			}

			swal({
				title: 'Error',
				text: text,
				type: 'error',
				customClass: 'wrio-modal wrio-modal-error',
				width: 500,
				confirmButtonText: 'OK',
			});
		},

		showWarningModal: function(text, callback) {
			if( !text ) {
				console.log('[Error]: Text required.');
				return;
			}

			swal({
				title: 'Warning',
				text: text,
				type: 'warning',
				customClass: 'wrio-modal wrio-modal-warning',
				width: 500,
				showCancelButton: true,
				showCloseButton: true,
				confirmButtonText: 'OK',
			}).then(function(result) {
				if( callback ) {
					callback();
				}
			}).catch(swal.noop);
		},
	};

})(jQuery);
