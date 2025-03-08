// import { onReady } from '@tec/tickets/order-modifiers/utils';
import { onReady } from '../../utils';
import { localizedData } from "./localized-data";
import IMask from 'imask';

console.log( localizedData );

const $jq = window.jQuery;
const validation = window.tribe.validation;

/**
 * The document element that should be targeted by the module.
 * Defaults to the document.
 *
 * @since TBD
 *
 * @type {HTMLElement}
 */
let targetDom = document;

/**
 * The mask instance.
 *
 * @since TBD
 *
 * @type {IMask.Mask}
 */
let mask;


/**
 * Sets the DOM to initialize the timer(s) in.
 *
 * Defaults to the document.
 *
 * @since TBD
 *
 * @param {HTMLElement} targetDocument The DOM to initialize the timer(s) in.
 */
export function setTargetDom( targetDocument ) {
	targetDom = targetDocument || document;
}

const selectors = {
	amount: '#order_modifier_amount',
	save: '#order_modifier_form_save',
	type: '#order_modifier_sub_type',
};

const getType = () => {
	const $typeSelect = $jq( selectors.type );
	return $typeSelect.val();
};

const addAmountExtras = () => {
	const $amount = $jq( selectors.amount );
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
			$jq( selectors.amount ).val( `${ value }%` );
			break;

		case 'flat':
			if ( 'after' === i18n.placement ) {
				$jq( selectors.amount ).val( `${ value }${ i18n.currencySymbol }` );
			} else {
				$jq( selectors.amount ).val( `${ i18n.currencySymbol }${ value }` );
			}
			break;

		default:
			break;
	}
};

const removeAmountExtras = () => {
	const $amount = $jq( selectors.amount );
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
	const $input = $jq( selectors.amount );
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

const formatNumber = IMask.createPipe( {
	mask: Number,
	thousandsSeparator: i18n.thousandsSeparator,
	decimalSeparator: i18n.decimalSeparator,
	scale: i18n.precision,
	padFractionalZeros: true,
	min: 0,
	max: 999999999,
} );

export const setupMask = ( element ) => {


	mask = IMask( element, {
		mask: localizedData.mask,
		lazy: false,
	} );
};

export {
	updateAmountDisplay,
};

$jq( document ).ready( () => {
	console.log( 'Hello, World! (jquery)' );
} );


onReady( () => {
	console.log( 'Hello, World!' );
	bindEvents();
	updateAmountDisplay();
} );
