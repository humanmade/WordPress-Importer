<?php
/**
 * Intro screen and uploader (step 0).
 */

wp_enqueue_media();

?>
<div class="welcome-panel">
	<div class="welcome-panel-content">
		<h2><?php esc_html_e( 'Step 1: Select Your Files', 'wordpress-importer' ) ?></h2>
		<p><?php esc_html_e(
			'Welcome to the WordPress Importer! Let&#8217;s get started importing your posts, pages, and media.',
			'wordpress-importer'
		) ?></p>
		<p><?php esc_html_e(
			'To get started, simply upload a WordPress eXtended RSS (WXR) file to import.',
			'wordpress-importer'
		) ?></p>
	</div>
</div>

<div class="narrow">

	<form action="<?php echo esc_attr( $this->get_url( 1 ) ) ?>" method="POST">

		<?php $this->render_upload_form() ?>

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
			status: 'private',
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
