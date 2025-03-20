/**
 * File: coupons.js
 *
 * Dependencies:
 * - jQuery
 * - window.IMask
 * - window.tribe.validation
 * - window.etOrderModifiersAmountField
 *
 * @typedef {Object} etOrderModifiersAmountField The internationalization object.
 * @type {string} currencySymbol     The currency symbol
 * @type {string} decimalSeparator   The decimal separator character
 * @type {string} thousandsSeparator The thousands separator character
 * @type {string} percentMax         The maximum value for a percentage
 * @type {string} placement          Can be "prefix" or "postfix"
 * @type {number} precision          The number of decimal places to display
 */

window.etOrderModifiersAmountField = window.etOrderModifiersAmountField || {
	currencySymbol: '$',
	decimalSeparator: '.',
	thousandsSeparator: ',',
	percentMax: 999999999,
	placement: 'prefix',
	precision: 2,
};

/**
 * This script initializes the amount field for order modifiers.
 *
 * @since 5.21.0
 * @param {jQuery} $ jQuery
 * @param {Object} validation The validation object
 * @param {etOrderModifiersAmountField} i18n The internationalization object
 */
( function( $, validation, i18n ) {
	const $document = $( document );
	let mask;

	const selectors = {
		amount: '#order_modifier_amount',
		form: '.tec-settings-order_modifier',
		type: '#order_modifier_sub_type',
	};

	const getType = () => $( selectors.type ).val();

	const getMaskPattern = () => {
		if ( 'percent' === getType() ) {
			return 'num %';
		}

		return 'postfix' === i18n.placement
			? `num ${ i18n.currencySymbol }`
			: `${ i18n.currencySymbol } num`;
	};

	const getMaskOptions = ( pattern ) => {
		return {
			mask: pattern,
			lazy: false,
			blocks: {
				num: {
					mask: Number,
					max: 999999999,
					min: 0,
					normalizeZeros: true,
					padFractionalZeros: true,
					radix: i18n.decimalSeparator,
					scale: i18n.precision,
					thousandsSeparator: i18n.thousandsSeparator,
				},
			},
		};
	};

	const setupMask = () => {
		mask = window.IMask(
			document.querySelector( selectors.amount ),
			getMaskOptions( getMaskPattern() ),
		);
	};

	const updateMask = () => {
		const value = mask.unmaskedValue;
		mask.updateOptions( { mask: getMaskPattern() } );
		mask.unmaskedValue = value;
	};

	const isPercentValid = () => {
		if ( 'percent' !== getType() ) {
			return true;
		}

		return parseFloat( mask.unmaskedValue ) <= parseFloat( i18n.percentMax );
	};

	const validateAmount = () => {
		const $form = $( selectors.form );
		const $input = $( selectors.amount );
		const asFloat = parseFloat( mask.unmaskedValue );

		// Set up the conditions to check if the value is valid.
		const isFloatValid = ! isNaN( asFloat ) && asFloat > 0;

		if ( isFloatValid && isPercentValid() ) {
			$input.removeClass( validation.selectors.error.className() );
			$input.val( asFloat );
			return;
		}

		// Remove the valid class so the validation library won't allow submit.
		$form.removeClass( validation.selectors.valid.className() );

		// Mark the input as invalid and display the error.
		$input.addClass( validation.selectors.error.className() );
		$input.one( 'focusin', validation.onChangeFieldRemoveError );
		$input.trigger( 'displayErrors.tribe' );
	};

	$document.ready( () => {
		setupMask( document.querySelector( selectors.amount ) );
		$document.on( 'change', selectors.type, updateMask );
		$document.on( 'validation.tribe', validateAmount );
	} );
}( jQuery, window.tribe.validation, window.etOrderModifiersAmountField || {} ) );
