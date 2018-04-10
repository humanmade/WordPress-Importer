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
	 * [--prefill=<values>]
	 * : Prefill posts, comments and/or terms (Default to posts,comments,terms)
	 *
	 * [--update-attachment-guids]
	 * : Update attachment GUID after file has been downloaded.
	 *
	 * [--fetch-attachments=<bool>]
	 * : Actually download attachments or skip this step.
	 * ---
	 * default: true
	 * options:
	 *   - true
	 *   - false
	 *
	 * [--aggressive-url-search]
	 * : Should we search/replace for URLs aggressively. If set searches all posts' content for old URLs and replaces. Other only checks for `<img class="wp-image-*">` (default)
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

		$path = realpath( $args[0] );
		if ( ! $path ) {
			WP_CLI::error( sprintf( 'Specified file %s does not exist', $args[0] ) );
		}

		$options = array(
			'prefill_existing_posts' => true,
			'prefill_existing_comments' => true,
			'prefill_existing_terms' => true,
			'update_attachment_guids' => false,
			'aggressive_url_search' => false,
			'fetch_attachments' => true
		);
		if ( isset( $assoc_args['fetch-attachments'] ) ) {
			$options['fetch_attachments'] = preg_match('/^(y|yes|1|true)$/i', $assoc_args['fetch-attachments'] );
		}
		if ( isset( $assoc_args['update-attachment-guids'] ) ) {
			$options['update_attachment_guids'] = !empty( $assoc_args['update-attachment-guids'] );
		}
		if ( isset( $assoc_args['aggressive-url-search'] ) ) {
			$options['aggressive_url_search'] = !empty( $assoc_args['aggressive-url-search'] );
		}
		if ( isset( $assoc_args['prefill'] ) ) {
			$options = array_merge( $options,
									array( 'prefill_existing_posts' => in_array( 'posts', explode(',', $assoc_args['prefill'] ), TRUE ),
										   'prefill_existing_comments' => in_array( 'comments', explode(',', $assoc_args['prefill'] ), TRUE ),
										   'prefill_existing_terms' => in_array( 'terms', explode(',', $assoc_args['prefill'] ), TRUE ) ) );
		}
		if ( isset( $assoc_args['default-author'] ) ) {
			$options['default_author'] = absint( $assoc_args['default-author'] );

			if ( ! get_user_by( 'ID', $options['default_author'] ) ) {
				WP_CLI::error( 'Invalid default author ID specified.' );
			}
		}
		$importer = new WXR_Importer( $options );
		$importer->set_logger( $logger );
		$result = $importer->import( $path );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
	}
}
