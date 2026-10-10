/**
 * The window kinds as the Ticket block uses them: the shared kinds, with the labels of their options and what the
 * block reads from its legacy store for each.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MIN_VALUE, MODE_DEFAULT, MODE_NOW, MODE_RELATIVE, MODE_SPECIFIC } from '../rule-constants';
import { ENDS_BEFORE_START, OUTSIDE_PARENT, RELATIVE_VALUE_OUT_OF_RANGE } from '../window-errors';
import { SALES_WINDOW, SALE_PRICE_WINDOW } from '../window-kinds';
import {
	getTicketFormDates,
	getTicketSalePriceFormDates,
	isSalePriceChecked,
	isSalePriceKept,
} from './common-store-bridge';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../window-kinds' ).WindowKind} WindowKind */

/**
 * @typedef {Object} BoundaryLabels
 *
 * @property {string} mode     The label of the mode select.
 * @property {string} value    The accessible name of the relative number.
 * @property {string} unit     The accessible name of the relative unit.
 * @property {string} [anchor] The accessible name of the anchor select, for a kind with several anchors.
 * @property {string} [date]   The accessible name of the boundary's own date picker, for a kind that has one.
 */

/**
 * @typedef {Object} BoundarySettings
 *
 * @property {BoundaryLabels}                   labels      The labels of the boundary's controls.
 * @property {{label: string, value: string}[]} modeOptions The mode options.
 */

/** @typedef {{start: BoundarySettings, end: BoundarySettings}} WindowSettings */
/** @typedef {function( Object ): (SaleWindowRule|null|undefined)} StoredRuleReader */
/** @typedef {function( Object, string ): {start: string|null, end: string|null}} FormDatesReader */
/** @typedef {function( Object, string ): boolean} KeptReader */
/** @typedef {function( WindowKind ): string} ErrorText */

/**
 * @typedef {Object} BlockWindowKindFields
 *
 * @property {function(): WindowSettings}  getBoundarySettings  The labels and mode options of each boundary.
 * @property {function( string ): boolean} isAdded              Whether the ticket block's form adds the window to the
 *                                                              ticket, so its request carries the rule.
 * @property {KeptReader}                  isKept               Whether a save of the ticket's form keeps the window, so
 *                                                              the server judges it, from the legacy ticket state and
 *                                                              the client ID. The block's own reading of the shared
 *                                                              kind's `isEnabled()`, which reads the classic form.
 * @property {boolean}                     marksNewRuleAsChange Whether keeping a new rule's defaults as the draft
 *                                                              marks the ticket as changed.
 * @property {string}                      requestKey           The field of the ticket request that carries the rule,
 *                                                              as JSON.
 * @property {string|undefined}            emptyValue           What the ticket request carries for a draft without a
 *                                                              rule, or `undefined` to carry nothing.
 * @property {StoredRuleReader}            readStored           The rule a ticket from the block editor tickets REST API
 *                                                              was stored with: `null` for none, and `undefined` for a
 *                                                              window the ticket does not have.
 * @property {function( Object ): boolean} isAnswered           Whether a ticket from that REST API says what was stored
 *                                                              for the window.
 * @property {FormDatesReader}             readFormDates        Reads the start and end dates the ticket's form sends
 *                                                              from the legacy ticket state and the client ID, each
 *                                                              `YYYY-MM-DD HH:mm:ss` in the event timezone, or `null`.
 * @property {Object<string, ErrorText>}   errorTexts           The text of each error the window can have, keyed by
 *                                                              the error's key: the server's own where it rejects a
 *                                                              save with the error, so one translation serves both.
 */

/** @typedef {WindowKind & BlockWindowKindFields} BlockWindowKind */

/**
 * Builds the labels and mode options of each end of the sales window.
 *
 * Where a msgid is the classic editor's (Now, When the event starts and the relative labels), its context is too, so
 * one translation serves both.
 *
 * @since TBD
 *
 * @return {{start: BoundarySettings, end: BoundarySettings}} The labels and mode options, keyed by end.
 */
function getSalesWindowSettings() {
	return {
		start: {
			labels: {
				mode: _x( 'From', 'When ticket sales start, in the Ticket block.', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sales start.
				value: __( 'Number of units before the event that sales start', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sales start.
				unit: __( 'Unit of the sales start', 'event-tickets' ),
				// translators: The screen reader label of what a relative ticket sales start is measured from.
				anchor: __( 'What the sales start is measured from', 'event-tickets' ),
			},
			modeOptions: [
				{ value: MODE_DEFAULT, label: _x( 'Now', 'When ticket sales start.', 'event-tickets' ) },
				{ value: MODE_RELATIVE, label: _x( 'A relative date', 'When ticket sales start.', 'event-tickets' ) },
				{ value: MODE_SPECIFIC, label: _x( 'A specific date', 'When ticket sales start.', 'event-tickets' ) },
			],
		},
		end: {
			labels: {
				mode: _x( 'To', 'When ticket sales end, in the Ticket block.', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sales end.
				value: __( 'Number of units before the event that sales end', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sales end.
				unit: __( 'Unit of the sales end', 'event-tickets' ),
				// translators: The screen reader label of what a relative ticket sales end is measured from.
				anchor: __( 'What the sales end is measured from', 'event-tickets' ),
			},
			modeOptions: [
				{
					value: MODE_DEFAULT,
					label: _x( 'When the event starts', 'When ticket sales end.', 'event-tickets' ),
				},
				{ value: MODE_RELATIVE, label: _x( 'A relative date', 'When ticket sales end.', 'event-tickets' ) },
				{ value: MODE_SPECIFIC, label: _x( 'A specific date', 'When ticket sales end.', 'event-tickets' ) },
			],
		},
	};
}

/**
 * Builds the labels and mode options of each boundary of the sale price window.
 *
 * The msgids and contexts are the classic editor's, so one translation serves both.
 *
 * @since TBD
 *
 * @return {{start: BoundarySettings, end: BoundarySettings}} The labels and mode options, keyed by boundary.
 */
function getSalePriceSettings() {
	return {
		start: {
			labels: {
				mode: __( 'Sale Starts:', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sale price start.
				value: __( 'Number of units before the event that the sale price starts', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sale price start.
				unit: __( 'Unit of the sale price start', 'event-tickets' ),
				// translators: The screen reader label of the date picker of a specific ticket sale price start.
				date: __( 'Sale price start date', 'event-tickets' ),
			},
			modeOptions: [
				{ value: MODE_NOW, label: _x( 'Now', 'When the sale price starts.', 'event-tickets' ) },
				{
					value: MODE_RELATIVE,
					label: _x( 'On a relative date', 'When the sale price starts.', 'event-tickets' ),
				},
				{
					value: MODE_SPECIFIC,
					label: _x( 'On a specific date', 'When the sale price starts.', 'event-tickets' ),
				},
			],
		},
		end: {
			labels: {
				mode: __( 'Sale Ends:', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sale price end.
				value: __( 'Number of units before the event that the sale price ends', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sale price end.
				unit: __( 'Unit of the sale price end', 'event-tickets' ),
				// translators: The screen reader label of the date picker of a specific ticket sale price end.
				date: __( 'Sale price end date', 'event-tickets' ),
			},
			modeOptions: [
				{
					value: MODE_RELATIVE,
					label: _x( 'On a relative date', 'When the sale price ends.', 'event-tickets' ),
				},
				{
					value: MODE_SPECIFIC,
					label: _x( 'On a specific date', 'When the sale price ends.', 'event-tickets' ),
				},
			],
		},
	};
}

/**
 * Gets the text of a relative number out of the range a kind takes.
 *
 * The msgid is the classic editor's, so one translation serves both.
 *
 * @since TBD
 *
 * @param {WindowKind} kind The window kind.
 *
 * @return {string} The text.
 */
function getRangeText( kind ) {
	return sprintf(
		// translators: %1$d is the smallest number a relative sale date takes, %2$d the largest.
		__( 'Enter a number from %1$d to %2$d.', 'event-tickets' ),
		MIN_VALUE,
		kind.maxValue
	);
}

/**
 * The ticket's sales window, as the Ticket block shows and sends it.
 *
 * @since TBD
 *
 * @type {BlockWindowKind}
 */
export const BLOCK_SALES_WINDOW = Object.freeze( {
	...SALES_WINDOW,
	getBoundarySettings: getSalesWindowSettings,
	isAdded: () => true,
	isKept: () => true,
	// A new ticket shows the options with the block, before the admin has changed anything to save.
	marksNewRuleAsChange: false,
	requestKey: 'ticket[relative_sale_dates]',
	// An empty rule removes the stored one.
	emptyValue: '',
	readStored: ( ticket ) => ticket.relative_sale_dates ?? null,
	isAnswered: ( ticket ) => undefined !== ticket.relative_sale_dates,
	readFormDates: getTicketFormDates,
	errorTexts: Object.freeze( {
		[ ENDS_BEFORE_START ]: () =>
			__( 'Ticket sales cannot end before they start. Please adjust the sales window.', 'event-tickets' ),
		[ RELATIVE_VALUE_OUT_OF_RANGE ]: getRangeText,
	} ),
} );

/**
 * The ticket's sale price window, as the Ticket block shows and sends it.
 *
 * @since TBD
 *
 * @type {BlockWindowKind}
 */
export const BLOCK_SALE_PRICE_WINDOW = Object.freeze( {
	...SALE_PRICE_WINDOW,
	parent: BLOCK_SALES_WINDOW,
	getBoundarySettings: getSalePriceSettings,
	// The server drops the rule along with an unchecked sale price.
	isAdded: isSalePriceChecked,
	// The server judges the window of a sale price it keeps: a checked one lower than the price.
	isKept: isSalePriceKept,
	/*
	 * The options show once the admin checks the sale price: Create or Update was checked for that change before these
	 * defaults existed, and is checked again only on a legacy store change.
	 */
	marksNewRuleAsChange: true,
	requestKey: 'ticket[sale_price][relative]',
	// The server keeps the stored rule, or the dates of a sale price stored without one.
	emptyValue: undefined,
	readStored: ( ticket ) => ( ticket.sale_price_data?.enabled ? ticket.sale_price_data.relative ?? null : undefined ),
	isAnswered: ( ticket ) => undefined !== ticket.sale_price_data,
	readFormDates: getTicketSalePriceFormDates,
	errorTexts: Object.freeze( {
		[ ENDS_BEFORE_START ]: () =>
			__( 'The sale price cannot end before it starts. Please adjust the sale price window.', 'event-tickets' ),
		[ RELATIVE_VALUE_OUT_OF_RANGE ]: getRangeText,
		[ OUTSIDE_PARENT ]: () =>
			__(
				'The sale price window falls outside the ticket sales window. Please adjust the dates.',
				'event-tickets'
			),
	} ),
} );

/**
 * Every kind the Ticket block shows, the sales window first: the sale price is judged against it.
 *
 * @since TBD
 *
 * @type {BlockWindowKind[]}
 */
export const BLOCK_WINDOW_KINDS = Object.freeze( [ BLOCK_SALES_WINDOW, BLOCK_SALE_PRICE_WINDOW ] );
