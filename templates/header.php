<?php
/**
 * Importer header. Fun times.
 */

// Load the admin header, which we skipped earlier in `on_load`
require_once( ABSPATH . 'wp-admin/admin-header.php' );

?>
<div class="wrap">

<?php do_action( 'wxr_importer.ui.header' ) ?>
