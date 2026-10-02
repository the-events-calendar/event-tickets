import React from 'react';
import { act } from 'react-test-renderer';
import { dispatch, select } from '@wordpress/data';
import { SelectControl, TextControl } from '@wordpress/components';
import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import '@tec/tickets/relative-sale-dates/block-editor/store';
import SalesWindow from '@tec/tickets/relative-sale-dates/block-editor/sales-window';

jest.mock( '@wordpress/data', () => require( './wordpress-data-registry' ) );

// `@wordpress/element` is not installed: the block editor provides it at runtime, as a re-export of React.
jest.mock( '@wordpress/element', () => require( 'react' ) );

// The shared manual mock of `@wordpress/i18n` has no `_x`; the real package translates nothing without data.
jest.mock( '@wordpress/i18n', () => jest.requireActual( '@wordpress/i18n' ) );

/*
 * `@wordpress/components` is not installed: the block editor provides it at runtime. Native controls that keep the
 * props are enough to read and drive the options.
 */
jest.mock( '@wordpress/components', () => ( {
	SelectControl: ( { label, value, options, onChange } ) => (
		<select aria-label={ label } value={ value } onChange={ ( event ) => onChange( event.target.value ) }>
			{ options.map( ( option ) => (
				<option key={ option.value } value={ option.value }>
					{ option.label }
				</option>
			) ) }
		</select>
	),
	TextControl: ( { label, value, onChange, hideLabelFromVision, ...rest } ) => (
		<input aria-label={ label } value={ value } onChange={ ( event ) => onChange( event.target.value ) } { ...rest } />
	),
} ) );

const UNIT_MINUTES = 60;
const UNIT_HOURS = 3600;
const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

const START_LABELS = {
	mode: 'From',
	value: 'Number of units before the event that sales start',
	unit: 'Unit of the sales start',
	anchor: 'What the sales start is measured from',
};

const END_LABELS = {
	mode: 'To',
	value: 'Number of units before the event that sales end',
	unit: 'Unit of the sales end',
	anchor: 'What the sales end is measured from',
};

// The relative values the server localizes as the defaults of each end, as `Editor` defines them.
const LOCALIZED_DEFAULTS = {
	start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
	end: { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'start' },
};

const Picker = () => null;

let clientCount = 0;
let root;

/**
 * Returns a client ID no earlier spec has used: the store is registered once for the whole file.
 *
 * @return {string} The client ID.
 */
function newClientId() {
	clientCount++;

	return `ticket-block-${ clientCount }`;
}

/**
 * Renders the sales window options of a ticket block.
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
function renderSalesWindow( clientId ) {
	let rendered;

	act( () => {
		rendered = global.renderer.create(
			<SalesWindow
				clientId={ clientId }
				picker={ <Picker className="tribe-editor__ticket__duration-picker" fromTime="10:00" toTime="18:00" /> }
			/>
		);
	} );

	root = rendered.root;
}

/**
 * Finds the control with the given label.
 *
 * @param {Function} type  The control component.
 * @param {string}   label The control label.
 *
 * @return {Object|undefined} The control's test instance, or `undefined` when it is not rendered.
 */
function findControl( type, label ) {
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
function change( type, label, value ) {
	act( () => {
		findControl( type, label ).props.onChange( value );
	} );
}

/**
 * Returns the class names of the pickers rendered.
 *
 * @return {string[]} The class names, one per picker.
 */
function getPickerClassNames() {
	return root.findAllByType( Picker ).map( ( picker ) => picker.props.className );
}

describe( 'the Ticket block sales window options', () => {
	beforeEach( () => {
		window.tribe = { tickets: { data: { blocks: { actions: legacyActions, selectors: legacySelectors } } } };
		window.tec = { tickets: { relativeSaleDates: { blockEditorData: { defaults: LOCALIZED_DEFAULTS } } } };
		window.__tribe_common_store__ = { dispatch: jest.fn(), getState: () => ( {} ) };
	} );

	afterEach( () => {
		delete window.tribe;
		delete window.tec;
		delete window.__tribe_common_store__;
	} );

	describe( 'for a new ticket', () => {
		it( 'should start sales now and end them when the event starts', () => {
			renderSalesWindow( newClientId() );

			expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'default' );
			expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'default' );
			expect( findControl( TextControl, START_LABELS.value ) ).toBeUndefined();
			expect( root.findAllByType( Picker ) ).toHaveLength( 0 );
		} );

		it( 'should keep the defaults as the draft, with the relative values each end offers', () => {
			const clientId = newClientId();

			renderSalesWindow( clientId );

			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( {
				start: { mode: 'default', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
				end: { mode: 'default', value: 1, unit: UNIT_HOURS, anchor: 'start' },
			} );
		} );

		it( 'should not mark the ticket as changed for its defaults', () => {
			renderSalesWindow( newClientId() );

			expect( window.__tribe_common_store__.dispatch ).not.toHaveBeenCalled();
		} );
	} );

	it( 'should offer the relative values the server localizes as the defaults', () => {
		const defaults = {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'end' },
			end: { mode: 'relative', value: 30, unit: UNIT_MINUTES, anchor: 'end' },
		};
		window.tec.tickets.relativeSaleDates.blockEditorData.defaults = defaults;
		const clientId = newClientId();

		renderSalesWindow( clientId );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( {
			start: { ...defaults.start, mode: 'default' },
			end: { ...defaults.end, mode: 'default' },
		} );
	} );

	it( 'should show the specific dates of a ticket saved without a rule, and leave its draft alone', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, null );

		renderSalesWindow( clientId );

		expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'specific' );
		expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'specific' );
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeNull();
	} );

	it( 'should show the relative values of the rule a ticket was saved with', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'end' },
			end: { mode: 'default' },
		} );

		renderSalesWindow( clientId );

		expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'relative' );
		expect( findControl( TextControl, START_LABELS.value ).props.value ).toBe( 3 );
		expect( findControl( SelectControl, START_LABELS.unit ).props.value ).toBe( String( UNIT_DAYS ) );
		expect( findControl( SelectControl, START_LABELS.anchor ).props.value ).toBe( 'end' );
		expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'default' );
	} );

	it( 'should offer 2 weeks before the event starts when the start becomes relative', () => {
		const clientId = newClientId();
		renderSalesWindow( clientId );

		change( SelectControl, START_LABELS.mode, 'relative' );

		expect( findControl( TextControl, START_LABELS.value ).props.value ).toBe( 2 );
		expect( findControl( SelectControl, START_LABELS.unit ).props.value ).toBe( String( UNIT_WEEKS ) );
		expect( findControl( SelectControl, START_LABELS.anchor ).props.value ).toBe( 'start' );
		expect( select( STORE_NAME ).getDraftRule( clientId ).start ).toStrictEqual( {
			mode: 'relative',
			value: 2,
			unit: UNIT_WEEKS,
			anchor: 'start',
		} );
	} );

	it( 'should offer 1 hour before the event starts when the end becomes relative', () => {
		const clientId = newClientId();
		renderSalesWindow( clientId );

		change( SelectControl, END_LABELS.mode, 'relative' );

		expect( select( STORE_NAME ).getDraftRule( clientId ).end ).toStrictEqual( {
			mode: 'relative',
			value: 1,
			unit: UNIT_HOURS,
			anchor: 'start',
		} );
	} );

	it( 'should mark the ticket as changed when the admin changes an option', () => {
		const clientId = newClientId();
		renderSalesWindow( clientId );

		change( SelectControl, END_LABELS.mode, 'relative' );

		expect( window.__tribe_common_store__.dispatch ).toHaveBeenCalledWith( legacyActions.setTicketHasChanges( clientId, true ) );
	} );

	it( 'should take a relative number from 1 to 60', () => {
		renderSalesWindow( newClientId() );
		change( SelectControl, START_LABELS.mode, 'relative' );

		const { type, min, max, step } = findControl( TextControl, START_LABELS.value ).props;

		expect( { type, min, max, step } ).toStrictEqual( { type: 'number', min: 1, max: 60, step: 1 } );
	} );

	it( 'should label the relative number, unit and anchor for screen readers only', () => {
		renderSalesWindow( newClientId() );
		change( SelectControl, START_LABELS.mode, 'relative' );

		expect( findControl( TextControl, START_LABELS.value ).props.hideLabelFromVision ).toBe( true );
		expect( findControl( SelectControl, START_LABELS.unit ).props.hideLabelFromVision ).toBe( true );
		expect( findControl( SelectControl, START_LABELS.anchor ).props.hideLabelFromVision ).toBe( true );
		expect( findControl( SelectControl, START_LABELS.mode ).props.hideLabelFromVision ).toBeUndefined();
	} );

	it( 'should name the units in the form the number takes', () => {
		renderSalesWindow( newClientId() );
		change( SelectControl, END_LABELS.mode, 'relative' );
		const unitLabels = () => findControl( SelectControl, END_LABELS.unit ).props.options.map( ( { label } ) => label );

		expect( unitLabels() ).toStrictEqual( [ 'minute', 'hour', 'day', 'week' ] );

		change( TextControl, END_LABELS.value, '5' );

		expect( unitLabels() ).toStrictEqual( [ 'minutes', 'hours', 'days', 'weeks' ] );
	} );

	it( 'should keep the relative number, unit and anchor the admin picks in the draft', () => {
		const clientId = newClientId();
		renderSalesWindow( clientId );
		change( SelectControl, START_LABELS.mode, 'relative' );

		change( TextControl, START_LABELS.value, '45' );
		change( SelectControl, START_LABELS.unit, String( UNIT_MINUTES ) );
		change( SelectControl, START_LABELS.anchor, 'end' );

		expect( select( STORE_NAME ).getDraftRule( clientId ).start ).toStrictEqual( {
			mode: 'relative',
			value: 45,
			unit: UNIT_MINUTES,
			anchor: 'end',
		} );
	} );

	it.each( [
		[ '75', 60 ],
		[ '0', 1 ],
		[ '-3', 1 ],
	] )( 'should keep a typed number of %s within 1 to 60, as %d', ( typed, kept ) => {
		const clientId = newClientId();
		renderSalesWindow( clientId );
		change( SelectControl, END_LABELS.mode, 'relative' );

		change( TextControl, END_LABELS.value, typed );

		expect( select( STORE_NAME ).getDraftRule( clientId ).end.value ).toBe( kept );
	} );

	it( 'should keep a cleared number empty, for the admin to type a new one', () => {
		const clientId = newClientId();
		renderSalesWindow( clientId );
		change( SelectControl, END_LABELS.mode, 'relative' );

		change( TextControl, END_LABELS.value, '' );

		expect( select( STORE_NAME ).getDraftRule( clientId ).end.value ).toBe( '' );
		expect( findControl( TextControl, END_LABELS.value ).props.value ).toBe( '' );
	} );

	it( 'should show only the start of the picker for a specific start', () => {
		renderSalesWindow( newClientId() );

		change( SelectControl, START_LABELS.mode, 'specific' );

		expect( getPickerClassNames() ).toStrictEqual( [
			'tribe-editor__ticket__duration-picker tec-tickets-relative-sale-dates__picker--start',
		] );
		expect( root.findByType( Picker ).props.fromTime ).toBe( '10:00' );
	} );

	it( 'should show only the end of the picker for a specific end', () => {
		renderSalesWindow( newClientId() );

		change( SelectControl, END_LABELS.mode, 'specific' );

		expect( getPickerClassNames() ).toStrictEqual( [
			'tribe-editor__ticket__duration-picker tec-tickets-relative-sale-dates__picker--end',
		] );
	} );

	it( 'should remember the relative values of an end the admin switches away from', () => {
		const clientId = newClientId();
		renderSalesWindow( clientId );
		change( SelectControl, START_LABELS.mode, 'relative' );
		change( TextControl, START_LABELS.value, '10' );

		change( SelectControl, START_LABELS.mode, 'default' );
		change( SelectControl, START_LABELS.mode, 'relative' );

		expect( findControl( TextControl, START_LABELS.value ).props.value ).toBe( 10 );
	} );
} );
