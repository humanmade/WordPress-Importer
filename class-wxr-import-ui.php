<?php

class WXR_Import_UI {
	/**
	 * Should we fetch attachments?
	 *
	 * Set in {@see display_import_step}.
	 *
	 * @var bool
	 */
	protected $fetch_attachments = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wxr_importer.ui.header', array( $this, 'show_updates_in_header' ) );
		add_action( 'admin_action_wxr-import-upload', array( $this, 'handle_async_upload' ) );
		add_filter( 'upload_mimes', array( $this, 'add_mime_type_xml' ) );

	}

	/**
	 * Add .xml files as supported format in the uploader.
	 *
	 * @param array $mimes Already supported mime types.
	 */
	public function add_mime_type_xml( $mimes ) {
		$mimes = array_merge( $mimes, array( 'xml' => 'application/xml' ) );

		return $mimes;
	}

	/**
	 * Show an update notice in the importer header.
	 */
	public function show_updates_in_header() {
		// Check for updates too.
		$updates = get_plugin_updates();
		$basename = plugin_basename( __FILE__ );
		if ( empty( $updates[ $basename ] ) ) {
			return;
		}

		$message = sprintf(
			esc_html__( 'A new version of this importer is available. Please update to version %s to ensure compatibility with newer export files.', 'wordpress-importer' ),
			$updates[ $basename ]->update->new_version
		);

		$args = array(
			'action' => 'upgrade-plugin',
			'plugin' => $basename,
		);
		$url = add_query_arg( $args, self_admin_url( 'update.php' ) );
		$url = wp_nonce_url( $url, 'upgrade-plugin_' . $basename );
		$link = sprintf( '<a href="%s" class="button">%s</a>', $url, esc_html__( 'Update Now', 'wordpress-importer' ) );

		printf( '<div class="error"><p>%s</p><p>%s</p></div>', $message, $link );
	}

	/**
	 * Get the URL for the importer.
	 *
	 * @param int $step Go to step rather than start.
	 */
	protected function get_url( $step = 0 ) {
		$path = 'admin.php?import=wordpress';
		if ( $step ) {
			$path = add_query_arg( 'step', (int) $step, $path );
		}
		return admin_url( $path );
	}

	protected function display_error( WP_Error $err, $step = 0 ) {
		$this->render_header();

		echo '<p><strong>' . __( 'Sorry, there has been an error.', 'wordpress-importer' ) . '</strong><br />';
		echo $err->get_error_message();
		echo '</p>';
		printf(
			'<p><a class="button" href="%s">Try Again</a></p>',
			esc_url( $this->get_url( $step ) )
		);

		$this->render_footer();
	}

	/**
	 * Handle load event for the importer.
	 */
	public function on_load() {
		// Skip outputting the header on our import page, so we can handle it.
		$_GET['noheader'] = true;
	}

	/**
	 * Render the import page.
	 */
	public function dispatch() {
		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
		switch ( $step ) {
			case 0:
				$this->display_intro_step();
				break;
			case 1:
				check_admin_referer( 'import-upload' );
				$this->display_author_step();
				break;
			case 2:
				$this->display_import_step();
				break;
		}
	}

	/**
	 * Render the importer header.
	 */
	protected function render_header() {
		require __DIR__ . '/templates/header.php';
	}

	/**
	 * Render the importer footer.
	 */
	protected function render_footer() {
		require __DIR__ . '/templates/footer.php';
	}

	/**
	 * Display introductory text and file upload form
	 */
	protected function display_intro_step() {
		require __DIR__ . '/templates/intro.php';
	}

	protected function render_upload_form() {
		/**
		 * Filter the maximum allowed upload size for import files.
		 *
		 * @since 2.3.0
		 *
		 * @see wp_max_upload_size()
		 *
		 * @param int $max_upload_size Allowed upload size. Default 1 MB.
		 */
		$max_upload_size = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			$error = '<div class="error inline"><p>';
			$error .= esc_html__(
				'Before you can upload your import file, you will need to fix the following error:',
				'wordpress-importer'
			);
			$error .= sprintf( '<p><strong>%s</strong></p></div>', $upload_dir['error'] );
			echo $error;
			return;
		}

		// Queue the JS needed for the page
		$url = plugins_url( 'assets/intro.js', __FILE__ );
		$deps = array(
			'wp-backbone',
			'wp-plupload',
		);
		wp_enqueue_script( 'import-upload', $url, $deps, false, true );

		// Set uploader settings
		wp_plupload_default_settings();
		$settings = array(
			'l10n' => array(
				'frameTitle' => esc_html__( 'Select', 'wordpress-importer' ),
				'buttonText' => esc_html__( 'Import', 'wordpress-importer' ),
			),
			'next_url' => wp_nonce_url( $this->get_url( 1 ), 'import-upload' ) . '&id={id}',
			'plupload' => array(
				'filters' => array(
					'max_file_size' => $max_upload_size . 'b',
					'mime_types'    => array(
						array(
							'title'      => esc_html__( 'XML files', 'wordpress-importer' ),
							'extensions' => 'xml',
						),
					),
				),

				'file_data_name' => 'import',
				'multipart_params' => array(
					'action'   => 'wxr-import-upload',
					'_wpnonce' => wp_create_nonce( 'wxr-import-upload' ),
				),
			),
		);
		wp_localize_script( 'import-upload', 'importUploadSettings', $settings );

		wp_enqueue_style( 'wxr-import-upload', plugins_url( 'assets/intro.css', __FILE__ ), array(), '20160412' );

		// Load the template
		remove_action( 'post-plupload-upload-ui', 'media_upload_flash_bypass' );
		require __DIR__ . '/templates/upload.php';
		add_action( 'post-plupload-upload-ui', 'media_upload_flash_bypass' );
	}

	/**
	 * Display the author picker (or upload errors).
	 */
	protected function display_author_step() {
		if ( isset( $_REQUEST['id'] ) ) {
			$err = $this->handle_select( wp_unslash( $_REQUEST['id'] ) );
		} else {
			$err = $this->handle_upload();
		}
		if ( is_wp_error( $err ) ) {
			$this->display_error( $err );
			return;
		}

		$data = $this->get_data_for_attachment( $this->id );
		if ( is_wp_error( $data ) ) {
			$this->display_error( $data );
			return;
		}

		require __DIR__ . '/templates/select-options.php';
	}

	/**
	 * Handles the WXR upload and initial parsing of the file to prepare for
	 * displaying author import options
	 *
	 * @return bool|WP_Error True on success, error object otherwise.
	 */
	protected function handle_upload() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'wxr_importer.upload.error', esc_html( $file['error'] ), $file );
		} elseif ( ! file_exists( $file['file'] ) ) {
			$message = sprintf(
				esc_html__( 'The export file could not be found at %s. It is likely that this was caused by a permissions problem.', 'wordpress-importer' ),
				'<code>' . esc_html( $file['file'] ) . '</code>'
			);
			return new WP_Error( 'wxr_importer.upload.no_file', $message, $file );
		}

		$this->id = (int) $file['id'];
		return true;
	}

	/**
	 * Handle an async upload.
	 *
	 * Triggers on `async-upload.php?action=wxr-import-upload` to handle
	 * Plupload requests from the importer.
	 */
	public function handle_async_upload() {
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		send_nosniff_header();
		nocache_headers();

		check_ajax_referer( 'wxr-import-upload' );

		/*
		 * This function does not use wp_send_json_success() / wp_send_json_error()
		 * as the html4 Plupload handler requires a text/html content-type for older IE.
		 * See https://core.trac.wordpress.org/ticket/31037
		 */

		$filename = wp_unslash( $_FILES['import']['name'] );
		$filename = sanitize_file_name( $filename );

		if ( ! current_user_can( 'upload_files' ) ) {
			echo wp_json_encode( array(
				'success' => false,
				'data'    => array(
					'message'  => __( 'You do not have permission to upload files.' ),
					'filename' => $filename,
				),
			) );

			exit;
		}

		$file = wp_import_handle_upload();
		if ( is_wp_error( $file ) ) {
			echo wp_json_encode( array(
				'success' => false,
				'data'    => array(
					'message'  => $file->get_error_message(),
					'filename' => $filename,
				),
			) );

			wp_die();
		}

		$attachment = wp_prepare_attachment_for_js( $file['id'] );
		if ( ! $attachment ) {
			exit;
		}

		echo wp_json_encode( array(
			'success' => true,
			'data'    => $attachment,
		) );

		exit;
	}

	/**
	 * Handle a WXR file selected from the media browser.
	 *
	 * @param int|string $id Media item to import from.
	 * @return bool|WP_Error True on success, error object otherwise.
	 */
	protected function handle_select( $id ) {
		if ( ! is_numeric( $id ) || intval( $id ) < 1 ) {
			return new WP_Error(
				'wxr_importer.upload.invalid_id',
				__( 'Invalid media item ID.', 'wordpress-importer' ),
				compact( 'id' )
			);
		}

		$id = (int) $id;

		$attachment = get_post( $id );
		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return new WP_Error(
				'wxr_importer.upload.invalid_id',
				__( 'Invalid media item ID.', 'wordpress-importer' ),
				compact( 'id', 'attachment' )
			);
		}

		if ( ! current_user_can( 'read_post', $attachment->ID ) ) {
			return new WP_Error(
				'wxr_importer.upload.sorry_dave',
				__( 'You cannot access the selected media item.', 'wordpress-importer' ),
				compact( 'id', 'attachment' )
			);
		}

		$this->id = $id;
		return true;
	}

	/**
	 * Get preliminary data for an import file.
	 *
	 * This is a quick pre-parse to verify the file and grab authors from it.
	 *
	 * @param int $id Media item ID.
	 * @return WXR_Import_Info|WP_Error Import info instance on success, error otherwise.
	 */
	protected function get_data_for_attachment( $id ) {
		$existing = get_post_meta( $id, '_wxr_import_info' );
		if ( ! empty( $existing ) ) {
			$data = $existing[0];
			$this->authors = $data->users;
			$this->version = $data->version;
			return $data;
		}

		$file = get_attached_file( $id );

		$importer = $this->get_importer();
		$data = $importer->get_preliminary_information( $file );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Cache the information on the upload
		if ( ! update_post_meta( $id, '_wxr_import_info', $data ) ) {
			return new WP_Error(
				'wxr_importer.upload.failed_save_meta',
				__( 'Could not cache information on the import.', 'wordpress-importer' ),
				compact( 'id' )
			);
		}

		$this->authors = $data->users;
		$this->version = $data->version;

		return $data;
	}

	/**
	 * Display the actual import step.
	 */
	protected function display_import_step() {
		$args = wp_unslash( $_POST );
		if ( ! isset( $args['import_id'] ) ) {
			// Missing import ID.
			$error = new WP_Error( 'wxr_importer.import.missing_id', __( 'Missing import file ID from request.', 'wordpress-importer' ) );
			$this->display_error( $error );
			return;
		}

		// Check the nonce.
		check_admin_referer( sprintf( 'wxr.import:%d', (int) $args['import_id'] ) );

		$this->id = (int) $args['import_id'];
		$file = get_attached_file( $this->id );

		$mapping = $this->get_author_mapping( $args );
		$fetch_attachments = ( ! empty( $args['fetch_attachments'] ) && $this->allow_fetch_attachments() );

		// Set our settings
		$settings = compact( 'mapping', 'fetch_attachments' );
		update_post_meta( $this->id, '_wxr_import_settings', $settings );

		// Time to run the import!
		set_time_limit( 0 );

		// Ensure we're not buffered.
		wp_ob_end_flush_all();
		flush();

		$data = get_post_meta( $this->id, '_wxr_import_info', true );
		require __DIR__ . '/templates/import.php';
	}

	/**
	 * Run an import, and send an event-stream response.
	 *
	 * Streams logs and success messages to the browser to allow live status
	 * and updates.
	 */
	public function stream_import() {
		// Turn off PHP output compression
		$previous = error_reporting( error_reporting() ^ E_WARNING );
		ini_set( 'output_buffering', 'off' );
		ini_set( 'zlib.output_compression', false );
		error_reporting( $previous );

		if ( $GLOBALS['is_nginx'] ) {
			// Setting this header instructs Nginx to disable fastcgi_buffering
			// and disable gzip for this request.
			header( 'X-Accel-Buffering: no' );
			header( 'Content-Encoding: none' );
		}

		// Start the event stream.
		header( 'Content-Type: text/event-stream' );

		$this->id = wp_unslash( (int) $_REQUEST['id'] );
		$settings = get_post_meta( $this->id, '_wxr_import_settings', true );
		if ( empty( $settings ) ) {
			// Tell the browser to stop reconnecting.
			status_header( 204 );
			exit;
		}

		// 2KB padding for IE
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n";

		// Time to run the import!
		set_time_limit( 0 );

		// Ensure we're not buffered.
		wp_ob_end_flush_all();
		flush();

		$mapping = $settings['mapping'];
		$this->fetch_attachments = (bool) $settings['fetch_attachments'];

		$importer = $this->get_importer();
		if ( ! empty( $mapping['mapping'] ) ) {
			$importer->set_user_mapping( $mapping['mapping'] );
		}
		if ( ! empty( $mapping['slug_overrides'] ) ) {
			$importer->set_user_slug_overrides( $mapping['slug_overrides'] );
		}

		// Are we allowed to create users?
		if ( ! $this->allow_create_users() ) {
			add_filter( 'wxr_importer.pre_process.user', '__return_null' );
		}

		// Keep track of our progress
		add_action( 'wxr_importer.processed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_failed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_already_imported.post', array( $this, 'already_imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_skipped.post', array( $this, 'already_imported_post' ), 10, 2 );
		add_action( 'wxr_importer.processed.comment', array( $this, 'imported_comment' ) );
		add_action( 'wxr_importer.process_already_imported.comment', array( $this, 'imported_comment' ) );
		add_action( 'wxr_importer.processed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.process_failed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.process_already_imported.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.processed.user', array( $this, 'imported_user' ) );
		add_action( 'wxr_importer.process_failed.user', array( $this, 'imported_user' ) );

		// Clean up some memory
		unset( $settings );

		// Flush once more.
		flush();

		$file = get_attached_file( $this->id );
		$err = $importer->import( $file );

		// Remove the settings to stop future reconnects.
		delete_post_meta( $this->id, '_wxr_import_settings' );

		// Let the browser know we're done.
		$complete = array(
			'action' => 'complete',
			'error' => false,
		);
		if ( is_wp_error( $err ) ) {
			$complete['error'] = $err->get_error_message();
		}

		$this->emit_sse_message( $complete );
		exit;
	}

	/**
	 * Get the importer instance.
	 *
	 * @return WXR_Importer
	 */
	protected function get_importer() {
		$importer = new WXR_Importer( $this->get_import_options() );
		$logger = new WP_Importer_Logger_ServerSentEvents();
		$importer->set_logger( $logger );

		return $importer;
	}

	/**
	 * Get options for the importer.
	 *
	 * @return array Options to pass to WXR_Importer::__construct
	 */
	protected function get_import_options() {
		$options = array(
			'fetch_attachments' => $this->fetch_attachments,
			'default_author'    => get_current_user_id(),
		);

		/**
		 * Filter the importer options used in the admin UI.
		 *
		 * @param array $options Options to pass to WXR_Importer::__construct
		 */
		return apply_filters( 'wxr_importer.admin.import_options', $options );
	}

	/**
	 * Display import options for an individual author. That is, either create
	 * a new user based on import info or map to an existing user
	 *
	 * @param int $index Index for each author in the form
	 * @param array $author Author information, e.g. login, display name, email
	 */
	protected function author_select( $index, $author ) {
		esc_html_e( 'Import author:', 'wordpress-importer' );
		$supports_extras = version_compare( $this->version, '1.0', '>' );

		if ( $supports_extras ) {
			$name = sprintf( '%s (%s)', $author['display_name'], $author['user_login'] );
		} else {
			$name = $author['display_name'];
		}
		echo ' <strong>' . esc_html( $name ) . '</strong><br />';

		if ( $supports_extras ) {
			echo '<div style="margin-left:18px">';
		}

		$create_users = $this->allow_create_users();
		if ( $create_users ) {
			if ( ! $supports_extras ) {
				esc_html_e( 'or create new user with login name:', 'wordpress-importer' );
				$value = '';
			} else {
				esc_html_e( 'as a new user:', 'wordpress-importer' );
				$value = sanitize_user( $author['user_login'], true );
			}

			printf(
				' <input type="text" name="user_new[%d]" value="%s" /><br />',
				$index,
				esc_attr( $value )
			);
		}

		if ( ! $create_users && $supports_extras ) {
			esc_html_e( 'assign posts to an existing user:', 'wordpress-importer' );
		} else {
			esc_html_e( 'or assign posts to an existing user:', 'wordpress-importer' );
		}

		wp_dropdown_users( array(
			'name' => sprintf( 'user_map[%d]', $index ),
			'multi' => true,
			'show_option_all' => __( '- Select -', 'wordpress-importer' ),
		));

		printf(
			'<input type="hidden" name="imported_authors[%d]" value="%s" />',
			(int) $index,
			esc_attr( $author['user_login'] )
		);

		// Keep the old ID for when we want to remap
		if ( isset( $author['ID'] ) ) {
			printf(
				'<input type="hidden" name="imported_author_ids[%d]" value="%d" />',
				(int) $index,
				esc_attr( $author['ID'] )
			);
		}

		if ( $supports_extras ) {
			echo '</div>';
		}
	}

	/**
	 * Decide whether or not the importer should attempt to download attachment files.
	 * Default is true, can be filtered via import_allow_fetch_attachments. The choice
	 * made at the import options screen must also be true, false here hides that checkbox.
	 *
	 * @return bool True if downloading attachments is allowed
	 */
	protected function allow_fetch_attachments() {
		return apply_filters( 'import_allow_fetch_attachments', true );
	}

	/**
	 * Decide whether or not the importer is allowed to create users.
	 * Default is true, can be filtered via import_allow_create_users
	 *
	 * @return bool True if creating users is allowed
	 */
	protected function allow_create_users() {
		return apply_filters( 'import_allow_create_users', true );
	}

	/**
	 * Get mapping data from request data.
	 *
	 * Parses form request data into an internally usable mapping format.
	 *
	 * @param array $args Raw (UNSLASHED) POST data to parse.
	 * @return array Map containing `mapping` and `slug_overrides` keys.
	 */
	protected function get_author_mapping( $args ) {
		if ( ! isset( $args['imported_authors'] ) ) {
			return array(
				'mapping'        => array(),
				'slug_overrides' => array(),
			);
		}

		$map        = isset( $args['user_map'] ) ? (array) $args['user_map'] : array();
		$new_users  = isset( $args['user_new'] ) ? $args['user_new'] : array();
		$old_ids    = isset( $args['imported_author_ids'] ) ? (array) $args['imported_author_ids'] : array();

		// Store the actual map.
		$mapping = array();
		$slug_overrides = array();

		foreach ( (array) $args['imported_authors'] as $i => $old_login ) {
			$old_id = isset( $old_ids[ $i ] ) ? (int) $old_ids[ $i ] : false;

			if ( ! empty( $map[ $i ] ) ) {
				$user = get_user_by( 'id', (int) $map[ $i ] );

				if ( isset( $user->ID ) ) {
					$mapping[] = array(
						'old_slug' => $old_login,
						'old_id'   => $old_id,
						'new_id'   => $user->ID,
					);
				}
			} elseif ( ! empty( $new_users[ $i ] ) ) {
				if ( $new_users[ $i ] !== $old_login ) {
					$slug_overrides[ $old_login ] = $new_users[ $i ];
				}
			}
		}

		return compact( 'mapping', 'slug_overrides' );
	}

	/**
	 * Emit a Server-Sent Events message.
	 *
	 * @param mixed $data Data to be JSON-encoded and sent in the message.
	 */
	protected function emit_sse_message( $data ) {
		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";

		// Extra padding.
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n";

		flush();
	}

	/**
	 * Send message when a post has been imported.
	 *
	 * @param int $id Post ID.
	 * @param array $data Post data saved to the DB.
	 */
	public function imported_post( $id, $data ) {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => ( $data['post_type'] === 'attachment' ) ? 'media' : 'posts',
			'delta'  => 1,
		));
	}

	/**
	 * Send message when a post is marked as already imported.
	 *
	 * @param array $data Post data saved to the DB.
	 */
	public function already_imported_post( $data ) {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => ( $data['post_type'] === 'attachment' ) ? 'media' : 'posts',
			'delta'  => 1,
		));
	}

	/**
	 * Send message when a comment has been imported.
	 */
	public function imported_comment() {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => 'comments',
			'delta'  => 1,
		));
	}

	/**
	 * Send message when a term has been imported.
	 */
	public function imported_term() {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => 'terms',
			'delta'  => 1,
		));
	}

	/**
	 * Send message when a user has been imported.
	 */
	public function imported_user() {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => 'users',
			'delta'  => 1,
		));
	}
}
