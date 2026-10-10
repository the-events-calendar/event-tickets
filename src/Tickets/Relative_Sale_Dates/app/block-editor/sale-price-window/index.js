/**
 * External dependencies
 */
import { cloneElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MODE_SPECIFIC } from '../../rule-constants';
import { useWindowDraft } from '../use-window-draft';
import WindowBoundaries from '../window-boundaries';
import { BLOCK_SALE_PRICE_WINDOW } from '../window-kinds';

/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */

/**
 * The date picker settings the legacy sale price section derives from the other boundary's date.
 *
 * @since TBD
 *
 * @type {string[]}
 */
const OTHER_DATE_BOUNDS = [ 'disabledDays', 'fromMonth', 'toMonth', 'month' ];

/**
 * Removes from a date picker's settings the bounds the legacy section derives from the other boundary's date.
 *
 * @since TBD
 *
 * @param {Object} dayPickerProps The date picker's settings.
 *
 * @return {Object} The settings, without those bounds.
 */
function withoutOtherDateBounds( dayPickerProps = {} ) {
	return Object.fromEntries(
		Object.entries( dayPickerProps ).filter( ( [ key ] ) => ! OTHER_DATE_BOUNDS.includes( key ) )
	);
}

/**
 * Prepares the legacy date picker of one boundary of the sale price window.
 *
 * @since TBD
 *
 * @param {Object}         picker   The boundary's date picker element.
 * @param {string}         name     The boundary, `start` or `end`.
 * @param {string}         label    The accessible name of the date picker.
 * @param {SaleWindowRule} formRule The rule the options show.
 *
 * @return {Object} The date picker, in the wrapper that keeps the width the sale price section gives it.
 */
function preparePicker( picker, name, label, formRule ) {
	const isOtherSpecific = MODE_SPECIFIC === formRule[ 'start' === name ? 'end' : 'start' ].mode;

	return (
		<div className={ `tribe-editor__ticket__sale-price--${ name }-date` }>
			{ cloneElement( picker, {
				inputProps: { ...picker.props.inputProps, 'aria-label': label },
				// The other boundary's date is hidden and left stale once it is relative or Now.
				...( ! isOtherSpecific && { dayPickerProps: withoutOtherDateBounds( picker.props.dayPickerProps ) } ),
			} ) }
		</div>
	);
}

/**
 * Renders the sale price window options of a ticket block in place of its sale dates row.
 *
 * @since TBD
 *
 * @param {Object}                       props          The component props.
 * @param {string}                       props.clientId The client ID of the ticket block.
 * @param {{start: Object, end: Object}} props.pickers  The sale price start and end date pickers.
 *
 * @return {Object} The sale price window options.
 */
export default function SalePriceWindow( { clientId, pickers } ) {
	const { formRule, onChange } = useWindowDraft( clientId, BLOCK_SALE_PRICE_WINDOW );
	return (
		<WindowBoundaries
			kind={ BLOCK_SALE_PRICE_WINDOW }
			formRule={ formRule }
			onChange={ onChange }
			getBoundaryProps={ ( name, settings ) => ( {
				picker: preparePicker( pickers[ name ], name, settings.labels.date, formRule ),
			} ) }
		/>
	);
}
