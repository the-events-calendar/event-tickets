var tribe_details = tribe_details || {};

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
		if(! trigger || ! trigger.classList.contains( 'tribe-block__tickets__item__details__summary' ) ){
			console.log('trigger fail');
			console.log(trigger);
			return;
		}

		var target_selector = trigger.getAttribute('aria-controls');
		var target = document.getElementById( target_selector );

		if ( ! target ) {
			console.log('target fail');
			console.log(target);
			return;
		}

		event.preventDefault();
		// Let our CSS handle the hide/show. Also allows us to make it responsive.
		if ( trigger.classList.contains( 'tribe__details--open' ) ) {
			trigger.classList.remove( 'tribe__details--open' );
			target.classList.remove( 'tribe__details--open' );
		} else {
			trigger.classList.add( 'tribe__details--open' );
			target.classList.add( 'tribe__details--open' );
		}
	}

	window.addEventListener( 'load', function() {
		var detailsElems = document.querySelectorAll( '.tribe-block__tickets__item__details__summary' );

		// details element not present
		if ( ! detailsElems.length ) {
			this.console.log("can't find 'em");
			return; }

		obj.init( detailsElems );
	});

} )( tribe_details );
