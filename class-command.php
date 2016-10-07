<?php

class WXR_Import_Command extends WP_CLI_Command {
	/**
	 * Import content from a WXR file.
	 *
	 * ## OPTIONS
	 *
	 * <file>...
	 * : Path to one or more valid WXR files for importing. Directories are also accepted.
	 *
	 * [--verbose[=<level>]]
	 * : Should we print verbose statements?
	 *   (No value for 'info'; or one of 'emergency', 'alert', 'critical',
	 *   'error', 'warning', 'notice', 'info', 'debug')
	 *
	 * [--default-author=<id>]
	 * : Default author ID to use if invalid user is found in the import data.
	 *
	 * [--disable-fetch-attachments]
	 * : Disable downloading external attachments.
	 *
	 */
	public function import( $args, $assoc_args ) {
		$logger = new WP_Importer_Logger_CLI();
		if ( ! empty( $assoc_args['verbose'] ) ) {
			if ( $assoc_args['verbose'] === true ) {
				$logger->min_level = 'info';
			} else {
				$valid = $logger->level_to_numeric( $assoc_args['verbose'] );
				if ( ! $valid ) {
					WP_CLI::error( 'Invalid verbosity level' );
					return;
				}

				$logger->min_level = $assoc_args['verbose'];
			}
		}

		$options = array(
			'fetch_attachments' => empty( $assoc_args['disable-fetch-attachments'] ),
		);
		if ( isset( $assoc_args['default-author'] ) ) {
			$options['default_author'] = absint( $assoc_args['default-author'] );

			if ( ! get_user_by( 'ID', $options['default_author'] ) ) {
				WP_CLI::error( 'Invalid default author ID specified.' );
			}
		}
		$importer = new WXR_Importer( $options );
		$importer->set_logger( $logger );
		$importer->import( realpath( $args[0] ) );
	}
}
