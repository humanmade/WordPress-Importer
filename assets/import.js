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

		// add row to the table, allowing DataTable to keep rows sorted by log-level
		$( '#import-log' ).dataTable().fnAddData( [data.level, data.message] );
	});

	// sorting/pagination of log messages, using the DataTables jquery plugin
	$( '#import-log' ).dataTable( {
		order: [[ 0, 'desc' ]],
		columns: [
			{ type: 'log-level' },
			{ type: 'string' },
		],
		lengthMenu: [[ 10, 20, 40, -1 ], [ 10, 20, 40, 'All' ]],
		pageLength: 10,
		pagingType: 'full_numbers',
	});
	
	// extend DataTables to allow sorting by log-level
	$.extend( jQuery.fn.dataTableExt.oSort, {
	    'log-level-asc': function( a, b ) {
	    	return log_level_orderby( a, b );
	    },
	    'log-level-desc': function(a,b) {
	    	return - log_level_orderby( a, b );
	    }
	} );

	/**
	 * Ordering by log-level
	 * 
	 * @param a
	 * @param b
	 * @returns -1, 0, 1
	 */
	function log_level_orderby( a, b ) {
		switch ( a ) {
			case 'error':
				switch ( b ) {
					case 'error':
						return 0;
					default:
						return 1;
				}
			case 'warning':
				switch ( b ) {
					case 'error':
						return -1;
					case 'warning':
						return 0;
					default:
						return 1;
				}
			case 'notice':
				switch ( b ) {
					case 'error':
					case 'warning':
						return -1;
					case 'notice':
						return 0;
					default:
						return 1;
				}
			case 'info':
				return -1;
			default:
				return 0;
		}
	}
})(jQuery);
