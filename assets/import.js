(function ($) {
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
			total = parseInt( total, 10 );
			if ( 0 === total || isNaN( total ) ) {
				total = 1;
			}
			var percent = parseInt( complete, 10 ) / total;
			document.getElementById( 'progress-' + type ).innerHTML = Math.round( percent * 100 ) + '%';
			document.getElementById( 'progressbar-' + type ).value = percent * 100;
		},
		render: function () {
			var types = Object.keys( this.complete );
			var complete = 0;
			var total = 0;

			for (var i = types.length - 1; i >= 0; i--) {
				var type = types[i];
				this.updateProgress( type, this.complete[ type ], this.data.count[ type ] );

				complete += this.complete[ type ];
				total += this.data.count[ type ];
			}

			this.updateProgress( 'total', complete, total );
		}
	};
	wxrImport.data = wxrImportData;
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
				var import_status_msg = jQuery('#import-status-message');
				import_status_msg.text( wxrImport.data.strings.complete );
				import_status_msg.removeClass('notice-info');
				import_status_msg.addClass('notice-success');
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
})(jQuery);
