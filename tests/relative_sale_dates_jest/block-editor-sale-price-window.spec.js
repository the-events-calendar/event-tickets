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
 * Returns the helper text paragraphs rendered.
 *
 * @return {Object[]} The paragraphs' test instances.
 */
function findHelpers() {
	return root.findAll(
		( node ) => 'p' === node.type && 'tec-tickets-relative-sale-dates__helper' === node.props.className
	);
}

/**
 * Returns the sale price length the helper text under *Sale Ends* shows.
 *
 * @return {string|undefined} The helper text, or `undefined` when it is not rendered.
 */
function getLengthText() {
	const [ helper ] = findHelpers();

	return helper ? helper.props.children : undefined;
}

/**
 * Registers the ticket block in the legacy store with the specific sale price dates its form holds.
 *
 * @param {string}      clientId The client ID of the ticket block.
 * @param {string|null} start    The sale price start date, `YYYY-MM-DD`, or `null` for none.
 * @param {string|null} end      The sale price end date, `YYYY-MM-DD`, or `null` for none.
 *
 * @return {void}
 */
function setFormSalePriceDates( clientId, start, end ) {
	act( () => {
		window.__tribe_common_store__.dispatch( legacyActions.registerTicketBlock( clientId ) );
		window.__tribe_common_store__.dispatch( legacyActions.setTicketTempSaleStartDate( clientId, start ?? '' ) );
		window.__tribe_common_store__.dispatch( legacyActions.setTicketTempSaleEndDate( clientId, end ?? '' ) );
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
		jest.useRealTimers();
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

	describe( 'the sale price length under Sale Ends', () => {
		// The event starts on 2040-10-20 at 19:00 in São Paulo, so 1 week before it is 2040-10-13.

		const relativeRule = ( start, end ) => ( {
			start: { mode: 'relative', ...start },
			end: { mode: 'relative', ...end },
		} );

		it( 'should count a whole number of weeks in weeks', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } )
			);

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( 'Tickets on sale for 2 weeks' );
		} );

		it( 'should count any other length in days', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule(
				clientId,
				relativeRule( { value: 10, unit: UNIT_DAYS }, { value: 1, unit: UNIT_WEEKS } )
			);

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( 'Tickets on sale for 3 days' );
		} );

		it( 'should count a Now start from today in the event timezone', () => {
			// 02:00 in UTC on 2040-10-01 is still 2040-09-30 in São Paulo: 13 days before 2040-10-13, not 12.
			jest.useFakeTimers( { now: new Date( '2040-10-01T02:00:00Z' ) } );

			renderSalePriceWindow( newClientId() );

			expect( getLengthText() ).toBe( 'Tickets on sale for 13 days' );
		} );

		it( 'should count a specific boundary from the date the form holds for it', () => {
			const clientId = newClientId();
			setFormSalePriceDates( clientId, '2040-10-06', null );
			dispatch( STORE_NAME ).setSalePriceRule( clientId, {
				start: { mode: 'specific' },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( 'Tickets on sale for 1 week' );
		} );

		it( 'should count again when the admin changes the date the form holds', () => {
			const clientId = newClientId();
			setFormSalePriceDates( clientId, '2040-10-06', null );
			dispatch( STORE_NAME ).setSalePriceRule( clientId, {
				start: { mode: 'specific' },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );
			renderSalePriceWindow( clientId );

			setFormSalePriceDates( clientId, '2040-10-11', null );

			expect( getLengthText() ).toBe( 'Tickets on sale for 2 days' );
		} );

		it( 'should count again when the admin changes the event date, without saving', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule(
				clientId,
				relativeRule( { value: 10, unit: UNIT_DAYS }, { value: 1, unit: UNIT_WEEKS } )
			);
			setFormSalePriceDates( clientId, null, '2040-10-30' );
			renderSalePriceWindow( clientId );

			change( SelectControl, END_LABELS.mode, 'specific' );

			expect( getLengthText() ).toBe( 'Tickets on sale for 20 days' );

			act( () => {
				window.__tribe_common_store__.dispatch( {
					type: 'SET_DATETIME',
					datetime: { ...DEFAULT_EVENT, start: '2040-10-27 19:00:00', end: '2040-10-27 22:30:00' },
				} );
			} );

			expect( getLengthText() ).toBe( 'Tickets on sale for 13 days' );
		} );

		it( 'should count again when the admin changes the rule', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } )
			);
			renderSalePriceWindow( clientId );

			change( TextControl, START_LABELS.value, '4' );

			expect( getLengthText() ).toBe( 'Tickets on sale for 3 weeks' );
		} );

		it( 'should count again when the admin changes a unit', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule(
				clientId,
				relativeRule( { value: 10, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } )
			);
			renderSalePriceWindow( clientId );

			change( SelectControl, START_LABELS.unit, String( UNIT_DAYS ) );

			expect( getLengthText() ).toBe( 'Tickets on sale for 3 days' );
		} );

		it( 'should leave the length out while the Sale Ends number is cleared, not count it from the event start', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } )
			);
			renderSalePriceWindow( clientId );

			change( TextControl, END_LABELS.value, '' );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should leave the length out while the Sale Starts number is cleared, not count it from the event start', () => {
			const clientId = newClientId();
			// A start counted from the event start, 2040-10-20, would read as 10 days to this end.
			setFormSalePriceDates( clientId, null, '2040-10-30' );
			dispatch( STORE_NAME ).setSalePriceRule( clientId, {
				start: { mode: 'relative', value: 3, unit: UNIT_WEEKS },
				end: { mode: 'specific' },
			} );
			renderSalePriceWindow( clientId );

			change( TextControl, START_LABELS.value, '' );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should leave the length out while a specific boundary has no date', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule( clientId, null );

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should leave the length out while the event dates cannot be read', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setSalePriceRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } )
			);
			delete window.tec.events;

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should announce the length politely under Sale Ends only', () => {
			renderSalePriceWindow( newClientId() );

			const [ endBoundary ] = root.findAll(
				( node ) => 'div' === node.type && node.props.className?.includes( '__end--end' )
			);
			const [ helper ] = endBoundary.findAll( ( node ) => 'p' === node.type );

			expect( helper.props[ 'aria-live' ] ).toBe( 'polite' );
			expect( findHelpers() ).toHaveLength( 1 );
		} );
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
