( function( window, $ ) {
	var $table         = $( document.getElementById( 'tribe_ticket_list_table' ) ).find( ' tbody' );
	var $tribe_tickets = $( document.getElementById( 'tribetickets' ) );

	/**
	* Implemnts jQuery drag-n-drop for the ticket table.
	* Stores order in the #tickets_order field.
	*
	* @param jQuery object $element parent element to make sortable ( var $table above )
	*/
	function make_sortable( $element ) {
		// If we don't have at least 2 sortable items, don't sort.
		if ( 2 > $element.find( 'tr:not(.Tribe__Tickets__RSVP)' ).length ) {
			return;
		}

		$element.sortable({
			cursor: 'move',
			items: 'tr:not(.Tribe__Tickets__RSVP)',
			forcePlaceholderSize: true,
			update: function() {
				data = $(this).sortable( 'toArray', { key: 'order[]', attribute: 'data-ticket-order-id' } );

				// Strip the text .sortable() requires - to reduce thrash later
				for ( i = 0, len = data.length; i < data.length; i++ ) {
					data[i] = data[i].replace( 'order_', '');
				}

				document.getElementById( 'tribe_tickets_order' ).value = data;
			}
		});
		$element.disableSelection();
		$element.find( '.table-header' ).disableSelection();
		$element.sortable( 'option', 'disabled', false );
	}

	function tribe_toggle_sortable() {
		if ( window.matchMedia( '( min-width: 786px )' ).matches ) {
			if ( ! $( $table ).hasClass( 'ui-sortable' ) ) {
				make_sortable( $table );
			} else {
				$( $table ).sortable( "enable" );
			}
		} else {
			if ( $( $table ).hasClass( 'ui-sortable' ) ) {
				$( $table ).sortable( 'disable' );
			}
		}
	}

	function tribe_make_rows_toggleable() {
		if ( window.matchMedia( '( max-width: 782px )' ).matches ) {
			// Toggle list table rows
			$table.on( 'click', '.tribe-toggle-row', function() {
				var $tr = $( this ).closest( 'tr' );
				if ( ! $tr.hasClass( 'is-expanded' ) ) {
					$tr.addClass( 'is-expanded' );
				} else {
					$tr.removeClass( 'is-expanded' );
				}

			});
		}
	}

	$( document ).ready( function () {
		// trigger once at start
		tribe_toggle_sortable();
		tribe_make_rows_toggleable();

		// disable/init depending on screen size
		var maybeSortable = _.debounce( tribe_toggle_sortable, 300 );
		$( window ).resize( maybeSortable );

		$tribe_tickets.on( 'tribe-tickets-refresh-tables', function( data ) {
			$table = $( document.getElementById( 'tribe_ticket_list_table' ) ).find( ' tbody' );
			// trigger on table refresh
			tribe_toggle_sortable();
			tribe_make_rows_toggleable()
		});
	});
})( window, jQuery );
