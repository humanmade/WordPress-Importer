<div id="plupload-upload-ui" class="hide-if-no-js">
	<?php
	/**
	 * Fires before the upload interface loads.
	 *
	 * @since 2.6.0 As 'pre-flash-upload-ui'
	 * @since 3.3.0
	 */
	do_action( 'pre-plupload-upload-ui' ); ?>

	<div id="drag-drop-area">
		<div class="drag-drop-inside drag-drop-selector">
			<p class="drag-drop-info"><?php _e('Drop files here'); ?></p>
			<p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
			<p class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></p>
		</div>
		<div class="drag-drop-inside drag-drop-status"></div>
	</div>

	<?php
	/**
	 * Fires after the upload interface loads.
	 *
	 * @since 2.6.0 As 'post-flash-upload-ui'
	 * @since 3.3.0
	 */
	do_action( 'post-plupload-upload-ui' ); ?>
</div>

<div id="html-upload-ui" class="hide-if-js">
	<?php
	/**
	 * Fires before the upload button in the media upload interface.
	 *
	 * @since 2.6.0
	 */
	do_action( 'pre-html-upload-ui' );
	?>
	<p id="async-upload-wrap">
		<label class="screen-reader-text" for="async-upload"><?php _e('Upload'); ?></label>
		<input type="file" name="async-upload" id="async-upload" />
		<?php submit_button( __( 'Upload' ), 'primary', 'html-upload', false ); ?>
		<a href="#" onclick="try{top.tb_remove();}catch(e){}; return false;"><?php _e('Cancel'); ?></a>
	</p>

	<div class="clear"></div>
	<?php
	/**
	 * Fires after the upload button in the media upload interface.
	 *
	 * @since 2.6.0
	 */
	do_action( 'post-html-upload-ui' );
	?>
</div>

<p class="max-upload-size"><?php printf(
	__( 'Maximum upload file size: %s.' ),
	esc_html( size_format( $max_upload_size ) )
) ?></p>

<style>
#plupload-upload-ui .drag-drop-status {
	display: none;

	margin: 70px auto 0;
	width: 400px;
}
#plupload-upload-ui .drag-drop-status .media-item {
	width: 200px;
	margin: 0 auto;
}
</style>

<script type="text/html" id="tmpl-import-uploading-file">
	<div class="media-item" id="media-item-{{ data.id }}">
		<div class="progress">
			<div class="percent">0%</div>
			<div class="bar"></div>
		</div>
		<div class="filename original">{{ data.filename }}</div>
	</div>
</script>

<script type="text/html" id="tmpl-import-upload-status">
	<# if ( data.uploading ) { #>
		<p><?php echo wp_kses( sprintf(
			__( 'Uploading %s&#8230;', 'wordpress-importer' ),
			'<code>{{ data.filename }}</code>'
		), 'data' ) ?></p>

		<div class="media-item">
			<div class="progress">
				<div class="percent">0%</div>
				<div class="bar"></div>
			</div>
		</div>

	<# } else { #>

		<p><?php esc_html_e( 'Success! Your import file is ready, let&#8217;s get started.', 'wordpress-importer' ) ?></p>
		<p><button type="submit" class="button"><?php esc_html_e( 'Start Import', 'wordpress-importer' ) ?></a></p>

	<# } #>
</script>

<script type="text/html" id="tmpl-import-upload-error">
	<p><?php printf(
		esc_html__( 'Whoops, could not upload file: %s', 'wordpress-importer' ),
		'{{ data.message }}'
	) ?></p>
	<p><button type="button" class="button"><?php esc_html_e( 'Try Again', 'wordpress-importer' ) ?></button></p>
</script>
