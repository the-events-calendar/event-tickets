/**
 * File: coupons.js
 *
 * Dependencies:
 * - jQuery
 * - window.tribe.validation
 * - window.etOrderModifiersAmountField
 */

// Fallbacks for the global variables.
window.etOrderModifiersAmountField = window.etOrderModifiersAmountField || {
	currencySymbol: '$',
	decimalSeparator: '.',
	placement: 'before',
	precision: 2,
};

( function( $, validation, i18n ) {
	const $document = $( document );

	const selectors = {
		amount: '#order_modifier_amount',
		save: '#order_modifier_form_save',
		type: '#order_modifier_sub_type',
	};

	const getType = () => {
		const $typeSelect = $( selectors.type );
		return $typeSelect.val();
	};

	const addAmountExtras = () => {
		const $amount = $( selectors.amount );
		let value = Number.parseFloat( $amount.val() || 0 ).toFixed( i18n.precision );

		// If the decimal separator is not a period, replace it.
		if ( '.' !== i18n.decimalSeparator ) {
			value = value.replace( '.', i18n.decimalSeparator );
		}

		// Set the input type to text.
		$amount.attr( 'type', 'text' );

		// Set the value based on the type.
		switch ( getType() ) {
			case 'percent':
				$( selectors.amount ).val( `${ value }%` );
				break;

			case 'flat':
				if ( 'after' === i18n.placement ) {
					$( selectors.amount ).val( `${ value }${ i18n.currencySymbol }` );
				} else {
					$( selectors.amount ).val( `${ i18n.currencySymbol }${ value }` );
				}
				break;

			default:
				break;
		}
	};

	const removeAmountExtras = () => {
		const $amount = $( selectors.amount );
		let value = $amount.val();

		// Maybe replace the decimal separator.
		if ( '.' !== i18n.decimalSeparator ) {
			value = value.replace( i18n.decimalSeparator, '.' );
		}

		// Remove any other non-digit characters.
		value = value.replace( /[^0-9.]/g, '' );

		$amount.val( value );
		$amount.attr( 'type', 'number' );
	};

	const updateAmountDisplay = () => {
		removeAmountExtras();
		addAmountExtras();
	};

	const validateAmount = () => {
		const $input = $( selectors.amount );
		const value = $input.val();
		const asFloat = parseFloat( value );

		if ( ! isNaN( asFloat ) && asFloat > 0 ) {
			$input.removeClass( validation.selectors.error.className() );
			return;
		}

		$input.addClass( validation.selectors.error.className() );
		$input.one( 'focusin', validation.onChangeFieldRemoveError );
		$input.trigger( 'displayErrors.tribe' );
	};

	const handleUnfocus = () => {
		validateAmount();
		addAmountExtras();
	};

	const prepareForValidation = () => {
		removeAmountExtras();
		validateAmount();
	};

	const bindEvents = () => {
		$document.on( 'change', selectors.type, updateAmountDisplay );
		$document.on( 'focusin', selectors.amount, removeAmountExtras );
		$document.on( 'focusout', selectors.amount, handleUnfocus );
		$document.on( 'validation.tribe', prepareForValidation );
	};

	$document.ready( () => {
		bindEvents();
		updateAmountDisplay();
	} );
}( jQuery, window.tribe.validation, window.etOrderModifiersAmountField || {} ) );
