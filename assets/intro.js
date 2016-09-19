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

		// Create the media frame.
		var frame = wp.media({
			id: 'import-select',
			// Set the title of the modal.
			title: options.l10n.frameTitle,
			multiple: true,

			// Tell the modal to show only xml files.
			library: {
				type: '',
				status: 'private',
			},

			// Customize the submit button.
			button: {
				// Set the text of the button.
				text: options.l10n.buttonText,
				// Tell the button not to close the modal, since we're
				// going to refresh the page when the image is selected.
				close: false,
			},
		});
		$('.upload-select').on( 'click', function ( event ) {
			event.preventDefault();

			frame.open();
		});
		frame.on( 'select', function () {
			console.log( this, arguments );
			var attachment = frame.state().get('selection').first().toJSON();
			console.log( attachment );

			var $input = $('#import-selected-id');
			$input.val( attachment.id );
			$input.parents('form')[0].submit();
		});
	});


})( jQuery );
