import React from 'react';
import { act } from 'react-test-renderer';
import { dispatch, select } from '@wordpress/data';
import { SelectControl, TextControl } from '@wordpress/components';
import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import '@tec/tickets/relative-sale-dates/block-editor/store';
import SalePriceWindow from '@tec/tickets/relative-sale-dates/block-editor/sale-price-window';
import { DEFAULT_EVENT, clearBlockEditorGlobals, setBlockEditorData, setEventState } from './block-editor-event-state';

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

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

const START_LABELS = {
	mode: 'Sale Starts:',
	value: 'Number of units before the event that the sale price starts',
	unit: 'Unit of the sale price start',
	date: 'Sale price start date',
};

const END_LABELS = {
	mode: 'Sale Ends:',
	value: 'Number of units before the event that the sale price ends',
	unit: 'Unit of the sale price end',
	date: 'Sale price end date',
};

const StartPicker = () => null;
const EndPicker = () => null;

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
 * Renders the sale price window options of a ticket block.
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
function renderSalePriceWindow( clientId ) {
	let rendered;

	act( () => {
		rendered = global.renderer.create(
			<SalePriceWindow
				clientId={ clientId }
				pickers={ {
					start: <StartPicker value="September 1, 2040" inputProps={ { disabled: false } } />,
					end: <EndPicker value="October 1, 2040" inputProps={ { disabled: false } } />,
				} }
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
 * Returns the values of the options of the select with the given label.
 *
 * @param {string} label The select label.
 *
 * @return {string[]} The option values.
 */
function getOptionValues( label ) {
	return findControl( SelectControl, label ).props.options.map( ( { value } ) => value );
}

describe( 'the Ticket block sale price window options', () => {
	beforeEach( () => {
		window.tribe = { tickets: { data: { blocks: { actions: legacyActions, selectors: legacySelectors } } } };
		setBlockEditorData();
		jest.spyOn( setEventState( DEFAULT_EVENT ), 'dispatch' );
	} );

	afterEach( () => {
		delete window.tribe;
		clearBlockEditorGlobals();
	} );

	describe( 'for a new ticket', () => {
		it( 'should start the sale price now and end it 1 week before the event starts', () => {
			renderSalePriceWindow( newClientId() );

			expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'now' );
			expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'relative' );
			expect( findControl( TextControl, START_LABELS.value ) ).toBeUndefined();
			expect( findControl( TextControl, END_LABELS.value ).props.value ).toBe( 1 );
			expect( findControl( SelectControl, END_LABELS.unit ).props.value ).toBe( String( UNIT_WEEKS ) );
			expect( root.findAllByType( StartPicker ) ).toHaveLength( 0 );
			expect( root.findAllByType( EndPicker ) ).toHaveLength( 0 );
		} );

		it( 'should keep the defaults as the sale price draft, with the relative values each boundary offers', () => {
			const clientId = newClientId();

			renderSalePriceWindow( clientId );

			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( {
				start: { mode: 'now', value: 2, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );
		} );

		it( 'should not mark the ticket as changed for its defaults', () => {
			renderSalePriceWindow( newClientId() );

			expect( window.__tribe_common_store__.dispatch ).not.toHaveBeenCalled();
		} );
	} );

	it( 'should offer the defaults the server localizes', () => {
		const salePriceDefaults = {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS },
			end: { mode: 'specific', value: 5, unit: UNIT_DAYS },
		};
		window.tec.tickets.relativeSaleDates.blockEditorData.salePriceDefaults = salePriceDefaults;
		const clientId = newClientId();

		renderSalePriceWindow( clientId );

		expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( salePriceDefaults );
	} );

	it( 'should show the specific dates of a sale price saved without a rule, and leave its draft alone', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setSalePriceRule( clientId, null );

		renderSalePriceWindow( clientId );

		expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'specific' );
		expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'specific' );
		expect( root.findAllByType( StartPicker ) ).toHaveLength( 1 );
		expect( root.findAllByType( EndPicker ) ).toHaveLength( 1 );
		expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toBeNull();
	} );

	it( 'should show the relative values of the sale price rule a ticket was saved with', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setSalePriceRule( clientId, {
			start: { mode: 'relative', value: 10, unit: UNIT_DAYS },
			end: { mode: 'specific' },
		} );

		renderSalePriceWindow( clientId );

		expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'relative' );
		expect( findControl( TextControl, START_LABELS.value ).props.value ).toBe( 10 );
		expect( findControl( SelectControl, START_LABELS.unit ).props.value ).toBe( String( UNIT_DAYS ) );
		expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'specific' );
	} );

	it( 'should offer Now, a relative date and a specific date for the start, and no Now for the end', () => {
		renderSalePriceWindow( newClientId() );

		expect( getOptionValues( START_LABELS.mode ) ).toStrictEqual( [ 'now', 'relative', 'specific' ] );
		expect( getOptionValues( END_LABELS.mode ) ).toStrictEqual( [ 'relative', 'specific' ] );
	} );

	it( 'should offer 2 weeks before the event starts when the start becomes relative', () => {
		const clientId = newClientId();
		renderSalePriceWindow( clientId );

		change( SelectControl, START_LABELS.mode, 'relative' );

		expect( findControl( TextControl, START_LABELS.value ).props.value ).toBe( 2 );
		expect( findControl( SelectControl, START_LABELS.unit ).props.value ).toBe( String( UNIT_WEEKS ) );
		expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ).start ).toStrictEqual( {
			mode: 'relative',
			value: 2,
			unit: UNIT_WEEKS,
		} );
	} );

	it( 'should count both relative boundaries from the event start, with no choice of anchor', () => {
		renderSalePriceWindow( newClientId() );

		change( SelectControl, START_LABELS.mode, 'relative' );

		const anchors = root.findAll(
			( node ) => 'span' === node.type && 'before the event starts' === node.props.children
		);
		const optionLabels = root
			.findAllByType( SelectControl )
			.flatMap( ( control ) => control.props.options.map( ( { label } ) => label ) );

		expect( anchors.map( ( node ) => node.props.children ) ).toStrictEqual( [
			'before the event starts',
			'before the event starts',
		] );
		expect( optionLabels ).not.toContain( 'before the event starts' );
		expect( optionLabels ).not.toContain( 'before the event ends' );
	} );

	it( 'should mark the ticket as changed when the admin changes an option', () => {
		const clientId = newClientId();
		renderSalePriceWindow( clientId );

		change( SelectControl, START_LABELS.mode, 'relative' );

		expect( window.__tribe_common_store__.dispatch ).toHaveBeenCalledWith(
			legacyActions.setTicketHasChanges( clientId, true )
		);
	} );

	it( 'should take a relative number from 1 to 30', () => {
		renderSalePriceWindow( newClientId() );

		const { type, min, max, step } = findControl( TextControl, END_LABELS.value ).props;

		expect( { type, min, max, step } ).toStrictEqual( { type: 'number', min: 1, max: 30, step: 1 } );
	} );

	it( 'should keep a typed number within 1 to 30, as an integer', () => {
		const clientId = newClientId();
		renderSalePriceWindow( clientId );

		change( TextControl, END_LABELS.value, '45' );

		expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ).end.value ).toBe( 30 );

		change( TextControl, END_LABELS.value, '0' );

		expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ).end.value ).toBe( 1 );
	} );

	it( 'should leave a cleared number empty, so the admin can type another', () => {
		const clientId = newClientId();
		renderSalePriceWindow( clientId );

		change( TextControl, END_LABELS.value, '' );

		expect( findControl( TextControl, END_LABELS.value ).props.value ).toBe( '' );
		expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ).end.value ).toBe( '' );
	} );

	it( 'should offer days and weeks, named in the form the number takes, and keep the unit as an integer', () => {
		const clientId = newClientId();
		renderSalePriceWindow( clientId );
		const unitOptions = () => findControl( SelectControl, END_LABELS.unit ).props.options;

		expect( unitOptions() ).toStrictEqual( [
			{ value: String( UNIT_DAYS ), label: 'day' },
			{ value: String( UNIT_WEEKS ), label: 'week' },
		] );

		change( TextControl, END_LABELS.value, '5' );
		change( SelectControl, END_LABELS.unit, String( UNIT_DAYS ) );

		expect( unitOptions().map( ( { label } ) => label ) ).toStrictEqual( [ 'days', 'weeks' ] );
		expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ).end ).toStrictEqual( {
			mode: 'relative',
			value: 5,
			unit: UNIT_DAYS,
		} );
	} );

	it( 'should label the relative number and unit for screen readers only', () => {
		renderSalePriceWindow( newClientId() );

		expect( findControl( TextControl, END_LABELS.value ).props.hideLabelFromVision ).toBe( true );
		expect( findControl( SelectControl, END_LABELS.unit ).props.hideLabelFromVision ).toBe( true );
		expect( findControl( SelectControl, END_LABELS.mode ).props.hideLabelFromVision ).toBeUndefined();
	} );

	it( 'should show only the picker of a boundary set to a specific date, labelled for screen readers', () => {
		renderSalePriceWindow( newClientId() );

		change( SelectControl, END_LABELS.mode, 'specific' );

		const [ endPicker ] = root.findAllByType( EndPicker );
		expect( root.findAllByType( StartPicker ) ).toHaveLength( 0 );
		expect( endPicker.props.value ).toBe( 'October 1, 2040' );
		expect( endPicker.props.inputProps ).toStrictEqual( { disabled: false, 'aria-label': END_LABELS.date } );
	} );

	it( 'should keep the sale price draft apart from the sales window rule', () => {
		const clientId = newClientId();
		const salesWindowRule = { start: { mode: 'default' }, end: { mode: 'default' } };
		dispatch( STORE_NAME ).setRule( clientId, salesWindowRule );
		renderSalePriceWindow( clientId );

		change( SelectControl, START_LABELS.mode, 'specific' );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( salesWindowRule );
	} );
} );
