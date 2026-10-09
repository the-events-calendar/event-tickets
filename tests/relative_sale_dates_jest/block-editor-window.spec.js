import { dispatch, select } from '@wordpress/data';
import { SelectControl, TextControl } from '@wordpress/components';
import { resetLocaleData, setLocaleData } from '@wordpress/i18n';
import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import { SALES_WINDOW, SALE_PRICE_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';
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
			end: { mode: 'relative', value: 30, unit: UNIT_MINUTES, anchor: 'end' },
		},
		// A new ticket's sales open now and close when the event starts, whatever the relative values offered.
		draftFromLocalized: {
			start: { mode: 'default', value: 3, unit: UNIT_DAYS, anchor: 'end' },
			end: { mode: 'default', value: 30, unit: UNIT_MINUTES, anchor: 'end' },
		},
		savedRule: { start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'end' }, end: { mode: 'default' } },
		relativeStart: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
		modes: { start: [ 'default', 'relative', 'specific' ], end: [ 'default', 'relative', 'specific' ] },
		max: 60,
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
		window.tec.tickets.relativeSaleDates.blockEditorData[ kind.defaultsKey ] = kindCase.localizedDefaults;
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
		expect( kindCase.countPickers() ).toBe( 2 );
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

		expect( relativeControls.length ).toBeGreaterThan( 0 );
		expect( relativeControls.map( ( node ) => node.props.hideLabelFromVision ) ).toStrictEqual(
			relativeControls.map( () => true )
		);
		expect( findControl( SelectControl, labels.start.mode ).props.hideLabelFromVision ).toBeUndefined();
	} );
} );

describe( 'the Ticket block sale price window options', () => {
	const START_LABELS = SALE_PRICE_LABELS.start;
	const END_LABELS = SALE_PRICE_LABELS.end;

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

	it( 'should show only the picker of a boundary set to a specific date, labelled for screen readers', () => {
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

	it( 'should keep the sale price draft apart from the sales window rule', () => {
		const clientId = newClientId();
		const salesWindowRule = { start: { mode: 'default' }, end: { mode: 'default' } };
		dispatch( STORE_NAME ).setRule( clientId, salesWindowRule, SALES_WINDOW );
		renderSalePriceWindow( clientId );

		change( SelectControl, START_LABELS.mode, 'specific' );

		expect( select( STORE_NAME ).getDraftRule( clientId, SALES_WINDOW ) ).toStrictEqual( salesWindowRule );
	} );
} );
