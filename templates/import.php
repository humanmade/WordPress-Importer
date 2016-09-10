<?php
/**
 * Page for the actual import step.
 */

$args = array(
	'action' => 'wxr-import',
	'id'     => $this->id,
);
$url = add_query_arg( urlencode_deep( $args ), admin_url( 'admin-ajax.php' ) );

$script_data = array(
	'count' => array(
		'posts' => $data->post_count,
		'media' => $data->media_count,
		'users' => count( $data->users ),
		'comments' => $data->comment_count,
		'terms' => $data->term_count,
	),
	'url' => $url,
	'strings' => array(
		'complete' => __( 'Import complete!', 'wordpress-importer' ),
	),
);

$url = plugins_url( 'assets/import.js', dirname( __FILE__ ) );
wp_enqueue_script( 'wxr-importer-import', $url, array( 'jquery' ), '20160909', true );
wp_localize_script( 'wxr-importer-import', 'wxrImportData', $script_data );

wp_enqueue_style( 'wxr-importer-import', plugins_url( 'assets/import.css', dirname( __FILE__ ) ), array(), '20160909' );

$this->render_header();
?>
<div class="welcome-panel">
	<div class="welcome-panel-content">
		<h2><?php esc_html_e( 'Step 3: Importing', 'wordpress-importer' ) ?></h2>
		<div id="import-status-message" class="notice notice-info"><?php esc_html_e( 'Now importing.', 'wordpress-importer' ) ?></div>

		<table class="import-status">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Import Summary', 'wordpress-importer' ) ?></th>
					<th><?php esc_html_e( 'Completed', 'wordpress-importer' ) ?></th>
					<th><?php esc_html_e( 'Progress', 'wordpress-importer' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<span class="dashicons dashicons-admin-post"></span>
						<?php
						echo esc_html( sprintf(
							_n( '%d post (including CPTs)', '%d posts (including CPTs)', $data->post_count, 'wordpress-importer' ),
							$data->post_count
						));
						?>
					</td>
					<td>
						<span id="completed-posts" class="completed">0/0</span>
					</td>
					<td>
						<progress id="progressbar-posts" max="100" value="0"></progress>
						<span id="progress-posts" class="progress">0%</span>
					</td>
				</tr>
				<tr>
					<td>
						<span class="dashicons dashicons-admin-media"></span>
						<?php
						echo esc_html( sprintf(
							_n( '%d media item', '%d media items', $data->media_count, 'wordpress-importer' ),
							$data->media_count
						));
						?>
					</td>
					<td>
						<span id="completed-media" class="completed">0/0</span>
					</td>
					<td>
						<progress id="progressbar-media" max="100" value="0"></progress>
						<span id="progress-media" class="progress">0%</span>
					</td>
				</tr>

				<tr>
					<td>
						<span class="dashicons dashicons-admin-users"></span>
						<?php
						echo esc_html( sprintf(
							_n( '%d user', '%d users', count( $data->users ), 'wordpress-importer' ),
							count( $data->users )
						));
						?>
					</td>
					<td>
						<span id="completed-users" class="completed">0/0</span>
					</td>
					<td>
						<progress id="progressbar-users" max="100" value="0"></progress>
						<span id="progress-users" class="progress">0%</span>
					</td>
				</tr>

				<tr>
					<td>
						<span class="dashicons dashicons-admin-comments"></span>
						<?php
						echo esc_html( sprintf(
							_n( '%d comment', '%d comments', $data->comment_count, 'wordpress-importer' ),
							$data->comment_count
						));
						?>
					</td>
					<td>
						<span id="completed-comments" class="completed">0/0</span>
					</td>
					<td>
						<progress id="progressbar-comments" max="100" value="0"></progress>
						<span id="progress-comments" class="progress">0%</span>
					</td>
				</tr>

				<tr>
					<td>
						<span class="dashicons dashicons-category"></span>
						<?php
						echo esc_html( sprintf(
							_n( '%d term', '%d terms', $data->term_count, 'wordpress-importer' ),
							$data->term_count
						));
						?>
					</td>
					<td>
						<span id="completed-terms" class="completed">0/0</span>
					</td>
					<td>
						<progress id="progressbar-terms" max="100" value="0"></progress>
						<span id="progress-terms" class="progress">0%</span>
					</td>
				</tr>
			</tbody>
		</table>

		<div class="import-status-indicator">
			<div class="progress">
				<progress id="progressbar-total" max="100" value="0"></progress>
			</div>
			<div class="status">
				<span id="completed-total" class="completed">0/0</span>
				<span id="progress-total" class="progress">0%</span>
			</div>
		</div>
	</div>
</div>

<table id="import-log" class="widefat">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Type', 'wordpress-importer' ) ?></th>
			<th><?php esc_html_e( 'Message', 'wordpress-importer' ) ?></th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>

<?php

$this->render_footer();
