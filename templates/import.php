<?php
/**
 * Page for the actual import step.
 */

$this->render_header();
?>
<div class="welcome-panel">
	<div class="welcome-panel-content">
		<h2><?php esc_html_e( 'Step 3: Importing', 'wordpress-importer' ) ?></h2>
		<p id="import-status-message"><?php esc_html_e( 'Now importing.', 'wordpress-importer' ) ?></p>

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
						<?php echo esc_html( sprintf(
							_n( '%d post (including CPTs)', '%d posts (including CPTs)', $data->post_count, 'wordpress-importer' ),
							$data->post_count
						)) ?>
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
						<?php echo esc_html( sprintf(
							_n( '%d media item', '%d media items', $data->media_count, 'wordpress-importer' ),
							$data->media_count
						)) ?>
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
						<?php echo esc_html( sprintf(
							_n( '%d user', '%d users', count( $data->users ), 'wordpress-importer' ),
							count( $data->users )
						)) ?>
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
						<?php echo esc_html( sprintf(
							_n( '%d comment', '%d comments', $data->comment_count, 'wordpress-importer' ),
							$data->comment_count
						)) ?>
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
						<?php echo esc_html( sprintf(
							_n( '%d term', '%d terms', $data->term_count, 'wordpress-importer' ),
							$data->term_count
						)) ?>
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
				<span class="dashicons dashicons-yes"></span>
			</div>
		</div>
	</div>
</div>

<table id="import-log" class="widefat">
	<thead>
		<tr>
			<th>Type</th>
			<th>Message</th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>

<?php
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
?>
<script>
var wxrImport = {
	complete: {
		posts: 0,
		media: 0,
		users: 0,
		comments: 0,
		terms: 0,
	},

	updateDelta: function (type, delta) {
		this.complete[ type ] += delta;

		var self = this;
		requestAnimationFrame(function () {
			self.render();
		});
	},
	updateProgress: function ( type, complete, total ) {
		var text = complete + '/' + total;
		document.getElementById( 'completed-' + type ).innerHTML = text;
		var percent = parseInt( complete ) / parseInt( total );
		document.getElementById( 'progress-' + type ).innerHTML = Math.round( percent * 100 ) + '%';
		document.getElementById( 'progressbar-' + type ).value = percent * 100;
	},
	render: function () {
		var types = Object.keys( this.complete );
		var complete = 0;
		var total = 0;
		console.log(this);
		for (var i = types.length - 1; i >= 0; i--) {
			var type = types[i];
			this.updateProgress( type, this.complete[ type ], this.data.count[ type ] );

			complete += this.complete[ type ];
			total += this.data.count[ type ];
		}

		this.updateProgress( 'total', complete, total );
	}
};
wxrImport.data = <?php echo wp_json_encode( $script_data ) ?>;
wxrImport.render();


var evtSource = new EventSource( wxrImport.data.url );
evtSource.onmessage = function ( message ) {
	var data = JSON.parse( message.data );
	switch ( data.action ) {
		case 'updateDelta':
			wxrImport.updateDelta( data.type, data.delta );
			break;

		case 'complete':
			evtSource.close();
			jQuery('#import-status-message').text( wxrImport.data.strings.complete );
			break;
	}
};
evtSource.addEventListener( 'log', function ( message ) {
	var data = JSON.parse( message.data );
	var row = document.createElement('tr');
	var level = document.createElement( 'td' );
	level.appendChild( document.createTextNode( data.level ) );
	row.appendChild( level );

	var message = document.createElement( 'td' );
	message.appendChild( document.createTextNode( data.message ) );
	row.appendChild( message );

	jQuery('#import-log').append( row );
});
</script>
<style>
.import-status {
	width: 100%;

	font-size: 14px;
	line-height: 16px;
	margin-bottom: 1em;
}
.import-status thead th {
	width: 32%;
	text-align: left;
	font-size: 16px;
	padding-bottom: 1em;
}
.import-status thead th:first-child {
	width: 36%;
}

.import-status th,
.import-status td {
	padding: 0 0 8px;
	margin-bottom: 6px;
}

#import-log tbody {
	max-height: 40em;
}
.import-status-indicator {
	margin-bottom: 1em;
}
.import-status-indicator progress {
	width: 100%;
}
.import-status-indicator .status {
	text-align: center;
}
.import-status-indicator .status .dashicons {
	color: #46B450;
	font-size: 3rem;
	height: auto;
	width: auto;
}
#completed-total {
	display: none;
}
</style>
<?php

$this->render_footer();
