<?php
/**
 * Intro screen and uploader (step 0).
 */
?>
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

</div>
