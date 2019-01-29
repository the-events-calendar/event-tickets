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

	obj.hasChanges = {};

	obj.selector = {
		container : '.tribe-block__tickets__registration__event',
		fields : '.tribe-block__tickets__item__attendee__fields',
		fieldsError : '.tribe-block__tickets__item__attendee__fields__error',
		fieldsErrorRequired: '.tribe-block__tickets__item__attendee__fields__error--required',
		fieldsErrorAjax: '.tribe-block__tickets__item__attendee__fields__error--ajax',
		loader: '.tribe-block__tickets__item__attendee__fields__loader',
		form : '.tribe-block__tickets__item__attendee__fields__form',
		toggler : '.tribe-block__tickets__registration__toggle__handler',
		status : '.tribe-block__tickets__registration__status',
		field : {
			text : '.tribe-block__tickets__item__attendee__field__text',
			checkbox : '.tribe-block__tickets__item__attendee__field__checkbox',
			select : '.tribe-block__tickets__item__attendee__field__select',
			radio : '.tribe-block__tickets__item__attendee__field__radio',
		},
		checkout : '.tribe-block__tickets__registration__checkout',
		checkoutButton: '.tribe-block__tickets__registration__checkout__submit'
	};

	var $tribe_registration = $( obj.selector.container );

	// Bail if there are no tickets on the current event/page/post
	if ( 0 === $tribe_registration.length ) {
		return;
	}

	/**
	 * Handle the toggle for each event
	 *
	 * @since 4.9
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
	 * Check if the required fields have data
	 *
	 * @since 4.9
	 *
	 * @return void
	*/
	obj.validateEventAttendees = function( $form ) {
		var is_valid = true;
		var $fields = $form.find( '.tribe-tickets-meta-required' );

		$fields.each( function() {
			var $field = $( this );
			var val = '';

			if (
				$field.is( obj.selector.field.radio )
				|| $field.is( obj.selector.field.checkbox )
			) {
				val = $field.find( 'input:checked' ).length ? 'checked' : '';
			} else if ( $field.is( obj.selector.field.select ) ) {
				val = $field.find( 'select' ).val();
			} else {
				val = $field.find( 'input, textarea' ).val().trim();
			}

			if ( 0 === val.length ) {
				is_valid = false;
			}

		});

		return is_valid;
	};

	/**
	 * Update container status to complete
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.updateStatusToComplete = function( $event ) {
		$event.find( obj.selector.status ).removeClass( 'incomplete' );
		$event.find( obj.selector.status ).find( 'i' ).removeClass( 'dashicons-edit' );
		$event.find( obj.selector.status ).find( 'i' ).addClass( 'dashicons-yes' );
	};

	/**
	 * Update container status to incomplete
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.updateStatusToIncomplete = function( $event ) {
		$event.find( obj.selector.status ).addClass( 'incomplete' );
		$event.find( obj.selector.status ).find( 'i' ).addClass( 'dashicons-edit' );
		$event.find( obj.selector.status ).find( 'i' ).removeClass( 'dashicons-yes' );
	};

	/**
	 * Handle save attendees info form submission.
	 * Display a message if there are required fields missing.
	 *
	 * @since 4.9
	 *
	 * @return void
	*/
	obj.handleSaveSubmission = function( e ) {
		e.preventDefault();
		var $form = $( this );
		var $fields = $form.closest( obj.selector.fields );
		var $event = $fields.closest( obj.selector.container );

		// hide all errors
		$fields.find( obj.selector.fieldsErrorRequired ).hide();
		$fields.find( obj.selector.fieldsErrorAjax ).hide();

		if ( ! obj.validateEventAttendees( $form ) ) {
			$fields.find( obj.selector.fieldsErrorRequired ).show();
			obj.updateStatusToIncomplete( $event )

			$( 'html, body').animate( {
				scrollTop: $fields.offset().top
			}, 300 );
		} else {
			$fields.find( obj.selector.loader ).show();

			var eventId = $event.data( 'event-id' );
			var params = $form.serializeArray();
			params.push( { name: 'event_id', value: eventId } );
			params.push( { name: 'action', value: 'tribe-tickets-save-attendee-info' } );

			$.post(
				TribeTicketsPlus.ajaxurl,
				params,
				function( response ) {
					if ( response.success ) {
						obj.updateStatusToComplete( $event )
						obj.hasChanges[ eventId ] = false;

						if ( response.data.meta_up_to_date ) {
							$( obj.selector.checkoutButton ).removeAttr( 'disabled' );
						}
					} else {
						$fields.find( obj.selector.fieldsErrorAjax ).show();
					}

					$fields.find( obj.selector.loader ).hide();
				}
			)
		}
	};

	/**
	 * Handle checkout form submission.
	 * Display a confirm if there are any changes to the attendee info that have not been saved
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.handleCheckoutSubmission = function( e ) {
		var eventIds = Object.keys( obj.hasChanges );
		var hasChanges = eventIds.reduce( function( hasChanges, eventId ) {
			return hasChanges || obj.hasChanges[ eventId ];
		}, false );

		if ( hasChanges && ! confirm( tribe_l10n_datatables.registration_prompt ) ) {
			e.preventDefault();
			return;
		}
	};

	/**
	 * Sets hasChanges flag to true for given eventId
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.setHasChanges = function( eventId ) {
		return function() {
			obj.hasChanges[ eventId ] = true;
		};
	};

	/**
	 * Bind event handlers to each form field
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.bindFormFields = function( $event ) {
		// set up hasChanges flag for event
		var eventId = $event.data( 'event-id' );
		obj.hasChanges[ eventId ] = false;

		var $fields = [
			$event.find( obj.selector.field.text ),
			$event.find( obj.selector.field.checkbox ),
			$event.find( obj.selector.field.radio ),
			$event.find( obj.selector.field.select ),
		];

		$fields.forEach( function( $field ) {
			var $formElement;

			if (
				$field.is( obj.selector.field.radio )
				|| $field.is( obj.selector.field.checkbox )
			) {
				$formElement = $field.find( 'input' );
			} else if ( $field.is( obj.selector.field.select ) ) {
				$formElement = $field.find( 'select' );
			} else {
				$formElement = $field.find( 'input, textarea' );
			}

			$formElement.change( obj.setHasChanges( eventId ) );
		} );
	};

	/**
	 * Bind event handlers to checkout form
	 */
	obj.bindCheckout = function() {
		$( obj.selector.checkout ).submit( obj.handleCheckoutSubmission );
	};

	/**
	 * Bind event handlers
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.bindEvents = function() {
		obj.bindCheckout();
	};

	/**
	 * Init containers for each event
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.initContainers = function() {
		$( obj.selector.container ).each( function() {
			var $event = $( this );
			var allRequired = obj.validateEventAttendees( $event );

			allRequired
				? obj.updateStatusToComplete( $event )
				: obj.updateStatusToIncomplete( $event );

			// bind submission handler to each form
			var $form = $event.find( obj.selector.form );
			$( $form ).on( 'submit', obj.handleSaveSubmission );

			// bind form fields to update hasChanges flag
			obj.bindFormFields( $event );
		});
	};

	/**
	 * Init the page, set a flag for those events that need to fill inputs
	 * Toggle down those who are ready
	 *
	 * @since 4.9
	 *
	 * @return void
	*/
	obj.initPage = function() {
		obj.initContainers();
		obj.bindEvents();
	};

	/**
	 * Init the tickets registration script
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.init = function() {
		obj.initPage();
	}

	obj.init();


})( jQuery, tribe.tickets.registration );
