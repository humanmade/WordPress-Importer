<?php
/**
 * Options for the import (step 1).
 */
?>
<form action="<?php echo $this->get_url( 2 ) ?>" method="post">

	<?php if ( ! empty( $data->users ) ) : ?>

		<h3><?php esc_html_e( 'Assign Authors', 'wordpress-importer' ) ?></h3>
		<p><?php _e( 'To make it easier for you to edit and save the imported content, you may want to reassign the author of the imported item to an existing user of this site. For example, you may want to import all the entries as <code>admin</code>s entries.', 'wordpress-importer' ) ?></p>

		<?php if ( $this->allow_create_users() ): ?>

			<p><?php printf( __( 'If a new user is created by WordPress, a new password will be randomly generated and the new user&#8217;s role will be set as %s. Manually changing the new user&#8217;s details will be necessary.', 'wordpress-importer' ), esc_html( get_option('default_role') ) ) ?></p>

		<?php endif; ?>

		<ol id="authors">

			<?php foreach ( $data->users as $index => $users ): ?>

				<li><?php $this->author_select( $index, $users['data'] ); ?></li>

			<?php endforeach ?>

		</ol>

	<?php endif; ?>

	<?php if ( $this->allow_fetch_attachments() ) : ?>

		<h3><?php esc_html_e( 'Import Attachments', 'wordpress-importer' ) ?></h3>
		<p>
			<input type="checkbox" value="1" name="fetch_attachments" id="import-attachments" />
			<label for="import-attachments"><?php
				esc_html_e( 'Download and import file attachments', 'wordpress-importer' ) ?></label>
		</p>

	<?php endif; ?>

	<input type="hidden" name="import_id" value="<?php echo esc_attr( $this->id ) ?>" />
	<?php wp_nonce_field( 'import-wordpress' ) ?>

	<?php submit_button( __( 'Start Importing', 'wordpress-importer' ) ) ?>

</form>
