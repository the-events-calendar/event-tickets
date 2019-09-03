var tribe_ticket_details = tribe_ticket_details || {};

( function( obj ) {
	'use strict';

	obj.init = function( detailsElems ) {
		obj.event_listeners();
	}

	obj.event_listeners = function() {
		// Add keyboard support for enter key.
		document.addEventListener( 'keyup', function( event ) {
			// Just trigger the click so we don't have to copy code.
			if ( 13 === event.keyCode ) { obj.toggle_open( event.target ); }
		} );

		document.addEventListener( 'click', function( event ) {
			obj.toggle_open( event.target );
		} );
	}

	obj.toggle_open = function( trigger ) {
		if(! trigger ){
			return;
		}

		var parent          = trigger.closest( '.tribe-block__tickets__item__details__summary');
		var target_selector = trigger.getAttribute('aria-controls');
		var target          = document.getElementById( target_selector );

		if ( ! target || ! parent ) {
			return;
		}

		event.preventDefault();

		// Let our CSS handle the hide/show. Also allows us to make it responsive.
		if ( parent.classList.contains( 'tribe__details--open' ) ) {
			parent.classList.remove( 'tribe__details--open' );
			target.classList.remove( 'tribe__details--open' );
		} else {
			parent.classList.add( 'tribe__details--open' );
			target.classList.add( 'tribe__details--open' );
		}
	}

	window.addEventListener( 'load', function() {
		var detailsElems = document.querySelectorAll( '.tribe-block__tickets__item__details__summary' );

		// details element not present
		if ( ! detailsElems.length ) {
			return; }

		obj.init( detailsElems );
	});

} )( tribe_ticket_details );
