/**
 * External dependencies
 */
import { SelectControl, TextControl } from '@wordpress/components';
import { cloneElement } from '@wordpress/element';
import { _nx } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	MAX_VALUE,
	MIN_VALUE,
	MODE_RELATIVE,
	MODE_SPECIFIC,
	UNIT_DAYS,
	UNIT_HOURS,
	UNIT_MINUTES,
	UNIT_WEEKS,
} from '../../rule-constants';
import { toRelativeValue } from '../rule';

/** @typedef {import( '../../sale-window' ).SaleWindowEnd} SaleWindowEnd */

/**
 * @typedef {Object} SalesWindowEndLabels
 *
 * @property {string} mode   The label of the mode select.
 * @property {string} value  The accessible name of the relative number.
 * @property {string} unit   The accessible name of the relative unit.
 * @property {string} anchor The accessible name of the relative anchor.
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

	// The msgids and context match the classic editor's, so one translation serves both.
	return [
		{
			value: String( UNIT_MINUTES ),
			label: _nx( 'minute', 'minutes', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
		},
		{
			value: String( UNIT_HOURS ),
			label: _nx( 'hour', 'hours', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
		},
		{
			value: String( UNIT_DAYS ),
			label: _nx( 'day', 'days', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
		},
		{
			value: String( UNIT_WEEKS ),
			label: _nx( 'week', 'weeks', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
		},
	];
}

/**
 * Renders the options of one end of the sales window: its mode, and the relative number, unit and anchor, or its half
 * of the date and time range picker.
 *
 * @since TBD
 *
 * @param {Object}                           props               The component props.
 * @param {string}                           props.name          The end of the window, `start` or `end`.
 * @param {SaleWindowEnd}                    props.end           The end's mode and relative values.
 * @param {SalesWindowEndLabels}             props.labels        The labels of the end's controls.
 * @param {{label: string, value: string}[]} props.modeOptions   The mode options.
 * @param {{label: string, value: string}[]} props.anchorOptions The anchor options.
 * @param {Object}                           props.picker        The date and time range picker element.
 * @param {string}                           props.helperText    The date a relative end works out to, or an empty
 *                                                               string.
 * @param {string}                           props.errorMessage  The sales window error this end is marked with, or an
 *                                                               empty string.
 * @param {Function}                         props.onChange      Called with the changed values of the end.
 *
 * @return {Object} The end's options.
 */
export default function SalesWindowEnd( {
	name,
	end,
	labels,
	modeOptions,
	anchorOptions,
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
						<span className="tec-tickets-relative-sale-dates__error" role="alert">
							{ errorMessage }
						</span>
					) : undefined
				}
				value={ end.mode }
				options={ modeOptions }
				onChange={ ( mode ) => onChange( { mode } ) }
			/>
			{ MODE_RELATIVE === end.mode && (
				<div className="tec-tickets-relative-sale-dates__relative">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ labels.value }
						hideLabelFromVision
						type="number"
						min={ MIN_VALUE }
						max={ MAX_VALUE }
						step={ 1 }
						value={ end.value }
						onChange={ ( value ) => onChange( { value: toRelativeValue( value ) } ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ labels.unit }
						hideLabelFromVision
						value={ String( end.unit ) }
						options={ getUnitOptions( end.value ) }
						onChange={ ( unit ) => onChange( { unit: parseInt( unit, 10 ) } ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ labels.anchor }
						hideLabelFromVision
						value={ end.anchor }
						options={ anchorOptions }
						onChange={ ( anchor ) => onChange( { anchor } ) }
					/>
					<p className="tec-tickets-relative-sale-dates__helper" aria-live="polite">
						{ helperText }
					</p>
				</div>
			) }
			{ MODE_SPECIFIC === end.mode &&
				cloneElement( picker, {
					className: [ picker.props.className, `tec-tickets-relative-sale-dates__picker--${ name }` ]
						.filter( Boolean )
						.join( ' ' ),
				} ) }
		</div>
	);
}
