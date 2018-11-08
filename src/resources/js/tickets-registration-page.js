// For compatibility purposes we add this
if ( 'undefined' === typeof tribe ) {
	tribe = {};
}

if ( 'undefined' === typeof tribe.tickets ) {
	tribe.tickets = {};
}

tribe.tickets.registration = {};

( function( $, obj ) {
	'use strict';

	obj.selector = {
		container : '.tribe-block__tickets__registration__event',
		fields    : '.tribe-block__tickets__item__attendee__fields',
		toggler   : '.tribe-block__tickets__registration__toggle__handler',
		status    : '.tribe-block__tickets__registration__status',
	};

	var $tribe_registration = $( obj.selector.container );

	// Bail if there are no tickets on the current event/page/post
	if ( 0 === $tribe_registration.length ) {
		return;
	}

	/**
	 * Handle the toggle for each event
	 *
	 * @since TBD
	 *
	 * @return void
	*/
	$( obj.selector.container ).on( 'click',
		obj.selector.toggler,
		function( e ) {
			e.preventDefault();

			var $this      = $( this );
			var $event     = $this.closest( obj.selector.container );

			$event.find( obj.selector.fields ).toggle();
			$this.toggleClass( 'open' );

	} );

	/**
	 * Check if the required fiels have data
	 *
	 * @since TBD
	 *
	 * @return void
	*/
	obj.validateEventAttendees = function( $form ) {
		var is_valid = true;
		var $fields = $form.find( '.tribe-tickets-meta-required' );

 		$fields.each( function() {
			var $el = $( this );
			var val = '';
 			if (
 				$el.is( '.tribe-tickets-meta-radio' )
 				|| $el.is( '.tribe-tickets-meta-checkbox' )
 			) {
				val = $el.find( 'input:checked' ).length ? 'checked' : '';
			} else {
				val = $el.find( 'input, select, textarea' ).val().trim();
			}

 			if ( 0 === val.length ) {
				is_valid = false;
			}

		});

 		return is_valid;
	};

	/**
	 * Init the page, set a flag for those events that need to fill inputs
	 * Toggle down those who are ready
	 *
	 * @since TBD
	 *
	 * @return void
	*/
	obj.initPage = function() {

		$( obj.selector.container ).each( function() {
			var $event = $( this );

			allRequired = obj.validateEventAttendees( $event );

			if ( ! allRequired ) {
				$event.find( obj.selector.status ).addClass( 'incomplete' );
			} else {
				$event.find( obj.selector.status ).removeClass( 'incomplete' );
				$event.find( obj.selector.status ).find( 'i' ).removeClass( 'dashicons-edit' );
				$event.find( obj.selector.status ).find( 'i' ).addClass( 'dashicons-yes' );
				$event.find( obj.selector.fields ).toggle();
			}

		});

	}

	/**
	 * Init the tickets registration script
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.init = function() {
		obj.initPage();
	}

	obj.init();


})( jQuery, tribe.tickets.registration );