<?php
/**
 * Intro screen and uploader (step 0).
 */

wp_enqueue_media();

$this->render_header();

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
		<button class="button upload-select"><?php esc_html_e(
			'Select it from the Media Library',
			'wordpress-importer'
		) ?></button>

		<?php wp_nonce_field( 'import-upload' ) ?>
		<input type="hidden" id="import-selected-id" name="id" value="" />
	</form>

</div>

<?php

$this->render_footer();
