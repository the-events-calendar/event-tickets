import { act } from 'react-test-renderer';
import { dispatch, select } from '@wordpress/data';
import { SelectControl, TextControl } from '@wordpress/components';
import { resetLocaleData, setLocaleData } from '@wordpress/i18n';
import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import { SALES_WINDOW, SALE_PRICE_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';
import { DEFAULT_EVENT, setTicketFormDates } from './block-editor-event-state';
import {
	EndPicker,
	SALE_PRICE_LABELS,
	StartPicker,
	change,
	findControl,
	getOptionValues,
	getRoot,
	newClientId,
	renderSalePriceWindow,
	salePriceCase,
	salesWindowCase,
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
const UNIT_WEEKS = 604800;

beforeEach( () => {
	setUpBlockEditor();
} );

afterEach( () => {
	tearDownBlockEditor();
} );

describe.each( [
	{
		...salesWindowCase,
		newDraft: {
			start: { mode: 'default', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'default', value: 1, unit: UNIT_HOURS, anchor: 'start' },
		},
		localizedDefaults: {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'end' },
			end: { mode: 'default', value: 30, unit: UNIT_MINUTES, anchor: 'end' },
		},
		draftFromLocalized: {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'end' },
			end: { mode: 'default', value: 30, unit: UNIT_MINUTES, anchor: 'end' },
		},
		savedRule: { start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'end' }, end: { mode: 'default' } },
		relativeStart: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
		modes: { start: [ 'default', 'relative', 'specific' ], end: [ 'default', 'relative', 'specific' ] },
		max: 60,
		// The relative start's number, unit and anchor; the end opens when the event starts.
		relativeControlCount: 3,
		clamps: [
			[ '75', 60 ],
			[ '0', 1 ],
			[ '-3', 1 ],
		],
		units: [
			[ UNIT_MINUTES, 'minute', 'minutes' ],
			[ UNIT_HOURS, 'hour', 'hours' ],
			[ UNIT_DAYS, 'day', 'days' ],
			[ UNIT_WEEKS, 'week', 'weeks' ],
		],
	},
	{
		...salePriceCase,
		newDraft: {
			start: { mode: 'now', value: 2, unit: UNIT_WEEKS },
			end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
		},
		localizedDefaults: {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS },
			end: { mode: 'specific', value: 5, unit: UNIT_DAYS },
		},
		draftFromLocalized: {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS },
			end: { mode: 'specific', value: 5, unit: UNIT_DAYS },
		},
		savedRule: { start: { mode: 'relative', value: 10, unit: UNIT_DAYS }, end: { mode: 'specific' } },
		relativeStart: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
		modes: { start: [ 'now', 'relative', 'specific' ], end: [ 'relative', 'specific' ] },
		max: 30,
		// The number and unit of the relative start and of the relative end a new sale price offers.
		relativeControlCount: 4,
		clamps: [
			[ '45', 30 ],
			[ '0', 1 ],
		],
		units: [
			[ UNIT_DAYS, 'day', 'days' ],
			[ UNIT_WEEKS, 'week', 'weeks' ],
		],
	},
] )( 'the Ticket block options of $title', ( kindCase ) => {
	const { kind, labels, render } = kindCase;
	const getDraft = ( clientId ) => select( STORE_NAME ).getDraftRule( clientId, kind );

	/**
	 * Renders the options of a new ticket with a relative start.
	 *
	 * @return {string} The client ID of the ticket block.
	 */
	function renderRelativeStart() {
		const clientId = newClientId();
		render( clientId );
		change( SelectControl, labels.start.mode, 'relative' );

		return clientId;
	}

	it( 'should keep the defaults as the draft of a new ticket, with the relative values each boundary offers', () => {
		const clientId = newClientId();

		render( clientId );

		expect( getDraft( clientId ) ).toStrictEqual( kindCase.newDraft );
	} );

	it( 'should offer the defaults the server localizes', () => {
		const data = window.tec.tickets.relativeSaleDates.blockEditorData;
		data.windowDefaults = { ...data.windowDefaults, [ kind.id ]: kindCase.localizedDefaults };
		const clientId = newClientId();

		render( clientId );

		expect( getDraft( clientId ) ).toStrictEqual( kindCase.draftFromLocalized );
	} );

	it( 'should show the specific dates of a window saved without a rule, and leave its draft alone', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, null, kind );

		render( clientId );

		expect( findControl( SelectControl, labels.start.mode ).props.value ).toBe( 'specific' );
		expect( findControl( SelectControl, labels.end.mode ).props.value ).toBe( 'specific' );
		expect( kindCase.countPickers() ).toStrictEqual( { start: 1, end: 1 } );
		expect( getDraft( clientId ) ).toBeNull();
	} );

	it( 'should show the relative values of the rule a ticket was saved with', () => {
		const clientId = newClientId();
		const { start, end } = kindCase.savedRule;
		dispatch( STORE_NAME ).setRule( clientId, kindCase.savedRule, kind );

		render( clientId );

		expect( findControl( SelectControl, labels.start.mode ).props.value ).toBe( start.mode );
		expect( findControl( TextControl, labels.start.value ).props.value ).toBe( start.value );
		expect( findControl( SelectControl, labels.start.unit ).props.value ).toBe( String( start.unit ) );
		expect( findControl( SelectControl, labels.end.mode ).props.value ).toBe( end.mode );
	} );

	it( 'should offer the modes each boundary takes', () => {
		render( newClientId() );

		expect( getOptionValues( labels.start.mode ) ).toStrictEqual( kindCase.modes.start );
		expect( getOptionValues( labels.end.mode ) ).toStrictEqual( kindCase.modes.end );
	} );

	it( 'should offer 2 weeks before the event starts when the start becomes relative', () => {
		const clientId = renderRelativeStart();

		expect( findControl( TextControl, labels.start.value ).props.value ).toBe( 2 );
		expect( findControl( SelectControl, labels.start.unit ).props.value ).toBe( String( UNIT_WEEKS ) );
		expect( getDraft( clientId ).start ).toStrictEqual( kindCase.relativeStart );
	} );

	it( 'should mark the ticket as changed when the admin changes an option', () => {
		const clientId = newClientId();
		render( clientId );
		// A new sale price marks the ticket as changed for its defaults already.
		window.__tribe_common_store__.dispatch.mockClear();

		change( SelectControl, labels.start.mode, 'relative' );

		expect( window.__tribe_common_store__.dispatch ).toHaveBeenCalledWith(
			legacyActions.setTicketHasChanges( clientId, true )
		);
	} );

	it( 'should take a relative number from 1 to the largest the window accepts', () => {
		renderRelativeStart();

		const { type, min, max, step } = findControl( TextControl, labels.start.value ).props;

		expect( { type, min, max, step } ).toStrictEqual( { type: 'number', min: 1, max: kindCase.max, step: 1 } );
	} );

	it.each( kindCase.clamps )( 'should keep a typed number of %s within range, as %d', ( typed, kept ) => {
		const clientId = renderRelativeStart();

		change( TextControl, labels.start.value, typed );

		expect( getDraft( clientId ).start.value ).toBe( kept );
	} );

	it( 'should leave a cleared number empty, so the admin can type another', () => {
		const clientId = renderRelativeStart();

		change( TextControl, labels.start.value, '' );

		expect( findControl( TextControl, labels.start.value ).props.value ).toBe( '' );
		expect( getDraft( clientId ).start.value ).toBe( '' );
	} );

	it( 'should offer the units the window takes, named in the form the number takes, and keep the unit as an integer', () => {
		const clientId = renderRelativeStart();
		const unitOptions = () => findControl( SelectControl, labels.start.unit ).props.options;
		change( TextControl, labels.start.value, '1' );

		expect( unitOptions() ).toStrictEqual(
			kindCase.units.map( ( [ unit, one ] ) => ( { value: String( unit ), label: one } ) )
		);

		change( TextControl, labels.start.value, '5' );
		change( SelectControl, labels.start.unit, String( UNIT_DAYS ) );

		expect( unitOptions().map( ( { label } ) => label ) ).toStrictEqual(
			kindCase.units.map( ( [ , , many ] ) => many )
		);
		expect( getDraft( clientId ).start ).toStrictEqual( {
			...kindCase.relativeStart,
			value: 5,
			unit: UNIT_DAYS,
		} );
	} );

	it( 'should name the units with the translation the classic editor uses', () => {
		setLocaleData(
			{
				'': { domain: 'event-tickets', plural_forms: 'nplurals=2; plural=(n != 1);' },
				'Unit of a relative ticket sale date.\u0004week': [ 'semana', 'semanas' ],
			},
			'event-tickets'
		);
		renderRelativeStart();
		change( TextControl, labels.start.value, '1' );

		const unitLabels = findControl( SelectControl, labels.start.unit ).props.options.map( ( { label } ) => label );
		resetLocaleData( undefined, 'event-tickets' );

		expect( unitLabels ).toContain( 'semana' );
	} );

	it( 'should label every relative control for screen readers only, and not the mode', () => {
		renderRelativeStart();

		const modeLabels = [ labels.start.mode, labels.end.mode ];
		const relativeControls = getRoot().findAll(
			( node ) =>
				[ SelectControl, TextControl ].includes( node.type ) && ! modeLabels.includes( node.props.label )
		);

		expect( relativeControls ).toHaveLength( kindCase.relativeControlCount );
		expect( relativeControls.map( ( node ) => node.props.hideLabelFromVision ) ).toStrictEqual(
			relativeControls.map( () => true )
		);
		expect( findControl( SelectControl, labels.start.mode ).props.hideLabelFromVision ).toBeUndefined();
	} );
} );

describe( 'the Ticket block sale price window options', () => {
	const START_LABELS = SALE_PRICE_LABELS.start;
	const END_LABELS = SALE_PRICE_LABELS.end;

	/**
	 * Returns the helper text paragraphs rendered.
	 *
	 * @return {Object[]} The paragraphs' test instances.
	 */
	function findHelpers() {
		return getRoot().findAll(
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

	describe( 'for a new ticket', () => {
		it( 'should start the sale price now and end it 1 week before the event starts', () => {
			renderSalePriceWindow( newClientId() );

			expect( findControl( SelectControl, START_LABELS.mode ).props.value ).toBe( 'now' );
			expect( findControl( SelectControl, END_LABELS.mode ).props.value ).toBe( 'relative' );
			expect( findControl( TextControl, START_LABELS.value ) ).toBeUndefined();
			expect( findControl( TextControl, END_LABELS.value ).props.value ).toBe( 1 );
			expect( findControl( SelectControl, END_LABELS.unit ).props.value ).toBe( String( UNIT_WEEKS ) );
			expect( getRoot().findAllByType( StartPicker ) ).toHaveLength( 0 );
			expect( getRoot().findAllByType( EndPicker ) ).toHaveLength( 0 );
		} );

		it( 'should mark the ticket as changed once its defaults are the draft, so Create or Update checks them', () => {
			const clientId = newClientId();

			renderSalePriceWindow( clientId );

			// The checkbox that shows the options already marked the ticket as changed, before the defaults existed.
			expect( window.__tribe_common_store__.dispatch ).toHaveBeenCalledWith(
				legacyActions.setTicketHasChanges( clientId, true )
			);
		} );
	} );

	it( 'should leave a sale price loaded with a rule, or without one, unmarked', () => {
		const withRule = newClientId();
		const withoutRule = newClientId();
		const rule = { start: { mode: 'now' }, end: { mode: 'specific' } };
		dispatch( STORE_NAME ).setRule( withRule, rule, SALE_PRICE_WINDOW );
		dispatch( STORE_NAME ).setRule( withoutRule, null, SALE_PRICE_WINDOW );

		renderSalePriceWindow( withRule );
		renderSalePriceWindow( withoutRule );

		expect( window.__tribe_common_store__.dispatch ).not.toHaveBeenCalled();
	} );

	it( 'should count both relative boundaries from the event start, with no choice of anchor', () => {
		renderSalePriceWindow( newClientId() );

		change( SelectControl, START_LABELS.mode, 'relative' );

		const anchors = getRoot().findAll(
			( node ) => 'span' === node.type && 'before the event starts' === node.props.children
		);
		const optionLabels = getRoot()
			.findAllByType( SelectControl )
			.flatMap( ( control ) => control.props.options.map( ( { label } ) => label ) );

		expect( anchors.map( ( node ) => node.props.children ) ).toStrictEqual( [
			'before the event starts',
			'before the event starts',
		] );
		expect( optionLabels ).not.toContain( 'before the event starts' );
		expect( optionLabels ).not.toContain( 'before the event ends' );
	} );

	// Common's DayPickerInput puts `inputProps` on its `<input>`; its own spec covers that, so this checks what ET hands it.
	it( 'should show only the picker of a boundary set to a specific date, with its accessible name in inputProps', () => {
		renderSalePriceWindow( newClientId() );

		change( SelectControl, END_LABELS.mode, 'specific' );

		const [ endPicker ] = getRoot().findAllByType( EndPicker );
		expect( getRoot().findAllByType( StartPicker ) ).toHaveLength( 0 );
		expect( endPicker.props.value ).toBe( 'October 1, 2040' );
		expect( endPicker.props.inputProps ).toStrictEqual( { disabled: false, 'aria-label': END_LABELS.date } );
	} );

	// Example: a specific start of June 20 switched to 3 weeks before kept the end from going back past June 20.
	it( 'should drop the bounds the hidden date of the other boundary sets on a picker while it is not specific', () => {
		renderSalePriceWindow( newClientId() );

		change( SelectControl, END_LABELS.mode, 'specific' );

		const { dayPickerProps } = getRoot().findByType( EndPicker ).props;
		expect( Object.keys( dayPickerProps ) ).toStrictEqual( [ 'selectedDays' ] );
	} );

	it( 'should keep the bounds of each picker while both boundaries are specific', () => {
		renderSalePriceWindow( newClientId() );

		change( SelectControl, START_LABELS.mode, 'specific' );
		change( SelectControl, END_LABELS.mode, 'specific' );

		expect( getRoot().findByType( EndPicker ).props.dayPickerProps ).toHaveProperty( 'disabledDays' );
		expect( getRoot().findByType( StartPicker ).props.dayPickerProps ).toHaveProperty( 'toMonth' );
	} );

	describe( 'the sale price length under Sale Ends', () => {
		// The event starts on 2040-10-20 at 19:00 in São Paulo, so 1 week before it is 2040-10-13.

		const relativeRule = ( start, end ) => ( {
			start: { mode: 'relative', ...start },
			end: { mode: 'relative', ...end },
		} );

		afterEach( () => {
			jest.useRealTimers();
		} );

		it( 'should count a whole number of weeks in weeks', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } ),
				SALE_PRICE_WINDOW
			);

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( 'Tickets on sale for 2 weeks' );
		} );

		it( 'should count any other length in days', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule(
				clientId,
				relativeRule( { value: 10, unit: UNIT_DAYS }, { value: 1, unit: UNIT_WEEKS } ),
				SALE_PRICE_WINDOW
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
			dispatch( STORE_NAME ).setRule(
				clientId,
				{ start: { mode: 'specific' }, end: { mode: 'relative', value: 1, unit: UNIT_WEEKS } },
				SALE_PRICE_WINDOW
			);

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( 'Tickets on sale for 1 week' );
		} );

		it( 'should count again when the admin changes the date the form holds', () => {
			const clientId = newClientId();
			setFormSalePriceDates( clientId, '2040-10-06', null );
			dispatch( STORE_NAME ).setRule(
				clientId,
				{ start: { mode: 'specific' }, end: { mode: 'relative', value: 1, unit: UNIT_WEEKS } },
				SALE_PRICE_WINDOW
			);
			renderSalePriceWindow( clientId );

			setFormSalePriceDates( clientId, '2040-10-11', null );

			expect( getLengthText() ).toBe( 'Tickets on sale for 2 days' );
		} );

		it( 'should count again when the admin changes the event date, without saving', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule(
				clientId,
				relativeRule( { value: 10, unit: UNIT_DAYS }, { value: 1, unit: UNIT_WEEKS } ),
				SALE_PRICE_WINDOW
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
			dispatch( STORE_NAME ).setRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } ),
				SALE_PRICE_WINDOW
			);
			renderSalePriceWindow( clientId );

			change( TextControl, START_LABELS.value, '4' );

			expect( getLengthText() ).toBe( 'Tickets on sale for 3 weeks' );
		} );

		it( 'should count again when the admin changes a unit', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule(
				clientId,
				relativeRule( { value: 10, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } ),
				SALE_PRICE_WINDOW
			);
			renderSalePriceWindow( clientId );

			change( SelectControl, START_LABELS.unit, String( UNIT_DAYS ) );

			expect( getLengthText() ).toBe( 'Tickets on sale for 3 days' );
		} );

		it( 'should leave the length out while the Sale Ends number is cleared, not count it from the event start', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } ),
				SALE_PRICE_WINDOW
			);
			renderSalePriceWindow( clientId );

			change( TextControl, END_LABELS.value, '' );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should leave the length out while the Sale Starts number is cleared, not count it from the event start', () => {
			const clientId = newClientId();
			// A start counted from the event start, 2040-10-20, would read as 10 days to this end.
			setFormSalePriceDates( clientId, null, '2040-10-30' );
			dispatch( STORE_NAME ).setRule(
				clientId,
				{ start: { mode: 'relative', value: 3, unit: UNIT_WEEKS }, end: { mode: 'specific' } },
				SALE_PRICE_WINDOW
			);
			renderSalePriceWindow( clientId );

			change( TextControl, START_LABELS.value, '' );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should leave the length out while a specific boundary has no date', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, null, SALE_PRICE_WINDOW );

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( '' );
		} );

		// The legacy code keeps an empty sale price date it loads as the string `Invalid date`.
		it( 'should leave the length out while the legacy store holds no day for a specific boundary', () => {
			const clientId = newClientId();
			setFormSalePriceDates( clientId, 'Invalid date', null );
			dispatch( STORE_NAME ).setRule(
				clientId,
				{ start: { mode: 'specific' }, end: { mode: 'relative', value: 1, unit: UNIT_WEEKS } },
				SALE_PRICE_WINDOW
			);

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should leave the length out while the event dates cannot be read', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule(
				clientId,
				relativeRule( { value: 3, unit: UNIT_WEEKS }, { value: 1, unit: UNIT_WEEKS } ),
				SALE_PRICE_WINDOW
			);
			delete window.tec.events;

			renderSalePriceWindow( clientId );

			expect( getLengthText() ).toBe( '' );
		} );

		it( 'should announce the length politely under Sale Ends only', () => {
			renderSalePriceWindow( newClientId() );

			const [ endBoundary ] = getRoot().findAll(
				( node ) => 'div' === node.type && node.props.className?.includes( '__end--end' )
			);
			const [ helper ] = endBoundary.findAll( ( node ) => 'p' === node.type );

			expect( helper.props[ 'aria-live' ] ).toBe( 'polite' );
			expect( findHelpers() ).toHaveLength( 1 );
		} );
	} );

	describe( 'the sale price window errors under Sale Ends', () => {
		/*
		 * Sales open 4 weeks before the event, on 2040-09-22, and close when it starts on 2040-10-20; 1 week before
		 * the event is 2040-10-13, 2 weeks 2040-10-06 and 5 weeks 2040-09-15.
		 */
		const salesWindowRule = {
			start: { mode: 'relative', value: 4, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'default' },
		};
		const ENDS_BEFORE_START = 'The sale price cannot end before it starts. Please adjust the sale price window.';
		const OUTSIDE_SALES_WINDOW =
			'The sale price window falls outside the ticket sales window. Please adjust the dates.';
		const OUT_OF_RANGE = 'Enter a number from 1 to 30.';

		/**
		 * Renders the sale price window options of a ticket block with its sale price checked, whose sales window opens
		 * 4 weeks before the event, or on its own start date, 2040-09-01, without a sales window rule.
		 *
		 * @param {Object|null} salePriceRule The ticket's sale price rule.
		 * @param {Object|null} windowRule    The ticket's sales window rule.
		 *
		 * @return {string} The client ID of the ticket block.
		 */
		function renderWithSalesWindow( salePriceRule, windowRule = salesWindowRule ) {
			const clientId = newClientId();
			setTicketFormDates( window.__tribe_common_store__, clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' );
			// The options only render once the sale price is checked.
			window.__tribe_common_store__.dispatch( legacyActions.setTempSalePriceChecked( clientId, true ) );
			dispatch( STORE_NAME ).setDraftRule( clientId, windowRule, SALES_WINDOW );
			dispatch( STORE_NAME ).setRule( clientId, salePriceRule, SALE_PRICE_WINDOW );
			renderSalePriceWindow( clientId );

			return clientId;
		}

		/**
		 * Returns the error messages rendered.
		 *
		 * @return {string[]} The messages.
		 */
		function getErrors() {
			return getRoot()
				.findAll( ( node ) => 'span' === node.type && 'alert' === node.props.role )
				.map( ( node ) => node.props.children );
		}

		it( 'should show the end-before-start message under Sale Ends and mark it invalid', () => {
			renderWithSalesWindow( {
				start: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
			} );

			expect( getErrors() ).toStrictEqual( [ ENDS_BEFORE_START ] );
			expect( getLengthText() ).toBe( '' );
			expect( findControl( SelectControl, END_LABELS.mode ).props[ 'aria-invalid' ] ).toBe( true );
			expect( findControl( SelectControl, START_LABELS.mode ).props[ 'aria-invalid' ] ).toBeUndefined();
		} );

		it( 'should show the sales window message when the start falls before the sales window', () => {
			renderWithSalesWindow( {
				start: { mode: 'relative', value: 5, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			expect( getErrors() ).toStrictEqual( [ OUTSIDE_SALES_WINDOW ] );
		} );

		it( 'should never show the sales window message for a Now start', () => {
			renderWithSalesWindow( {
				start: { mode: 'now' },
				end: { mode: 'relative', value: 5, unit: UNIT_WEEKS },
			} );

			expect( getErrors() ).toStrictEqual( [ ENDS_BEFORE_START ] );
		} );

		it( 'should show no message for a valid sale price window', () => {
			renderWithSalesWindow( {
				start: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			expect( getErrors() ).toStrictEqual( [] );
			expect( findControl( SelectControl, END_LABELS.mode ).props[ 'aria-invalid' ] ).toBeUndefined();
		} );

		it( 'should show only the sales window error while the sales window itself is invalid', () => {
			renderWithSalesWindow(
				{
					start: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
					end: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
				},
				{ start: { ...salesWindowRule.start, value: '' }, end: { mode: 'default' } }
			);

			expect( getErrors() ).toStrictEqual( [] );
		} );

		it( 'should show no message for a sale price the save drops for not being lower than the price', () => {
			const clientId = renderWithSalesWindow( {
				start: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
			} );

			act( () => {
				window.__tribe_common_store__.dispatch( legacyActions.setTicketTempPrice( clientId, '20.00' ) );
				window.__tribe_common_store__.dispatch( legacyActions.setTempSalePrice( clientId, '25.00' ) );
			} );

			expect( getErrors() ).toStrictEqual( [] );
		} );

		it( 'should show no message for a sale price saved without a rule', () => {
			renderWithSalesWindow( null );

			expect( getErrors() ).toStrictEqual( [] );
		} );

		it( 'should check again when the admin changes an option', () => {
			renderWithSalesWindow( {
				start: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			change( TextControl, END_LABELS.value, '3' );

			expect( getErrors() ).toStrictEqual( [ ENDS_BEFORE_START ] );
		} );

		it( 'should check again when the sales window changes', () => {
			const clientId = renderWithSalesWindow( {
				start: { mode: 'relative', value: 5, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			expect( getErrors() ).toStrictEqual( [ OUTSIDE_SALES_WINDOW ] );

			act( () => {
				dispatch( STORE_NAME ).setDraftRule(
					clientId,
					{ ...salesWindowRule, start: { ...salesWindowRule.start, value: 6 } },
					SALES_WINDOW
				);
			} );

			expect( getErrors() ).toStrictEqual( [] );
		} );

		// As the sales window names its own, the field that blocks the save says what it takes.
		it( 'should name a cleared number on the number itself, not as a window that ends before it starts', () => {
			renderWithSalesWindow( {
				start: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			change( TextControl, END_LABELS.value, '' );

			const endValue = findControl( TextControl, END_LABELS.value );
			const endMode = findControl( SelectControl, END_LABELS.mode );

			// Passed as the number's help, so the field describes itself with it.
			expect( endValue.props.help.props.children ).toBe( OUT_OF_RANGE );
			expect( endValue.props.help.props.role ).toBe( 'alert' );
			expect( endValue.props[ 'aria-invalid' ] ).toBe( true );
			expect( endMode.props.help ).toBeUndefined();
			expect( endMode.props[ 'aria-invalid' ] ).toBeUndefined();
		} );

		it( 'should name a number past the largest the sale price takes on the number itself', () => {
			renderWithSalesWindow( {
				start: { mode: 'relative', value: 31, unit: UNIT_DAYS },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			expect( findControl( TextControl, START_LABELS.value ).props.help.props.children ).toBe( OUT_OF_RANGE );
			expect( findControl( TextControl, END_LABELS.value ).props.help ).toBeUndefined();
			expect( getErrors() ).toStrictEqual( [] );
		} );

		it( "should judge the sale price against the ticket's own dates without a sales window rule", () => {
			const clientId = renderWithSalesWindow(
				{ start: { mode: 'specific' }, end: { mode: 'relative', value: 1, unit: UNIT_WEEKS } },
				null
			);

			setFormSalePriceDates( clientId, '2040-08-31', null );

			expect( getErrors() ).toStrictEqual( [ OUTSIDE_SALES_WINDOW ] );

			setFormSalePriceDates( clientId, '2040-09-02', null );

			expect( getErrors() ).toStrictEqual( [] );
		} );

		it( 'should check again when the admin picks a specific sale price start', () => {
			const clientId = renderWithSalesWindow( {
				start: { mode: 'specific' },
				end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
			} );

			expect( getErrors() ).toStrictEqual( [] );

			setFormSalePriceDates( clientId, '2040-09-01', null );

			expect( getErrors() ).toStrictEqual( [ OUTSIDE_SALES_WINDOW ] );
		} );
	} );

	it( 'should keep the sale price draft apart from the sales window rule', () => {
		const clientId = newClientId();
		const salesWindowRule = { start: { mode: 'default' }, end: { mode: 'default' } };
		dispatch( STORE_NAME ).setRule( clientId, salesWindowRule, SALES_WINDOW );
		renderSalePriceWindow( clientId );

		change( SelectControl, START_LABELS.mode, 'specific' );

		expect( select( STORE_NAME ).getDraftRule( clientId, SALES_WINDOW ) ).toStrictEqual( salesWindowRule );
	} );
} );
