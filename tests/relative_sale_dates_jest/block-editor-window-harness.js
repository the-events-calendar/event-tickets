/**
 * Renders the Ticket block's window options and drives them as the admin would, for the specs of every window kind.
 *
 * A spec mocks `@wordpress/components` with `wordpress-components-controls.js`, and `@wordpress/data`, `@wordpress/element`
 * and `@wordpress/i18n`, as `block-editor-window.spec.js` does.
 *
 * @since TBD
 */
import React from 'react';
import { act } from 'react-test-renderer';
import { SelectControl } from '@wordpress/components';
import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { SALES_WINDOW, SALE_PRICE_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';
import '@tec/tickets/relative-sale-dates/block-editor/store';
import SalesWindow from '@tec/tickets/relative-sale-dates/block-editor/sales-window';
import SalePriceWindow from '@tec/tickets/relative-sale-dates/block-editor/sale-price-window';
import { DEFAULT_EVENT, clearBlockEditorGlobals, setBlockEditorData, setEventState } from './block-editor-event-state';

export const Picker = () => null;
export const StartPicker = () => null;
export const EndPicker = () => null;

export const SALES_LABELS = {
	start: {
		mode: 'From',
		value: 'Number of units before the event that sales start',
		unit: 'Unit of the sales start',
		anchor: 'What the sales start is measured from',
	},
	end: {
		mode: 'To',
		value: 'Number of units before the event that sales end',
		unit: 'Unit of the sales end',
		anchor: 'What the sales end is measured from',
	},
};

export const SALE_PRICE_LABELS = {
	start: {
		mode: 'Sale Starts:',
		value: 'Number of units before the event that the sale price starts',
		unit: 'Unit of the sale price start',
		date: 'Sale price start date',
	},
	end: {
		mode: 'Sale Ends:',
		value: 'Number of units before the event that the sale price ends',
		unit: 'Unit of the sale price end',
		date: 'Sale price end date',
	},
};

let clientCount = 0;
let root;

/**
 * Returns a client ID no earlier spec has used: the store is registered once for the whole file.
 *
 * @return {string} The client ID.
 */
export function newClientId() {
	clientCount++;

	return `ticket-block-${ clientCount }`;
}

/**
 * Renders an element as the options under test.
 *
 * @param {Object} element The element.
 *
 * @return {void}
 */
function render( element ) {
	let rendered;

	act( () => {
		rendered = global.renderer.create( element );
	} );

	root = rendered.root;
}

/**
 * Renders the sales window options of a ticket block, around the Sale Duration picker.
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function renderSalesWindow( clientId ) {
	render(
		<SalesWindow
			clientId={ clientId }
			picker={ <Picker className="tribe-editor__ticket__duration-picker" fromTime="10:00" toTime="18:00" /> }
		/>
	);
}

/**
 * Renders the sale price window options of a ticket block, with the date pickers of its sale price section.
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function renderSalePriceWindow( clientId ) {
	// As the legacy section builds them: each picker is bounded by the other boundary's date.
	const fromDate = new Date( 2040, 8, 1 );
	const toDate = new Date( 2040, 9, 1 );

	render(
		<SalePriceWindow
			clientId={ clientId }
			pickers={ {
				start: (
					<StartPicker
						value="September 1, 2040"
						inputProps={ { disabled: false } }
						dayPickerProps={ {
							selectedDays: [ fromDate ],
							disabledDays: { after: toDate },
							toMonth: toDate,
						} }
					/>
				),
				end: (
					<EndPicker
						value="October 1, 2040"
						inputProps={ { disabled: false } }
						dayPickerProps={ {
							selectedDays: [ fromDate ],
							disabledDays: { before: fromDate },
							month: fromDate,
							fromMonth: fromDate,
						} }
					/>
				),
			} }
		/>
	);
}

/**
 * Returns the root of the options rendered last.
 *
 * @return {Object} The test renderer's root instance.
 */
export function getRoot() {
	return root;
}

/**
 * Finds the control with the given label.
 *
 * @param {Function} type  The control component.
 * @param {string}   label The control label.
 *
 * @return {Object|undefined} The control's test instance, or `undefined` when it is not rendered.
 */
export function findControl( type, label ) {
	return root.findAll( ( node ) => node.type === type && node.props.label === label )[ 0 ];
}

/**
 * Changes the value of the control with the given label, as the admin would.
 *
 * @param {Function} type  The control component.
 * @param {string}   label The control label.
 * @param {string}   value The new value, as the control reports it.
 *
 * @return {void}
 */
export function change( type, label, value ) {
	act( () => {
		findControl( type, label ).props.onChange( value );
	} );
}

/**
 * Returns the values of the options of the select with the given label.
 *
 * @param {string} label The select label.
 *
 * @return {string[]} The option values.
 */
export function getOptionValues( label ) {
	return findControl( SelectControl, label ).props.options.map( ( { value } ) => value );
}

/**
 * Sets the globals the options read, with the legacy ticket code and the event dates, and spies on the common store.
 *
 * @return {Object} The common store.
 */
export function setUpBlockEditor() {
	window.tribe = { tickets: { data: { blocks: { actions: legacyActions, selectors: legacySelectors } } } };
	setBlockEditorData();
	const commonStore = setEventState( DEFAULT_EVENT );
	jest.spyOn( commonStore, 'dispatch' );

	return commonStore;
}

/**
 * Removes the globals `setUpBlockEditor()` set.
 *
 * @return {void}
 */
export function tearDownBlockEditor() {
	delete window.tribe;
	clearBlockEditorGlobals();
}

/**
 * Counts the sales window pickers each end shows its own half of.
 *
 * @return {{start: number, end: number}} The number of pickers each end shows.
 */
function countSalesPickers() {
	const count = ( name ) =>
		root
			.findAllByType( Picker )
			.filter( ( node ) => node.props.className.includes( `tec-tickets-relative-sale-dates__picker--${ name }` ) )
			.length;

	return { start: count( 'start' ), end: count( 'end' ) };
}

/**
 * The sales window options, as the specs of every kind drive them.
 *
 * @type {{title: string, kind: Object, render: Function, labels: Object, countPickers: Function}}
 */
export const salesWindowCase = {
	title: 'the sales window',
	kind: SALES_WINDOW,
	render: renderSalesWindow,
	labels: SALES_LABELS,
	countPickers: countSalesPickers,
};

/**
 * The sale price window options, as the specs of every kind drive them.
 *
 * @type {{title: string, kind: Object, render: Function, labels: Object, countPickers: Function}}
 */
export const salePriceCase = {
	title: 'the sale price window',
	kind: SALE_PRICE_WINDOW,
	render: renderSalePriceWindow,
	labels: SALE_PRICE_LABELS,
	countPickers: () => ( {
		start: root.findAllByType( StartPicker ).length,
		end: root.findAllByType( EndPicker ).length,
	} ),
};
