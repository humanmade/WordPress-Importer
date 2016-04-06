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
	}

	/**
	 * Show an update notice in the importer header.
	 */
	public function show_updates_in_header() {
		// Check for updates too.
		$updates = get_plugin_updates();
		$basename = plugin_basename( __FILE__ );
		if ( empty( $updates[$basename] ) ) {
			return;
		}

		$message = sprintf(
			esc_html__( 'A new version of this importer is available. Please update to version %s to ensure compatibility with newer export files.', 'wordpress-importer' ),
			$updates[$basename]->update->new_version
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
		echo '<p><strong>' . __( 'Sorry, there has been an error.', 'wordpress-importer' ) . '</strong><br />';
		echo $err->get_error_message();
		echo '</p>';
		printf(
			'<p><a class="button" href="%s">Try Again</a></p>',
			esc_url( $this->get_url( $step ) )
		);
	}

	/**
	 * Render the import page.
	 */
	public function dispatch() {
		require __DIR__ . '/templates/header.php';

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
				check_admin_referer( 'import-wordpress' );
				$this->display_import_step();
				break;
		}

		require __DIR__ . '/templates/footer.php';
	}

	/**
	 * Display introductory text and file upload form
	 */
	protected function display_intro_step() {
		require __DIR__ . '/templates/intro.php';
	}

	/**
	 * Display the author picker (or upload errors).
	 */
	protected function display_author_step() {
		if ( isset( $_REQUEST['id'] ) ) {
			$err = $this->handle_select( $_REQUEST['id'] );
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
				__( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'wordpress-importer' ),
				esc_html( $file['file'] )
			);
			return new WP_Error( 'wxr_importer.upload.no_file', $message, $file );
		}

		$this->id = (int) $file['id'];
		return true;
	}

	/**
	 * Handle a WXR file selected from the media browser.
	 *
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

	protected function get_data_for_attachment( $id ) {
		$file = get_attached_file( $id );

		$importer = $this->get_importer();
		$data = $importer->get_preliminary_information( $file );
		if ( is_wp_error( $data ) ) {
			return $data;
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

		$this->fetch_attachments = ( ! empty( $args['fetch_attachments'] ) && $this->allow_fetch_attachments() );
		$this->id = (int) $args['import_id'];
		$file = get_attached_file( $this->id );

		$importer = $this->get_importer();

		$mapping = $this->get_author_mapping( $args );
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

		// Time to run the import!
		set_time_limit(0);

		$err = $importer->import( $file );

		// Clean up, we're done here.
		wp_import_cleanup( $this->id );

		if ( is_wp_error( $err ) ) {
			$this->display_error( $err );
			return;
		}

		echo '<p>' . __( 'All done.', 'wordpress-importer' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!', 'wordpress-importer' ) . '</a>' . '</p>';
		echo '<p>' . __( 'Remember to update the passwords and roles of imported users.', 'wordpress-importer' ) . '</p>';
	}

	/**
	 * Get the importer instance.
	 *
	 * @return WXR_Importer
	 */
	protected function get_importer() {
		$importer = new WXR_Importer( $this->get_import_options() );
		$logger = new WP_Importer_Logger_HTML();
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
	 * @param int $n Index for each author in the form
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

		if ( $supports_extras )
			echo '<div style="margin-left:18px">';

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
			'show_option_all' => __( '- Select -', 'wordpress-importer' )
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

		if ( $supports_extras )
			echo '</div>';
	}

	/**
	 * Retrieve authors from parsed WXR data
	 *
	 * Uses the provided author information from WXR 1.1 files
	 * or extracts info from each post for WXR 1.0 files
	 *
	 * @param array $import_data Data returned by a WXR parser
	 */
	function get_authors_from_import( $import_data ) {
		if ( ! empty( $import_data['authors'] ) ) {
			$this->authors = $import_data['authors'];
		// no author information, grab it from the posts
		} else {
			foreach ( $import_data['posts'] as $post ) {
				$login = sanitize_user( $post['post_author'], true );
				if ( empty( $login ) ) {
					$this->logger->warning( sprintf(
						__( 'Failed to import author %s. Their posts will be attributed to the current user.', 'wordpress-importer' ),
						$post['post_author']
					) );
					continue;
				}

				if ( ! isset($this->authors[$login]) )
					$this->authors[$login] = array(
						'author_login' => $login,
						'author_display_name' => $post['post_author']
					);
			}
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
	 * Map old author logins to local user IDs based on decisions made
	 * in import options form. Can map to an existing user, create a new user
	 * or falls back to the current user in case of error with either of the previous
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
			$old_id = isset( $old_ids[$i] ) ? (int) $old_ids[$i] : false;

			if ( isset( $map[$i] ) ) {
				$user = get_user_by( 'id', (int) $map[$i] );

				if ( isset( $user->ID ) ) {
					$mapping[] = array(
						'old_slug' => $old_login,
						'old_id'   => $old_id,
						'new_id'   => $user->ID,
					);
				}
			} elseif ( isset( $new_users[ $i ] ) ) {
				if ( $new_users[ $i ] !== $old_login ) {
					$slug_overrides[ $old_login ] = $new_users[ $i ];
				}
			}
		}

		return compact( 'mapping', 'slug_overrides' );
	}
}
