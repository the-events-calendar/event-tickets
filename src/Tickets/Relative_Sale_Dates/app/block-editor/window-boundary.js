/**
 * External dependencies
 */
import { SelectControl, TextControl } from '@wordpress/components';
import { _nx, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	ANCHOR_END,
	ANCHOR_START,
	MIN_VALUE,
	MODE_RELATIVE,
	MODE_SPECIFIC,
	UNIT_DAYS,
	UNIT_HOURS,
	UNIT_MINUTES,
	UNIT_WEEKS,
} from '../rule-constants';
import { toRelativeValue } from './rule';

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( './window-kinds' ).BlockWindowKind} BlockWindowKind */
/** @typedef {import( './window-kinds' ).BoundaryLabels} BoundaryLabels */

/**
 * The name of each unit, in the form a number takes.
 *
 * The msgids and context match the classic editor's, so one translation serves both.
 *
 * @since TBD
 *
 * @type {Object<number, function( number ): string>}
 */
const UNIT_NAMES = {
	[ UNIT_MINUTES ]: ( count ) =>
		_nx( 'minute', 'minutes', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_HOURS ]: ( count ) => _nx( 'hour', 'hours', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_DAYS ]: ( count ) => _nx( 'day', 'days', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_WEEKS ]: ( count ) => _nx( 'week', 'weeks', count, 'Unit of a relative ticket sale date.', 'event-tickets' ),
};

/**
 * Builds the options of the units a window takes, named in the form the number takes.
 *
 * @since TBD
 *
 * @param {number[]}      units The units the window takes, in seconds.
 * @param {number|string} value The relative number, as the admin typed it.
 *
 * @return {{label: string, value: string}[]} The unit options.
 */
export function getUnitOptions( units, value ) {
	const count = parseInt( value, 10 );

	return units.map( ( unit ) => ( { value: String( unit ), label: UNIT_NAMES[ unit ]( count ) } ) );
}

/**
 * Builds the options of the event dates a window's relative boundaries are counted from.
 *
 * The msgids and context match the classic editor's, so one translation serves both.
 *
 * @since TBD
 *
 * @param {string[]} anchors The event dates the window counts from.
 *
 * @return {{label: string, value: string}[]} The anchor options.
 */
function getAnchorOptions( anchors ) {
	const labels = {
		[ ANCHOR_START ]: _x(
			'before the event starts',
			'What a relative ticket sale date is measured from.',
			'event-tickets'
		),
		[ ANCHOR_END ]: _x(
			'before the event ends',
			'What a relative ticket sale date is measured from.',
			'event-tickets'
		),
	};

	return anchors.map( ( anchor ) => ( { value: anchor, label: labels[ anchor ] } ) );
}

/**
 * Renders the options of one boundary of a window: its mode, and the relative number, unit and anchor, or its date
 * picker.
 *
 * @since TBD
 *
 * @param {Object}                           props                     The component props.
 * @param {BlockWindowKind}                  props.kind                The window kind.
 * @param {string}                           props.name                The boundary, `start` or `end`.
 * @param {SaleWindowEnd}                    props.boundary            The boundary's mode and relative values.
 * @param {BoundaryLabels}                   props.labels              The labels of the boundary's controls.
 * @param {{label: string, value: string}[]} props.modeOptions         The mode options.
 * @param {Object}                           props.picker              The date picker element of a specific boundary,
 *                                                                     as the container prepared it.
 * @param {string}                           [props.helperText]        The text under the relative values, or
 *                                                                     `undefined` for a boundary that shows none.
 * @param {string}                           [props.errorMessage]      The window error this boundary is marked with,
 *                                                                     or an empty string.
 * @param {string}                           [props.valueErrorMessage] The range error of the relative number, or an
 *                                                                     empty string.
 * @param {Function}                         props.onChange            Called with the changed values of the boundary.
 *
 * @return {Object} The boundary's options.
 */
export default function WindowBoundary( {
	kind,
	name,
	boundary,
	labels,
	modeOptions,
	picker,
	helperText,
	errorMessage = '',
	valueErrorMessage = '',
	onChange,
} ) {
	const anchorOptions = getAnchorOptions( kind.anchors );

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
						max={ kind.maxValue }
						step={ 1 }
						value={ boundary.value }
						aria-invalid={ valueErrorMessage ? true : undefined }
						help={
							valueErrorMessage ? (
								<span className="tec-tickets-relative-sale-dates__error" role="alert">
									{ valueErrorMessage }
								</span>
							) : undefined
						}
						onChange={ ( value ) => onChange( { value: toRelativeValue( value, kind ) } ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ labels.unit }
						hideLabelFromVision
						value={ String( boundary.unit ) }
						options={ getUnitOptions( kind.units, boundary.value ) }
						onChange={ ( unit ) => onChange( { unit: parseInt( unit, 10 ) } ) }
					/>
					{ anchorOptions.length > 1 ? (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ labels.anchor }
							hideLabelFromVision
							value={ boundary.anchor }
							options={ anchorOptions }
							onChange={ ( anchor ) => onChange( { anchor } ) }
						/>
					) : (
						<span>{ anchorOptions[ 0 ].label }</span>
					) }
					{ undefined !== helperText && (
						<p className="tec-tickets-relative-sale-dates__helper" aria-live="polite">
							{ helperText }
						</p>
					) }
				</div>
			) }
			{ MODE_SPECIFIC === boundary.mode && picker }
		</div>
	);
}
