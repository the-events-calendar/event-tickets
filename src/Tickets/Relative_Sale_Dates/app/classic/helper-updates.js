/**
 * Keeps the text around the classic ticket form's windows in step with the form: the dates relative ends work out to,
 * how long a window lasts, the unit names and the tickets list.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';
import { _nx } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MODE_RELATIVE, UNIT_DAYS, UNIT_HOURS, UNIT_MINUTES, UNIT_WEEKS } from '../rule-constants';
import { resolveWindow } from '../sale-window';
import { getFormWindow } from '../window-check';
import { getWindowLengthText } from '../window-length';
import { getContext, getEventDates, readFormDates } from './form-context';
import { formatHelperText } from './helper-text';
import { readRule } from './rule';
import { getListText } from './tickets-list';
import { CLASSIC_SALES_WINDOW, CLASSIC_WINDOW_KINDS } from './window-kinds';

/** @typedef {import( './window-kinds' ).ClassicWindowKind} ClassicWindowKind */

/**
 * The name of each unit for a number of it, keyed by the unit in seconds. The msgids and context match the template's.
 *
 * @since TBD
 *
 * @type {Object<string, function( number ): string>}
 */
const UNIT_NAMES = {
	[ UNIT_MINUTES ]: ( number ) =>
		_nx( 'minute', 'minutes', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_HOURS ]: ( number ) =>
		_nx( 'hour', 'hours', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_DAYS ]: ( number ) => _nx( 'day', 'days', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_WEEKS ]: ( number ) =>
		_nx( 'week', 'weeks', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
};

/**
 * The kinds whose window the form says the length of.
 *
 * @since TBD
 *
 * @type {ClassicWindowKind[]}
 */
export const LENGTH_KINDS = CLASSIC_WINDOW_KINDS.filter( ( kind ) => kind.lengthId );

/**
 * Gets the translated day and month names TEC localizes, for `DateFormatter`.
 *
 * @since TBD
 *
 * @param {Object} dynamic The date formats and names TEC localizes for its own helper text.
 *
 * @return {Object} The names that were localized.
 */
function getDateSettings( dynamic ) {
	// A name list left undefined would replace `DateFormatter`'s English default.
	return Object.fromEntries(
		[ 'days', 'daysShort', 'months', 'monthsShort' ]
			.filter( ( name ) => Array.isArray( dynamic[ name ] ) )
			.map( ( name ) => [ name, dynamic[ name ] ] )
	);
}

/**
 * Reads the rule a tickets list row carries.
 *
 * @since TBD
 *
 * @param {string} json The rule, as JSON.
 *
 * @return {import( '../sale-window' ).SaleWindowRule|null} The rule, or `null` when it cannot be read.
 */
function parseRule( json ) {
	try {
		const rule = JSON.parse( json );

		return rule?.start && rule?.end ? rule : null;
	} catch ( error ) {
		return null;
	}
}

/**
 * Writes, under each relative end of the sales window, the date it works out to for the event dates in the form.
 *
 * @since TBD
 *
 * @return {void}
 */
export function updateHelperText() {
	const context = getContext();

	if ( ! context || ! document.getElementById( 'ticket_sales_start_helper' ) ) {
		return;
	}

	const { settings, dynamic } = context;
	const rule = readRule( document );
	const saleWindow = resolveWindow( rule, getEventDates( context ), CLASSIC_SALES_WINDOW );
	const formats = {
		dateWithYear: settings.dateWithYear,
		dateNoYear: settings.dateNoYear,
		timeFormat: settings.timeFormat,
		dateSettings: getDateSettings( dynamic ),
	};

	[ 'start', 'end' ].forEach( ( key ) => {
		const date = saleWindow ? saleWindow[ key ] : null;
		const isRelative = MODE_RELATIVE === rule[ key ].mode;

		// The year is now's in the event timezone, as the block editor reads it.
		const text =
			isRelative && date && date.isValid()
				? formatHelperText( settings.text[ key ], date, formats, moment().utcOffset( date.utcOffset() ).year() )
				: '';
		const helper = document.getElementById( `ticket_sales_${ key }_helper` );

		// The helper is a live region: writing the same text again can announce it again.
		if ( helper.textContent !== text ) {
			helper.textContent = text;
		}
	} );
}

/**
 * Writes how long the window of a kind lasts for the event dates in the form.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind The kind of the window.
 *
 * @return {void}
 */
export function updateWindowLength( kind ) {
	const context = getContext();
	const length = document.getElementById( kind.lengthId );

	if ( ! context || ! length ) {
		return;
	}

	const text = getWindowLengthText(
		getFormWindow( readRule( document, kind ), getEventDates( context ), readFormDates( kind, context ), kind ),
		kind
	);

	// The length is a live region: writing the same text again can announce it again.
	if ( length.textContent !== text ) {
		length.textContent = text;
	}
}

/**
 * Writes how long the window of every kind that shows its length lasts.
 *
 * @since TBD
 *
 * @return {void}
 */
export function updateWindowLengths() {
	LENGTH_KINDS.forEach( updateWindowLength );
}

/**
 * Names the units of each relative end of every window in the plural form of the number typed for it.
 *
 * @since TBD
 *
 * @return {void}
 */
export function updateUnitNames() {
	CLASSIC_WINDOW_KINDS.forEach( ( kind ) => {
		[ 'start', 'end' ].forEach( ( key ) => {
			const value = document.getElementById( `${ kind.fieldPrefix }_${ key }_value` );
			const unit = document.getElementById( `${ kind.fieldPrefix }_${ key }_unit` );

			if ( ! value || ! unit ) {
				return;
			}

			const number = parseInt( value.value, 10 );

			Array.from( unit.options )
				.filter( ( option ) => kind.units.includes( Number( option.value ) ) )
				.forEach( ( option ) => {
					// A cleared number shows the plural names.
					option.textContent = UNIT_NAMES[ option.value ]( Number.isNaN( number ) ? 2 : number );
				} );
		} );
	} );
}

/**
 * Rewrites the sale dates of each listed ticket with a rule for the event dates in the form.
 *
 * Only the text follows the form: the row's icon, which tells an upcoming, on-sale or past ticket apart, is the one the
 * server drew, and changes once the event and ticket are saved.
 *
 * @since TBD
 *
 * @return {void}
 */
export function updateTicketsList() {
	const context = getContext();
	const rows = document.querySelectorAll( '[data-relative-sale-dates]' );

	if ( ! context || ! rows.length ) {
		return;
	}

	const eventDates = getEventDates( context );
	const listSettings = { format: context.settings.dateWithYear, dateSettings: getDateSettings( context.dynamic ) };

	rows.forEach( ( row ) => {
		const rule = parseRule( row.dataset.relativeSaleDates );

		// A row whose rule cannot be read keeps the dates the server wrote, and the other rows are still updated.
		if ( ! rule ) {
			return;
		}

		row.textContent = getListText(
			rule,
			resolveWindow( rule, eventDates, CLASSIC_SALES_WINDOW ),
			{ start: row.dataset.saleStart, end: row.dataset.saleEnd || '' },
			listSettings
		);
	} );
}
