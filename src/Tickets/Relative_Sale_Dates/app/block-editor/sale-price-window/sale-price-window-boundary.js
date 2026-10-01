/**
 * External dependencies
 */
import { SelectControl, TextControl } from '@wordpress/components';
import { cloneElement } from '@wordpress/element';
import { _n, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	MIN_VALUE,
	MODE_RELATIVE,
	MODE_SPECIFIC,
	SALE_PRICE_MAX_VALUE,
	UNIT_DAYS,
	UNIT_WEEKS,
} from '../../rule-constants';
import { toRelativeValue } from '../rule';

/** @typedef {import( '../../sale-price-window' ).SalePriceBoundary} SalePriceBoundary */

/**
 * @typedef {Object} SalePriceBoundaryLabels
 *
 * @property {string} mode  The label of the mode select.
 * @property {string} value The accessible name of the relative number.
 * @property {string} unit  The accessible name of the relative unit.
 * @property {string} date  The accessible name of the specific date picker.
 */

/**
 * Builds the unit options, named in the form the number takes.
 *
 * @since TBD
 *
 * @param {number|string} value The relative number, as the admin typed it.
 *
 * @return {{label: string, value: string}[]} The unit options.
 */
function getUnitOptions( value ) {
	const count = parseInt( value, 10 );

	// The msgids match the sales window's, so one translation serves both.
	return [
		{ value: String( UNIT_DAYS ), label: _n( 'day', 'days', count, 'event-tickets' ) },
		{ value: String( UNIT_WEEKS ), label: _n( 'week', 'weeks', count, 'event-tickets' ) },
	];
}

/**
 * Renders the options of one boundary of the sale price window: its mode, and the relative number and unit, or its date
 * picker.
 *
 * @since TBD
 *
 * @param {Object}                           props              The component props.
 * @param {string}                           props.name         The boundary, `start` or `end`.
 * @param {SalePriceBoundary}                props.boundary     The boundary's mode and relative values.
 * @param {SalePriceBoundaryLabels}          props.labels       The labels of the boundary's controls.
 * @param {{label: string, value: string}[]} props.modeOptions  The mode options.
 * @param {Object}                           props.picker       The boundary's date picker element.
 * @param {string}                           [props.helperText] How long the sale price lasts, for the boundary that
 *                                                              tells it; left out for the other.
 * @param {string}                           props.errorMessage The sale price window error this boundary is marked
 *                                                              with, or an empty string.
 * @param {Function}                         props.onChange     Called with the changed values of the boundary.
 *
 * @return {Object} The boundary's options.
 */
export default function SalePriceWindowBoundary( {
	name,
	boundary,
	labels,
	modeOptions,
	picker,
	helperText,
	errorMessage,
	onChange,
} ) {
	return (
		<div className={ `tec-tickets-relative-sale-dates__end tec-tickets-relative-sale-dates__end--${ name }` }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ labels.mode }
				aria-invalid={ errorMessage ? true : undefined }
				// The control points `aria-describedby` at its help, so the error goes there to be announced with it.
				help={
					errorMessage ? (
						<span className="tribe-editor__ticket__sale-price__error-message" role="alert">
							{ errorMessage }
						</span>
					) : undefined
				}
				value={ boundary.mode }
				options={ modeOptions }
				onChange={ ( mode ) => onChange( { mode } ) }
			/>
			{ MODE_RELATIVE === boundary.mode && (
				<div className="tec-tickets-relative-sale-dates__relative">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ labels.value }
						hideLabelFromVision
						type="number"
						min={ MIN_VALUE }
						max={ SALE_PRICE_MAX_VALUE }
						step={ 1 }
						value={ boundary.value }
						onChange={ ( value ) => onChange( { value: toRelativeValue( value, SALE_PRICE_MAX_VALUE ) } ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ labels.unit }
						hideLabelFromVision
						value={ String( boundary.unit ) }
						options={ getUnitOptions( boundary.value ) }
						onChange={ ( unit ) => onChange( { unit: parseInt( unit, 10 ) } ) }
					/>
					<span>
						{ _x(
							'before the event starts',
							'What a relative ticket sale date is measured from.',
							'event-tickets'
						) }
					</span>
				</div>
			) }
			{ MODE_SPECIFIC === boundary.mode && (
				// The legacy wrapper keeps the width the sale price section gives its pickers.
				<div className={ `tribe-editor__ticket__sale-price--${ name }-date` }>
					{ cloneElement( picker, {
						inputProps: { ...picker.props.inputProps, 'aria-label': labels.date },
					} ) }
				</div>
			) }
			{ undefined !== helperText && (
				<p className="tec-tickets-relative-sale-dates__helper" aria-live="polite">
					{ helperText }
				</p>
			) }
		</div>
	);
}
