(function( window, $ ) {
	var $table = $( '.eventtable.ticket_list.eventForm tbody' ),
		enable_width = '400px';

	// For drag-n-drop
	function make_sortable( $element ) {
		$element.sortable({
			cursor: 'move',
			items: 'tr:not(.Tribe__Tickets__RSVP)',
			placeholder: 'ui-state-highlight',
			forcePlaceholderSize: true,
			update: function() {
				data = $(this).sortable( 'toArray', { key: 'order[]', attribute: 'data-ticket-order-id' } );
				console.log( 'data: ' + data);
				document.getElementById( 'tickets_order' ).value = data;
			}
		});
		$element.disableSelection();
		$element.find( '.table-header' ).disableSelection();
		$element.sortable( 'option', 'disabled', false );
	}

	$(document).ready(function () {
		// init if we're not on small screens
		if ( window.matchMedia( '( min-width: 400px )' ).matches ) {
			 make_sortable( $table );
		}

		// disable/init depending on screen size
		$(window).on( 'resize', function() {
			if ( window.matchMedia( '( min-width: 400px )' ).matches ) {
				if ( ! $( $table ).hasClass( 'ui-sortable' ) ) {
					make_sortable( $table );
				}
			} else {
				if ( $( $table ).hasClass( 'ui-sortable' ) ) {
					$( $table ).sortable( 'option', 'disabled', true );
				}
			}
		});
	});



})( window, jQuery );
