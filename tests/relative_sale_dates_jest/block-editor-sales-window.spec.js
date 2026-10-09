import React from 'react';
import { act } from 'react-test-renderer';
import { dispatch, select } from '@wordpress/data';
import { SelectControl, TextControl } from '@wordpress/components';
import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import { DEFAULT_EVENT, setBlockEditorData, setEventState, setTicketFormDates } from './block-editor-event-state';
import {
	Picker,
	SALES_LABELS,
	change,
	findControl,
	getRoot,
	newClientId,
	renderSalesWindow,
	setUpBlockEditor,
	tearDownBlockEditor,
} from './block-editor-window-harness';

jest.mock( '@wordpress/data', () => require( './wordpress-data-registry' ) );

// `@wordpress/element` is not installed: the block editor provides it at runtime, as a re-export of React.
jest.mock( '@wordpress/element', () => require( 'react' ) );

// The shared manual mock of `@wordpress/i18n` has no `_x`; the real package translates nothing without data.
jest.mock( '@wordpress/i18n', () => jest.requireActual( '@wordpress/i18n' ) );

jest.mock( '@wordpress/components', () => require( './wordpress-components-controls' ) );

const UNIT_MINUTES = 60;
const UNIT_HOURS = 3600;
const UNIT_DAYS = 86400;

const START_LABELS = SALES_LABELS.start;
const END_LABELS = SALES_LABELS.end;

let commonStore;

/**
 * Returns the helper texts rendered.
 *
 * @return {string[]} The helper texts, one per end that shows one.
 */
function getHelperTexts() {
	return getRoot()
		.findAll( ( node ) => 'p' === node.type && 'tec-tickets-relative-sale-dates__helper' === node.props.className )
		.map( ( node ) => node.props.children )
		.filter( Boolean );
}

/**
 * Returns what each helper text region rendered holds.
 *
 * @return {string[]} The content of each region, empty while it has no date.
 */
function getHelperRegions() {
	return getRoot()
		.findAll( ( node ) => 'p' === node.type && 'tec-tickets-relative-sale-dates__helper' === node.props.className )
		.map( ( node ) => node.props.children || '' );
}

/**
 * Returns the class names of the pickers rendered.
 *
 * @return {string[]} The class names, one per picker.
 */
function getPickerClassNames() {
	return getRoot()
		.findAllByType( Picker )
		.map( ( picker ) => picker.props.className );
}

describe( 'the Ticket block sales window options', () => {
	beforeEach( () => {
		commonStore = setUpBlockEditor();
	} );

	afterEach( () => {
		jest.useRealTimers();
		tearDownBlockEditor();
	} );

	describe( 'for a new ticket', () => {
		it( 'should start sales now and end them when the event starts', () => {
			renderSalesWindow( newClientId() );

			expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'default' );
			expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'default' );
			expect( findControl( TextControl, START_LABELS.value ) ).toBeUndefined();
			expect( getRoot().findAllByType( Picker ) ).toHaveLength( 0 );
		} );

		it( 'should not mark the ticket as changed for its defaults', () => {
			renderSalesWindow( newClientId() );

			expect( window.__tribe_common_store__.dispatch ).not.toHaveBeenCalled();
		} );
	} );

	it( 'should show the anchor of the rule a ticket was saved with', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'end' },
			end: { mode: 'default' },
		} );

		renderSalesWindow( clientId );

		expect( findControl( SelectControl, START_LABELS.anchor ).props.value ).toBe( 'end' );
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

	describe( 'with the legacy duration error', () => {
		/**
		 * Sets the legacy sales duration error of a ticket, as the legacy picker check does.
		 *
		 * @param {string}  clientId The client ID of the ticket block.
		 * @param {boolean} hasError Whether the ticket has the error.
		 *
		 * @return {void}
		 */
		const setDurationError = ( clientId, hasError ) =>
			act( () => {
				commonStore.dispatch( legacyActions.setTicketHasDurationError( clientId, hasError ) );
			} );

		const hasDurationError = ( clientId ) =>
			legacySelectors.getTicketHasDurationError( commonStore.getState(), { clientId } );

		/**
		 * Renders the options of a ticket block the legacy store knows, with the legacy duration error set.
		 *
		 * @return {string} The client ID of the ticket block.
		 */
		const renderWithDurationError = () => {
			const clientId = newClientId();
			commonStore.dispatch( legacyActions.registerTicketBlock( clientId ) );
			renderSalesWindow( clientId );
			change( SelectControl, START_LABELS.mode, 'specific' );
			change( SelectControl, END_LABELS.mode, 'specific' );
			setDurationError( clientId, true );

			return clientId;
		};

		it( 'should leave it to the legacy check while both ends are specific dates', () => {
			const clientId = renderWithDurationError();

			expect( hasDurationError( clientId ) ).toBe( true );
		} );

		it( 'should clear it once an end no longer shows the specific dates', () => {
			const clientId = renderWithDurationError();

			change( SelectControl, END_LABELS.mode, 'relative' );

			expect( hasDurationError( clientId ) ).toBe( false );
		} );

		// The legacy check runs again on every picker edit, against the dates the relative end hides.
		it( 'should clear it again when a picker edit brings it back while an end is not a specific date', () => {
			const clientId = renderWithDurationError();
			change( SelectControl, END_LABELS.mode, 'relative' );

			setDurationError( clientId, true );

			expect( hasDurationError( clientId ) ).toBe( false );
		} );
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

	it( 'should show only the start of the picker for a specific start', () => {
		renderSalesWindow( newClientId() );

		change( SelectControl, START_LABELS.mode, 'specific' );

		expect( getPickerClassNames() ).toStrictEqual( [
			'tribe-editor__ticket__duration-picker tec-tickets-relative-sale-dates__picker--start',
		] );
		expect( getRoot().findByType( Picker ).props.fromTime ).toBe( '10:00' );
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

	describe( 'window check', () => {
		const ERROR = 'Ticket sales cannot end before they start. Please adjust the sales window.';
		const OUT_OF_RANGE = 'Enter a number from 1 to 60.';

		/**
		 * Returns the error message rendered, if any.
		 *
		 * @return {Object|undefined} The error element's test instance.
		 */
		function findError() {
			return getRoot().findAll( ( node ) => 'string' === typeof node.type && 'alert' === node.props.role )[ 0 ];
		}

		/**
		 * Renders the options of a ticket whose form sends the given sale dates.
		 *
		 * @param {string} clientId The client ID of the ticket block.
		 *
		 * @return {void}
		 */
		function renderTicket( clientId ) {
			setTicketFormDates( commonStore, clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' );
			renderSalesWindow( clientId );
		}

		it( 'should show the error and mark the end invalid while the window ends before it starts', () => {
			renderTicket( newClientId() );
			change( SelectControl, START_LABELS.mode, 'relative' );
			change( SelectControl, START_LABELS.unit, String( UNIT_HOURS ) );
			change( TextControl, START_LABELS.value, '1' );
			change( SelectControl, END_LABELS.mode, 'relative' );
			change( TextControl, END_LABELS.value, '2' );

			const endMode = findControl( SelectControl, END_LABELS.mode );

			// Passed as the end's help, so the control describes itself with it.
			expect( endMode.props.help.props.children ).toBe( ERROR );
			expect( findError().props.children ).toBe( ERROR );
			expect( endMode.props[ 'aria-invalid' ] ).toBe( true );
		} );

		it( 'should style the error as its own, not as the legacy duration error', () => {
			renderTicket( newClientId() );
			change( SelectControl, START_LABELS.mode, 'relative' );
			change( SelectControl, START_LABELS.unit, String( UNIT_HOURS ) );
			change( TextControl, START_LABELS.value, '1' );
			change( SelectControl, END_LABELS.mode, 'relative' );
			change( TextControl, END_LABELS.value, '2' );

			expect( findError().props.className ).toBe( 'tec-tickets-relative-sale-dates__error' );
		} );

		it( 'should show no error for a window that starts before it ends', () => {
			renderTicket( newClientId() );
			change( SelectControl, START_LABELS.mode, 'relative' );
			change( SelectControl, END_LABELS.mode, 'relative' );

			expect( findError() ).toBeUndefined();
			expect( findControl( SelectControl, END_LABELS.mode ).props[ 'aria-invalid' ] ).toBeUndefined();
			expect( findControl( SelectControl, END_LABELS.mode ).props.help ).toBeUndefined();
		} );

		it( 'should name a cleared start number on the number itself, not as a window that ends before it starts', () => {
			renderTicket( newClientId() );
			change( SelectControl, START_LABELS.mode, 'relative' );
			change( TextControl, START_LABELS.value, '' );

			const startValue = findControl( TextControl, START_LABELS.value );

			expect( startValue.props.help.props.children ).toBe( OUT_OF_RANGE );
			expect( startValue.props[ 'aria-invalid' ] ).toBe( true );
			expect( findControl( SelectControl, END_LABELS.mode ).props.help ).toBeUndefined();
			expect( findControl( SelectControl, END_LABELS.mode ).props[ 'aria-invalid' ] ).toBeUndefined();
		} );

		// The field keeps a typed number within 1 to 60, so only a cleared one is out of range.
		it( 'should name a cleared end number on the end number', () => {
			renderTicket( newClientId() );
			change( SelectControl, END_LABELS.mode, 'relative' );
			change( TextControl, END_LABELS.value, '' );

			expect( findControl( TextControl, END_LABELS.value ).props.help.props.children ).toBe( OUT_OF_RANGE );
			expect( findControl( TextControl, START_LABELS.value ) ).toBeUndefined();
		} );
	} );

	describe( 'helper text', () => {
		it( 'should tell when a relative start works out to, in the event timezone', () => {
			renderSalesWindow( newClientId() );

			change( SelectControl, START_LABELS.mode, 'relative' );

			expect( getHelperTexts() ).toStrictEqual( [ 'Sales start October 6, 2040 at 7:00 pm' ] );
		} );

		it( 'should tell when a relative end works out to', () => {
			renderSalesWindow( newClientId() );

			change( SelectControl, END_LABELS.mode, 'relative' );

			expect( getHelperTexts() ).toStrictEqual( [ 'Sales end October 20, 2040 at 6:00 pm' ] );
		} );

		it( 'should leave the year out of a date in the current year', () => {
			jest.useFakeTimers( { now: new Date( '2040-03-01T12:00:00Z' ) } );
			renderSalesWindow( newClientId() );

			change( SelectControl, START_LABELS.mode, 'relative' );

			expect( getHelperTexts() ).toStrictEqual( [ 'Sales start October 6 at 7:00 pm' ] );
		} );

		it( 'should work the date out again when the event date changes, without saving', () => {
			renderSalesWindow( newClientId() );
			change( SelectControl, START_LABELS.mode, 'relative' );

			act( () => {
				commonStore.dispatch( {
					type: 'SET_DATETIME',
					datetime: { ...DEFAULT_EVENT, start: '2040-11-10 19:00:00', end: '2040-11-10 22:30:00' },
				} );
			} );

			expect( getHelperTexts() ).toStrictEqual( [ 'Sales start October 27, 2040 at 7:00 pm' ] );
		} );

		it( 'should tell a date in the fixed offset the server resolves a manual UTC offset to', () => {
			setBlockEditorData( { timezones: { 'UTC+3': '+03:00' } } );
			commonStore = setEventState( { ...DEFAULT_EVENT, timeZone: 'UTC+3' } );
			renderSalesWindow( newClientId() );

			change( SelectControl, START_LABELS.mode, 'relative' );

			expect( getHelperTexts() ).toStrictEqual( [ 'Sales start October 6, 2040 at 7:00 pm' ] );
		} );

		it( 'should take the current year in the event timezone', () => {
			// Still 2040 in the browser's UTC, already 2041 on Kiritimati, 14 hours ahead.
			jest.useFakeTimers( { now: new Date( '2040-12-31T12:00:00Z' ) } );
			commonStore = setEventState( {
				...DEFAULT_EVENT,
				start: '2041-01-20 19:00:00',
				end: '2041-01-20 22:00:00',
				timeZone: 'Pacific/Kiritimati',
			} );
			renderSalesWindow( newClientId() );

			change( SelectControl, START_LABELS.mode, 'relative' );

			expect( getHelperTexts() ).toStrictEqual( [ 'Sales start January 6 at 7:00 pm' ] );
		} );

		it( 'should keep the helper region mounted, so screen readers announce the date when it appears', () => {
			delete window.tec.events;
			renderSalesWindow( newClientId() );

			change( SelectControl, START_LABELS.mode, 'relative' );

			expect( getHelperRegions() ).toStrictEqual( [ '' ] );
		} );

		it( 'should show no helper text for an end that is not relative', () => {
			renderSalesWindow( newClientId() );

			expect( getHelperTexts() ).toStrictEqual( [] );
		} );

		it( 'should show no helper text while the event dates cannot be read', () => {
			delete window.tec.events;
			renderSalesWindow( newClientId() );

			change( SelectControl, START_LABELS.mode, 'relative' );

			expect( getHelperTexts() ).toStrictEqual( [] );
		} );
	} );
} );
