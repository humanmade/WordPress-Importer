<?php
/**
 * Intro screen and uploader (step 0).
 */

wp_enqueue_media();

?>
<h2><?php esc_html_e( 'Import WordPress', 'wordpress-importer' ) ?></h2>

<div class="narrow">

	<p><?php esc_html_e(
		'Howdy! Upload your WordPress eXtended RSS (WXR) file and we&#8217;ll import the posts, pages, comments, custom fields, categories, and tags into this site.',
		'wordpress-importer'
	) ?></p>

	<p><?php esc_html_e(
		'Choose a WXR (.xml) file to upload, then click Upload file and import.',
		'wordpress-importer'
	) ?></p>

	<?php wp_import_upload_form( $this->get_url( 1 ) ) ?>

	<form action="<?php echo esc_attr( $this->get_url( 1 ) ) ?>" method="GET">
		<p><?php esc_html_e(
			'Already uploaded your WXR file?.',
			'wordpress-importer'
		) ?></p>
		<button class="button upload-select">Select it from the Media Library</button>

		<?php wp_nonce_field( 'import-upload' ) ?>
		<input type="hidden" id="import-selected-id" name="id" value="" />
	</form>

</div>

<script>
jQuery( function ($) {
	// Create the media frame.
	var frame = wp.media({
		// Set the title of the modal.
		title: "Select",

		// Tell the modal to show only images.
		library: {
			type: '',
		},

		// Customize the submit button.
		button: {
			// Set the text of the button.
			text: "Import",
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
</script>
