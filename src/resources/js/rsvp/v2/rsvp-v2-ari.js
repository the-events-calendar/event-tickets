/**
 * RSVP V2 ARI JavaScript
 *
 * Handles Attendee Registration Interface (ARI) for RSVP V2 on the frontend.
 *
 * @since TBD
 */

/* global jQuery, TribeRsvpV2Block, tribe */

/**
 * Makes sure we have all the required levels on the Tribe Object.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets = tribe.tickets || {};
tribe.tickets.rsvp = tribe.tickets.rsvp || {};
tribe.tickets.rsvp.v2 = tribe.tickets.rsvp.v2 || {};

/**
 * Configures RSVP V2 ARI Object in the Global Tribe variable.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets.rsvp.v2.ari = {};

/**
 * Initializes in a Strict env the code that manages the RSVP V2 ARI.
 *
 * @since TBD
 * @param {Object} $   jQuery
 * @param {Object} obj tribe.tickets.rsvp.v2.ari
 * @return {void}
 */
( function ( $, obj ) {
	'use strict';

	const $document = $( document );

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 * @type {Object}
	 */
	obj.selectors = {
		container: '.tribe-tickets__rsvp-v2-wrapper',
		rsvpForm: '.tribe-tickets__rsvp-v2-form',
		guestList: '.tribe-tickets__rsvp-v2-ar-guest-list',
		guestListItem: '.tribe-tickets__rsvp-v2-ar-guest-list-item',
		guestListItemTemplate: '.tribe-tickets__rsvp-v2-ar-guest-list-item-template',
		guestListItemButton: '.tribe-tickets__rsvp-v2-ar-guest-list-item-button',
		guestListItemButtonInactive: '.tribe-tickets__rsvp-v2-ar-guest-list-item-button--inactive',
		guestFormWrapper: '.tribe-tickets__rsvp-v2-ar-form',
		guestFormFields: '.tribe-tickets__rsvp-v2-ar-form-guest',
		guestFormFieldsError: '.tribe-tickets__form-message--error',
		guestFormFieldsTemplate: '.tribe-tickets__rsvp-v2-ar-form-guest-template',
		addGuestButton: '.tribe-tickets__rsvp-v2-ar-quantity-input-number--plus',
		removeGuestButton: '.tribe-tickets__rsvp-v2-ar-quantity-input-number--minus',
		quantityInput: '.tribe-tickets__rsvp-v2-ar-quantity-input input[type="number"]',
		nextGuestButton: '.tribe-tickets__rsvp-v2-form-button--next',
		submitButton: '.tribe-tickets__rsvp-v2-form-button--submit',
		hiddenElement: '.tribe-common-a11y-hidden',
		formFieldInput: 'input, select, textarea',
		formFieldRequired: '[required]',
	};

	/**
	 * Go to guest.
	 *
	 * @since TBD
	 * @param {jQuery} $container  jQuery object of the RSVP container.
	 * @param {number} guestNumber The guest number we want to go to.
	 * @return {void}
	 */
	obj.goToGuest = function ( $container, guestNumber ) {
		const $guestFormWrapper = $container.find( obj.selectors.guestFormWrapper );
		const $targetGuestForm = $guestFormWrapper.find(
			obj.selectors.guestFormFields + '[data-guest-number="' + guestNumber + '"]'
		);
		const $guestListButtons = $container.find( obj.selectors.guestListItemButton );

		// Set all forms as hidden.
		$container.find( obj.selectors.guestFormFields ).addClass( obj.selectors.hiddenElement.replace( '.', '' ) );
		$container.find( obj.selectors.guestFormFields ).prop( 'hidden', true );

		// Show the selected guest.
		obj.showElement( $targetGuestForm );
		$targetGuestForm.removeAttr( 'hidden' );

		// Set the classes for inactive.
		$guestListButtons.addClass( obj.selectors.guestListItemButtonInactive.replace( '.', '' ) );
		$guestListButtons.attr( 'aria-selected', 'false' );

		// Set the active class for the current.
		const $targetGuestButton = $container.find(
			obj.selectors.guestListItemButton + '[data-guest-number="' + guestNumber + '"]'
		);
		$targetGuestButton.removeClass( obj.selectors.guestListItemButtonInactive.replace( '.', '' ) );
		$targetGuestButton.attr( 'aria-selected', 'true' );
	};

	/**
	 * Show element.
	 *
	 * @since TBD
	 * @param {jQuery} $element jQuery object of the element to show.
	 * @return {void}
	 */
	obj.showElement = function ( $element ) {
		$element.removeClass( obj.selectors.hiddenElement.replace( '.', '' ) );
	};

	/**
	 * Hide element.
	 *
	 * @since TBD
	 * @param {jQuery} $element jQuery object of the element to hide.
	 * @return {void}
	 */
	obj.hideElement = function ( $element ) {
		$element.addClass( obj.selectors.hiddenElement.replace( '.', '' ) );
	};

	/**
	 * Validates a single field.
	 *
	 * @since TBD
	 * @param {HTMLElement} field The field element to validate.
	 * @return {boolean} True if the field is valid.
	 */
	obj.validateField = function ( field ) {
		const $field = $( field );

		// Check if field is required and empty.
		if ( $field.prop( 'required' ) ) {
			const value = $field.val();

			if ( ! value || value.trim() === '' ) {
				return false;
			}
		}

		// Use browser validation if available.
		if ( typeof field.checkValidity === 'function' ) {
			return field.checkValidity();
		}

		return true;
	};

	/**
	 * Checks if the guest form is valid.
	 *
	 * @since TBD
	 * @param {jQuery} $guestForm jQuery object of the guest form container.
	 * @return {boolean} True if the form is valid.
	 */
	obj.isGuestValid = function ( $guestForm ) {
		const $fields = $guestForm.find( obj.selectors.formFieldInput );
		let isValid = true;

		$fields.each( function () {
			const $field = $( this );
			const isValidField = obj.validateField( $field[ 0 ] );

			if ( ! isValidField ) {
				isValid = false;
			}
		} );

		const $guestFormError = $guestForm.find( obj.selectors.guestFormFieldsError );

		if ( isValid ) {
			obj.hideElement( $guestFormError );
		} else {
			obj.showElement( $guestFormError );
		}

		return isValid;
	};

	/**
	 * Check if there are required fields for the ARI.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the container.
	 * @return {boolean} True if there are required fields for ARI.
	 */
	obj.hasAriRequiredFields = function ( $container ) {
		const $form = $container.find( obj.selectors.rsvpForm );
		const $required = $form.find( obj.selectors.formFieldRequired );

		// True if there are required fields beyond the basic name/email.
		return $required.length > 0;
	};

	/**
	 * Checks if can move to the guest coming in `guestNumber`.
	 *
	 * @since TBD
	 * @param {jQuery} $container  jQuery object of the RSVP container.
	 * @param {number} guestNumber The guest number we want to go to.
	 * @return {boolean} True if can go to the guest.
	 */
	obj.canGoToGuest = function ( $container, guestNumber ) {
		const currentGuest = obj.getCurrentGuest( $container );
		const hasAriRequiredFields = obj.hasAriRequiredFields( $container );

		// If the guest number is lower than the current guest, return true.
		if ( guestNumber < currentGuest ) {
			return true;
		}

		// They can only proceed to the next guest if there are required ARI fields.
		if ( hasAriRequiredFields && 1 < guestNumber - currentGuest ) {
			return false;
		}

		// Get the current guest form.
		const $currentGuestForm = $container.find(
			obj.selectors.guestFormFields + '[data-guest-number="' + currentGuest + '"]'
		);

		// Get if there are required fields in the current.
		const isCurrentGuestValid = obj.isGuestValid( $currentGuestForm );

		return isCurrentGuestValid;
	};

	/**
	 * Get the total guests number for the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {number} Number representing the total guests.
	 */
	obj.getTotalGuests = function ( $container ) {
		return $container.find( obj.selectors.guestFormFields ).length;
	};

	/**
	 * Get the current guest number for the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {number} Number representing the current guest.
	 */
	obj.getCurrentGuest = function ( $container ) {
		const $currentFormFields = $container.find(
			obj.selectors.guestFormFields + ':not(' + obj.selectors.hiddenElement + ')'
		);

		return $currentFormFields.data( 'guest-number' );
	};

	/**
	 * Set the "Next" and "Submit" hidden classes.
	 * Bind the required actions to the "Next" button.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.setNextAndSubmit = function ( $container ) {
		const $guestForm = $container.find( obj.selectors.guestFormFields );
		const totalGuests = $guestForm.length;

		obj.bindNextButton( $container );

		$guestForm.each( function ( index, wrapper ) {
			const $nextGuestButton = $( wrapper ).find( obj.selectors.nextGuestButton );
			const $submitButton = $( wrapper ).find( obj.selectors.submitButton );
			const currentGuest = index + 1;

			// If it's the last guest.
			if ( currentGuest === totalGuests ) {
				obj.showElement( $submitButton );
				obj.hideElement( $nextGuestButton );
			} else {
				obj.showElement( $nextGuestButton );
				obj.hideElement( $submitButton );
			}
		} );
	};

	/**
	 * Bind go to guest.
	 *
	 * @since TBD
	 * @param {jQuery} $container     jQuery object of the RSVP container.
	 * @param {jQuery} $button        jQuery object of the button.
	 * @param {number} guestNumberVal The guest number.
	 * @return {void}
	 */
	obj.bindGoToGuest = function ( $container, $button, guestNumberVal ) {
		let guestNumber = guestNumberVal || 1;

		$button.on( 'click', function () {
			const guestNumberDataAttribute = $( this ).data( 'guest-number' );
			if ( undefined !== guestNumberDataAttribute ) {
				guestNumber = guestNumberDataAttribute;
			}

			if ( ! obj.canGoToGuest( $container, guestNumber ) ) {
				return;
			}

			obj.goToGuest( $container, guestNumber );
		} );
	};

	/**
	 * Add guest.
	 * Adds the form and the list item.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.addGuest = function ( $container ) {
		const $guestList = $container.find( obj.selectors.guestList );
		const $guestFormWrapper = $container.find( obj.selectors.guestFormWrapper );
		const totalGuests = obj.getTotalGuests( $container );

		const rsvpId = $container.data( 'rsvp-id' );
		const rsvpFieldsTemplate = window.wp.template(
			obj.selectors.guestFormFieldsTemplate.replace( '.', '' ) + '-' + rsvpId
		);
		const guestListItemTemplate = window.wp.template(
			obj.selectors.guestListItemTemplate.replace( '.', '' ) + '-' + rsvpId
		);
		const data = { attendee_id: totalGuests + 1 };

		// Append the new guest list item and new guest form.
		$guestList.append( guestListItemTemplate( data ) );
		$guestFormWrapper.append( rsvpFieldsTemplate( data ) );

		const $guestListItems = $guestList.children( obj.selectors.guestListItem );
		const $newGuest = $guestListItems.last();
		const $newGuestButton = $newGuest.find( obj.selectors.guestListItemButton );

		// Globally set next guest / Submit.
		obj.setNextAndSubmit( $container );

		// Bind actions on fields / buttons.
		obj.bindGoToGuest( $container, $newGuestButton );

		// Bind Cancel button in this new form.
		if ( tribe.tickets.rsvp.v2.block ) {
			const block = tribe.tickets.rsvp.v2.block;
			$container.find( block.selectors.cancelButton ).off();
			block.bindCancel( $container );
		}
	};

	/**
	 * Remove guest.
	 * Remove the form and the list item.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.removeGuest = function ( $container ) {
		const totalGuests = obj.getTotalGuests( $container );
		const currentGuest = obj.getCurrentGuest( $container );

		// Bail if there's only one guest.
		if ( totalGuests === 1 ) {
			return;
		}

		// Go to the previous guest if we're on the last one.
		if ( totalGuests === currentGuest ) {
			obj.goToGuest( $container, currentGuest - 1 );
		}

		const $guestFormFields = $container.find( obj.selectors.guestFormFields );
		const $guestListItems = $container.find( obj.selectors.guestListItem );

		// Remove HTML and bound actions of the ones that were generated via JS.
		$guestListItems.last().remove();
		$guestFormFields.last().remove();

		// Update the Next Guest / Previous buttons for the new "last" guest.
		const $newLastGuest = $container.find( obj.selectors.guestFormFields ).last();
		const $nextGuestButton = $newLastGuest.find( obj.selectors.nextGuestButton );
		const $submitButton = $newLastGuest.find( obj.selectors.submitButton );

		obj.showElement( $submitButton );
		obj.hideElement( $nextGuestButton );
	};

	/**
	 * Handle the quantity change.
	 *
	 * @since TBD
	 * @param {Event} e click event
	 * @return {void}
	 */
	obj.handleQuantityChange = function ( e ) {
		e.preventDefault();
		const $input = $( this ).parent().find( 'input[type="number"]' );
		const increase = $( this ).hasClass( obj.selectors.addGuestButton.replace( '.', '' ) );
		const step = $input.attr( 'step' ) ? Number( $input.attr( 'step' ) ) : 1;
		const originalValue = Number( $input.val() );

		// stepUp or stepDown the input according to the button that was clicked
		// handle IE/Edge
		if ( increase ) {
			const max = $input.attr( 'max' ) ? Number( $input.attr( 'max' ) ) : -1;

			if ( typeof $input[ 0 ].stepUp === 'function' ) {
				try {
					// Bail if we're already at the max, safari has issues with stepUp() here.
					if ( max !== -1 && max < originalValue + step ) {
						return;
					}
					$input[ 0 ].stepUp();
				} catch ( ex ) {
					$input[ 0 ].value = -1 === max || max >= originalValue + step ? originalValue + step : max;
				}
			} else {
				$input[ 0 ].value = -1 === max || max >= originalValue + step ? originalValue + step : max;
			}
		} else {
			const min = $input.attr( 'min' ) ? Number( $input.attr( 'min' ) ) : 0;

			if ( typeof $input[ 0 ].stepDown === 'function' ) {
				try {
					$input[ 0 ].stepDown();
				} catch ( ex ) {
					$input[ 0 ].value = min <= originalValue - step ? originalValue - step : min;
				}
			} else {
				$input[ 0 ].value = min <= originalValue - step ? originalValue - step : min;
			}
		}

		// Trigger the on Change for the input (if it has changed) as it's not handled via stepUp() || stepDown()
		if ( originalValue !== $input[ 0 ].value ) {
			$input.trigger( 'input' );
		}
	};

	/**
	 * Handle the number input + and - actions.
	 *
	 * @since TBD
	 * @param {Event} e input event
	 * @return {void}
	 */
	obj.handleQuantityChangeValue = function ( e ) {
		e.preventDefault();
		const $this = $( e.target );
		const $container = e.data.container;

		const max = $this.attr( 'max' );
		const min = $this.attr( 'min' );
		let newQuantity = parseInt( $this.val(), 10 );
		newQuantity = isNaN( newQuantity ) ? 0 : newQuantity;

		// Set it to the max if the new quantity is over the max.
		if ( max && max < newQuantity ) {
			newQuantity = max;
		}

		// If the quantity less than the min, set it to the min.
		if ( min && newQuantity < min ) {
			newQuantity = min;
		}

		// Set the input value.
		$this.val( newQuantity );

		// Define the difference and see if they're adding or removing.
		const difference = newQuantity - obj.getTotalGuests( $container );
		const isAdding = difference > 0;

		// Add or remove guest depending on the difference between the current value and
		// the new value from the input.
		for ( let i = 0; i < Math.abs( difference ); i++ ) {
			if ( isAdding ) {
				obj.addGuest( $container );
			} else {
				obj.removeGuest( $container );
			}
		}
	};

	/**
	 * Binds events for next guest button.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.bindNextButton = function ( $container ) {
		const $guestForm = $container.find( obj.selectors.guestFormFields );
		const $lastForm = $guestForm.last();
		const $lastFormNextButton = $lastForm.find( obj.selectors.nextGuestButton );
		const lastFormGuestNumber = $lastForm.data( 'guest-number' );

		obj.bindGoToGuest( $container, $lastFormNextButton, lastFormGuestNumber + 1 );
	};

	/**
	 * Binds events for guest addition/removal.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.bindGuestAddRemove = function ( $container ) {
		const $addGuestButton = $container.find( obj.selectors.addGuestButton );
		const $removeGuestButton = $container.find( obj.selectors.removeGuestButton );
		const $guestListItemButton = $container.find( obj.selectors.guestListItemButton );
		const $qtyInput = $container.find( obj.selectors.quantityInput );

		obj.bindGoToGuest( $container, $guestListItemButton );

		$addGuestButton.on( 'click', obj.handleQuantityChange );
		$removeGuestButton.on( 'click', obj.handleQuantityChange );

		$qtyInput.on( 'input', { container: $container }, obj.handleQuantityChangeValue );
	};

	/**
	 * Unbinds events.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.unbindEvents = function ( $container ) {
		const $addGuestButton = $container.find( obj.selectors.addGuestButton );
		const $removeGuestButton = $container.find( obj.selectors.removeGuestButton );
		const $guestListItemButton = $container.find( obj.selectors.guestListItemButton );

		$addGuestButton.off();
		$removeGuestButton.off();
		$guestListItemButton.off();
	};

	/**
	 * Binds events for container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.bindEvents = function ( $container ) {
		obj.bindGuestAddRemove( $container );
		obj.bindNextButton( $container );
	};

	/**
	 * Initialize RSVP V2 ARI for a single container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.init = function ( $container ) {
		// Only initialize if ARI elements are present.
		const $guestList = $container.find( obj.selectors.guestList );

		if ( $guestList.length === 0 ) {
			return;
		}

		obj.bindEvents( $container );
	};

	/**
	 * Handles the initialization of RSVP V2 ARI.
	 *
	 * @since TBD
	 * @return {void}
	 */
	obj.ready = function () {
		$( tribe.tickets.rsvp.v2.block.selectors.container ).each( function () {
			obj.init( $( this ) );
		} );
	};

	// Initialize on document ready.
	$document.ready( obj.ready );

	// Allow re-initialization via WordPress hooks.
	if ( typeof wp !== 'undefined' && wp.hooks ) {
		wp.hooks.addAction( 'tec.tickets.rsvp.v2.init', 'tec.tickets.ari', obj.ready );
	}

} )( jQuery, tribe.tickets.rsvp.v2.ari );
