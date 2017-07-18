<?php
/**
 * Resume the import.
 */

$this->render_header();

$generator = $data->generator;
if ( preg_match( '#^http://wordpress\.org/\?v=(\d+\.\d+\.\d+)$#', $generator, $matches ) ) {
	$generator = sprintf( __( 'WordPress %s', 'wordpress-importer' ), $matches[1] );
}

?>
<div class="welcome-panel">
	<div class="welcome-panel-content">
		<h2><?php esc_html_e( 'Resume Import', 'wordpress-importer' ) ?></h2>
		<p><?php esc_html_e( 'We have detected this import can be resumed. Click Resume below, or chose to delete the partial import data.', 'wordpress-importer' ) ?></p>
	</div>
</div>

<form action="<?php echo esc_url( $this->get_url( 2 ) ) ?>" method="post">
	<input type="hidden" name="import_id" value="<?php echo esc_attr( $this->id ) ?>" />
	<?php wp_nonce_field( sprintf( 'wxr.import:%d', $this->id ) ) ?>

	<?php submit_button( __( 'Resume Import', 'wordpress-importer' ) ) ?>
</form>

<?php

$this->render_footer();
