(function ($) {
	var options = importUploadSettings;
	var uploader, statusTemplate, errorTemplate;

	// progress and success handlers for media multi uploads
	var renderStatus = function ( attachment ) {
		var attr = attachment.attributes;
		var $status = jQuery.parseHTML( statusTemplate( attr ).trim() );

		$('.bar', $status).width( (200 * attr.loaded) / attr.size );
		$('.percent', $status).html( attr.percent + '%' );

		$('.drag-drop-status').empty().append( $status );
	};
	var renderError = function ( message ) {
		var data = {
			message: message,
		};

		var status = errorTemplate( data );
		var $status = $('.drag-drop-status');
		$status.html( status );
		$status.one( 'click', 'button', function () {
			$status.empty().hide();
			$('.drag-drop-selector').show();
		});
	};
	var actions = {
		init: function () {
			var uploaddiv = $('#plupload-upload-ui');

			if ( uploader.supports.dragdrop ) {
				uploaddiv.addClass('drag-drop');
			} else {
				uploaddiv.removeClass('drag-drop');
			}
		},

		added: function ( attachment ) {
			$('.drag-drop-selector').hide();
			$('.drag-drop-status').show();

			renderStatus( attachment );
		},

		progress: function ( attachment ) {
			renderStatus( attachment );
		},

		success: function ( attachment ) {
			$('#import-selected-id').val( attachment.id );

			renderStatus( attachment );
		},

		error: function ( message, data, file ) {
			renderError( message );
		},
	};

	// init and set the uploader
	var init = function() {
		var isIE = navigator.userAgent.indexOf('Trident/') != -1 || navigator.userAgent.indexOf('MSIE ') != -1;

		// Make sure flash sends cookies (seems in IE it does whitout switching to urlstream mode)
		if ( ! isIE && 'flash' === plupload.predictRuntime( options ) &&
			( ! options.required_features || ! options.required_features.hasOwnProperty( 'send_binary_string' ) ) ) {

			options.required_features = options.required_features || {};
			options.required_features.send_binary_string = true;
		}

		var instanceOptions = _.extend({}, options, actions);
		instanceOptions.browser = $('#plupload-browse-button');
		instanceOptions.dropzone = $('#plupload-upload-ui');

		uploader = new wp.Uploader(instanceOptions);
	};

	$(document).ready(function() {
		statusTemplate = wp.template( 'import-upload-status' );
		errorTemplate = wp.template( 'import-upload-error' );

		init();
	});

})( jQuery );
