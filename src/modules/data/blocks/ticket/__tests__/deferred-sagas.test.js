/**
 * External dependencies
 */
import { runSaga } from 'redux-saga';
import { call, select } from 'redux-saga/effects';
import moment from 'moment';

/**
 * Internal dependencies
 */
import * as sagas from '../deferred-sagas';
import { setBodyDetails, setTicketDetails, setTicketTempDetails, syncTicketSaleEndWithEventStart } from '../sagas';
import reducer, { DEFAULT_STATE } from '../reducer';
import * as actions from '../actions';
import * as selectors from '../selectors';
import * as types from '../types';

// The editor stores the tests drive: which blocks exist, in which order, the saved record and the edits.
const mockEditor = {
	order: [],
	record: { id: 10 },
	editPost: jest.fn(),
	createErrorNotice: jest.fn(),
	insertBlock: jest.fn(),
};

jest.mock( '@wordpress/data', () => ( {
	select: () => ( {
		getCurrentPost: () => mockEditor.record,
		getBlock: ( clientId ) => ( mockEditor.order.includes( clientId ) ? {} : null ),
		getBlockIndex: ( clientId ) => mockEditor.order.indexOf( clientId ),
		getBlocksByName: ( name ) => ( 'tribe/tickets' === name ? [ 'tickets-parent' ] : [] ),
		getEditedPostAttribute: ( key ) => ( 'type' === key ? 'tribe_events' : undefined ),
	} ),
	dispatch: () => ( {
		editPost: mockEditor.editPost,
		createErrorNotice: mockEditor.createErrorNotice,
		insertBlock: mockEditor.insertBlock,
	} ),
} ) );

jest.mock( '@wordpress/blocks', () => ( {
	createBlock: ( name, attributes ) => ( { name, attributes } ),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	doAction: jest.fn(),
	addAction: jest.fn(),
	addFilter: jest.fn(),
	removeAction: jest.fn(),
	removeFilter: jest.fn(),
	applyFilters: ( name, value ) => value,
} ) );

const { doAction, addFilter } = require( '@wordpress/hooks' );
const { isEqual } = require( 'lodash' );

/**
 * Builds the tickets block state from ticket fields keyed by client ID.
 *
 * @param {Object} tickets   Client ID => fields.
 * @param {Object} blockLevel Staged deletes and moves.
 *
 * @return {Object} The store state the selectors read.
 */
const stateWith = ( tickets, blockLevel = {} ) => {
	let block = DEFAULT_STATE;

	Object.entries( tickets ).forEach( ( [ clientId, fields ] ) => {
		block = reducer( block, actions.registerTicketBlock( clientId ) );
		block = reducer( block, actions.setTicketIsStaged( clientId, !! fields.isStaged ) );
		block = reducer( block, actions.setTicketHasBeenCreated( clientId, !! fields.hasBeenCreated ) );
		block = reducer( block, actions.setTicketHasChanges( clientId, !! fields.hasChanges ) );
		block = reducer( block, actions.setTicketId( clientId, fields.ticketId || 0 ) );
		block = reducer( block, actions.setTicketHasDurationError( clientId, !! fields.hasDurationError ) );
	} );

	( blockLevel.deletes || [] ).forEach( ( id ) => ( block = reducer( block, actions.stageTicketDelete( id ) ) ) );
	Object.entries( blockLevel.moves || {} ).forEach(
		( [ id, destination ] ) => ( block = reducer( block, actions.stageTicketMove( Number( id ), destination ) ) )
	);

	return { tickets: { blocks: { ticket: block } } };
};

/**
 * Runs a saga against a fixed state and records what it dispatched.
 *
 * @param {Object}   state The store state.
 * @param {Function} saga  The saga.
 * @param {...*}     args  Its arguments.
 *
 * @return {Array<Object>} The dispatched actions.
 */
const run = ( state, saga, ...args ) => {
	const dispatched = [];
	runSaga( { dispatch: ( action ) => dispatched.push( action ), getState: () => state }, saga, ...args );

	return dispatched;
};

/**
 * Prepares a save like the editor does and returns the edits it would send.
 *
 * @param {Object} state The store state.
 * @param {Object} edits The edits before the filter.
 *
 * @return {Object} The edits after the filter.
 */
const prepare = ( state, edits = { id: 10 } ) => {
	let prepared;
	run( state, sagas.prepareSave, edits, ( value ) => ( prepared = value ) );

	return prepared;
};

const ofType = ( dispatched, type ) => dispatched.filter( ( action ) => action.type === type );

beforeEach( () => {
	mockEditor.order = [];
	mockEditor.record = { id: 10 };
	mockEditor.editPost.mockClear();
	mockEditor.createErrorNotice.mockClear();
	mockEditor.insertBlock.mockClear();
	doAction.mockReset();
	[ 'a', 'b', 'c', 'u', 'r' ].forEach( sagas.forgetBody );
} );

describe( 'refreshPayload', () => {
	it( 'builds the payload from the blocks that exist, in block order, with their position as the menu order', () => {
		mockEditor.order = [ 'b', 'a' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ], [ 'menu_order', '0' ] ] );
		sagas.rememberBody( 'b', [ [ 'name', 'B' ], [ 'menu_order', '7' ] ] );
		// Removed through the editor's own toolbar: still in the store, gone from the editor.
		sagas.rememberBody( 'r', [ [ 'name', 'Removed' ] ] );

		run( stateWith( { a: { isStaged: true }, b: { isStaged: true }, r: { isStaged: true } } ), sagas.refreshPayload );

		expect( mockEditor.editPost ).toHaveBeenLastCalledWith( {
			tec_tickets: {
				create: [
					{ ticket_show_description: 'yes', ticket_name: 'B', ticket_menu_order: '0', tec_tickets_create_key: 'b' },
					{ ticket_show_description: 'yes', ticket_name: 'A', ticket_menu_order: '1', tec_tickets_create_key: 'a' },
				],
				update: {},
				delete: [],
				move: {},
			},
		}, { undoIgnore: true } );
	} );
} );

describe( 'stageTicket', () => {
	it( 'keeps the temp details as the shown details, marks the ticket staged and refreshes the payload', () => {
		const gen = sagas.stageTicket( 'a', [ [ 'name', 'A' ] ] );

		expect( gen.next().value ).toEqual( select( selectors.getTicketTempDetails, { clientId: 'a' } ) );
		const temp = { title: 'A' };
		gen.next( temp ); // setTicketDetails
		gen.next(); // setTicketIsStaged
		gen.next(); // setTicketSaveError
		gen.next(); // setTicketHasChanges
		expect( gen.next().value ).toEqual( call( sagas.refreshPayload ) );
		expect( gen.next().done ).toBe( true );
	} );
} );

describe( 'stagePendingChanges', () => {
	it( 'stages a saved ticket with unconfirmed changes, such as an end date moved with the event, when it is valid', () => {
		mockEditor.order = [ 'c' ];
		const gen = sagas.stagePendingChanges();

		gen.next(); // all client IDs
		gen.next( [ 'c' ] ); // by client ID
		expect( gen.next( { c: { hasBeenCreated: true, isStaged: false, hasChanges: true, ticketId: 5 } } ).value ).toEqual(
			select( selectors.isTicketValid, { clientId: 'c' } )
		);
		gen.next( true ); // sale price rule
		gen.next( true ); // duration
		expect( gen.next( false ).value ).toEqual( call( setBodyDetails, 'c' ) );
		const body = { entries: () => [ [ 'name', 'C' ] ][ Symbol.iterator ]() };
		expect( gen.next( body ).value ).toEqual( call( sagas.stageTicket, 'c', [ [ 'name', 'C' ] ] ) );
		expect( gen.next().done ).toBe( true );
	} );

	it( 'stages a saved ticket moved in the block order', () => {
		mockEditor.order = [ 'c' ];
		sagas.rememberPosition( 'c' );
		mockEditor.order = [ 'x', 'c' ];
		const gen = sagas.stagePendingChanges();

		gen.next();
		gen.next( [ 'c' ] );
		expect( gen.next( { c: { hasBeenCreated: true, isStaged: false, hasChanges: false, ticketId: 5 } } ).value ).toEqual(
			select( selectors.isTicketValid, { clientId: 'c' } )
		);
	} );

	it( 'leaves alone an unchanged ticket and a staged one, and flags one with invalid changes', () => {
		mockEditor.order = [ 'a', 'b', 'c' ];
		[ 'a', 'b', 'c' ].forEach( sagas.rememberPosition );
		const gen = sagas.stagePendingChanges();

		gen.next();
		gen.next( [ 'a', 'b', 'c' ] );
		const next = gen.next( {
			a: { hasBeenCreated: true, isStaged: false, hasChanges: false, ticketId: 1 },
			b: { hasBeenCreated: true, isStaged: true, hasChanges: false, ticketId: 2 },
			c: { hasBeenCreated: true, isStaged: false, hasChanges: true, ticketId: 3 },
		} );
		expect( next.value ).toEqual( select( selectors.isTicketValid, { clientId: 'c' } ) );
		gen.next( false ); // invalid
		gen.next( true ); // sale price
		expect( gen.next( false ).value.PUT.action.payload.clientId ).toBe( 'c' );
		expect( gen.next().done ).toBe( true );
	} );
} );

describe( 'prepareSave', () => {
	it( 'sends the payload of the blocks that exist and drops a stale edit when nothing is staged', () => {
		mockEditor.order = [ 'a' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );
		sagas.rememberBody( 'r', [ [ 'name', 'Removed' ] ] );

		const edits = prepare( stateWith( { a: { isStaged: true }, r: { isStaged: true } } ), { id: 10, content: 'x' } );

		expect( edits.content ).toBe( 'x' );
		expect( edits.tec_tickets.create ).toEqual( [
			{ ticket_show_description: 'yes', ticket_name: 'A', tec_tickets_create_key: 'a' },
		] );

		const nothing = prepare( stateWith( {} ), { id: 10, tec_tickets: { create: [ {} ], update: {}, delete: [], move: {} } } );
		expect( nothing ).toEqual( { id: 10 } );
	} );

	it( 'always hands the edits back, even when building the payload throws', () => {
		let prepared = null;
		run( {}, sagas.prepareSave, { id: 10 }, ( value ) => ( prepared = value ) );

		expect( prepared ).toEqual( { id: 10 } );
	} );
} );

describe( 'applying the answer of a save', () => {
	const save = ( state, response ) => {
		prepare( state );
		mockEditor.record = { id: 10, tec_tickets: response };

		return run( state, sagas.applyLastSaveResponse );
	};

	it( 'settles what was sent and announces it with the ticket details, as the REST save did', () => {
		mockEditor.order = [ 'a', 'u' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );
		sagas.rememberBody( 'u', [ [ 'name', 'U' ] ] );
		const state = stateWith(
			{
				a: { isStaged: true },
				u: { isStaged: true, hasBeenCreated: true, ticketId: 30 },
			},
			{ deletes: [ 40 ] }
		);

		const dispatched = save( state, { created: { 0: 101 }, errors: [] } );

		expect( dispatched ).toContainEqual( actions.setTicketId( 'a', 101 ) );
		expect( dispatched ).toContainEqual( actions.setTicketIsStaged( 'a', false ) );
		expect( dispatched ).toContainEqual( actions.setTicketIsStaged( 'u', false ) );
		expect( dispatched ).toContainEqual( actions.clearStagedTickets( { deletes: [ 40 ], moves: [] } ) );
		// The store's details, with the moments listeners such as the Tickets Plus waitlist read.
		const details = expect.objectContaining( { startDateMoment: expect.anything(), endDateMoment: expect.anything() } );
		expect( doAction ).toHaveBeenCalledWith( 'tec.tickets.blocks.ticketCreated', 'a', 101, details );
		expect( doAction ).toHaveBeenCalledWith( 'tec.tickets.blocks.ticketUpdated', 'u', 30, details );
		expect( doAction ).toHaveBeenCalledWith( 'tec.tickets.blocks.ticketDeleted', null, 40 );
	} );

	it( 'keeps every sent change staged and says why when the whole payload was refused', () => {
		mockEditor.order = [ 'u' ];
		sagas.rememberBody( 'u', [ [ 'name', 'U' ] ] );
		const state = stateWith( { u: { isStaged: true, hasBeenCreated: true, ticketId: 30 } }, { deletes: [ 40 ] } );

		const dispatched = save( state, { created: {}, errors: [ { part: null, key: null, message: 'Too many' } ] } );

		expect( dispatched ).toContainEqual( actions.setTicketIsStaged( 'u', true ) );
		expect( dispatched ).toContainEqual( actions.setTicketSaveError( 'u', 'Too many' ) );
		expect( dispatched ).toContainEqual( actions.clearStagedTickets( { deletes: [], moves: [] } ) );
		expect( doAction ).not.toHaveBeenCalledWith( 'tec.tickets.blocks.ticketUpdated', expect.anything(), expect.anything(), expect.anything() );
		expect( doAction ).not.toHaveBeenCalledWith( 'tec.tickets.blocks.ticketDeleted', null, 40 );
		expect( mockEditor.createErrorNotice ).toHaveBeenCalledWith( 'Too many', expect.anything() );
	} );

	it( 'shows a refused delete as a notice, its block being gone', () => {
		const state = stateWith( {}, { deletes: [ 40 ] } );

		save( state, { created: {}, errors: [ { part: 'delete', key: 40, message: 'Not allowed' } ] } );

		expect( mockEditor.createErrorNotice ).toHaveBeenCalledTimes( 1 );
		expect( doAction ).not.toHaveBeenCalledWith( 'tec.tickets.blocks.ticketDeleted', null, 40 );
	} );

	it( 'finishes settling when a listener throws', () => {
		mockEditor.order = [ 'a' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );
		doAction.mockImplementation( () => {
			throw new TypeError( "Cannot read properties of undefined (reading 'isBefore')" );
		} );
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );

		const dispatched = save( stateWith( { a: { isStaged: true } } ), { created: { 0: 101 }, errors: [] } );

		expect( dispatched ).toContainEqual( actions.clearStagedTickets( { deletes: [], moves: [] } ) );
		expect( mockEditor.editPost ).toHaveBeenCalled();
		console.error.mockRestore(); // eslint-disable-line no-console
	} );

	it( 'never applies the same answer twice', () => {
		mockEditor.order = [ 'a' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );
		const state = stateWith( { a: { isStaged: true } } );
		const response = { created: { 0: 101 }, errors: [] };

		save( state, response );
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );
		const again = save( state, response );

		// The record still carries the old answer: the server did not answer this save.
		expect( again ).not.toContainEqual( actions.setTicketId( 'a', 101 ) );
		expect( again ).toContainEqual( actions.setTicketSaveError( 'a', 'The ticket changes were not saved with the post.' ) );
	} );

	it( 'only refreshes the edit when the save carried nothing', () => {
		prepare( stateWith( {} ) );
		mockEditor.record = { id: 10 };

		const dispatched = run( stateWith( {} ), sagas.applyLastSaveResponse );

		expect( ofType( dispatched, actions.setTicketSaveError( 'a', '' ).type ) ).toEqual( [] );
		expect( mockEditor.editPost ).toHaveBeenCalledWith( { tec_tickets: undefined }, { undoIgnore: true } );
	} );
} );

describe( 'the review of the stacked PRs, second round', () => {
	const save = ( state, response, moveDuringSave = null ) => {
		prepare( state );
		if ( moveDuringSave ) {
			moveDuringSave();
		}
		mockEditor.record = { id: 10, tec_tickets: response };

		return run( state, sagas.applyLastSaveResponse );
	};

	it( 'stages again a staged ticket whose fields changed since, such as a sale end moved with the event start', () => {
		mockEditor.order = [ 'c' ];
		const gen = sagas.stagePendingChanges();

		gen.next();
		gen.next( [ 'c' ] );
		// Staged, not created yet, and changed since it was staged.
		expect( gen.next( { c: { hasBeenCreated: false, isStaged: true, hasChanges: true, ticketId: 0 } } ).value ).toEqual(
			select( selectors.isTicketValid, { clientId: 'c' } )
		);
		gen.next( true ); // sale price rule
		gen.next( true ); // duration
		expect( gen.next( false ).value ).toEqual( call( setBodyDetails, 'c' ) );
	} );

	it( 'never stages a ticket whose dates are invalid, and says it was not saved', () => {
		mockEditor.order = [ 'c' ];
		const gen = sagas.stagePendingChanges();

		gen.next();
		gen.next( [ 'c' ] );
		gen.next( { c: { hasBeenCreated: true, isStaged: false, hasChanges: true, ticketId: 5 } } );
		gen.next( true ); // valid
		gen.next( true ); // sale price
		const next = gen.next( true ); // has a duration error

		expect( next.value.PUT.action.type ).toBe( actions.setTicketSaveError( 'c', '' ).type );
		expect( next.value.PUT.action.payload.clientId ).toBe( 'c' );
		expect( next.value.PUT.action.payload.saveError ).not.toBe( '' );
		expect( gen.next().done ).toBe( true );
	} );

	it( 'sends no payload, keeps everything staged and says so when preparing the save fails', () => {
		// The staged create of a block removed since; preparing throws on a store it cannot read.
		const stale = { create: [ { ticket_name: 'Removed block' } ], update: {}, delete: [], move: {} };
		let prepared = null;
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		run( {}, sagas.prepareSave, { id: 10, tec_tickets: stale }, ( value ) => ( prepared = value ) );
		console.error.mockRestore(); // eslint-disable-line no-console

		expect( prepared ).toEqual( { id: 10 } );
		expect( mockEditor.createErrorNotice ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not let a listener of ticketStaged stop the staging', () => {
		doAction.mockImplementation( () => {
			throw new Error( 'A staged listener failed.' );
		} );
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		const gen = sagas.stageTicket( 'a', [ [ 'name', 'A' ] ] );
		let step = gen.next();
		while ( ! step.done ) {
			step = gen.next();
		}
		console.error.mockRestore(); // eslint-disable-line no-console

		expect( step.done ).toBe( true );
	} );

	it( 'records as saved the position a ticket was sent with, not one it was moved to while the save was out', () => {
		mockEditor.order = [ 'a', 'b' ];
		sagas.rememberPosition( 'a' );
		sagas.rememberPosition( 'b' );
		sagas.rememberBody( 'a', [ [ 'name', 'A' ], [ 'menu_order', '0' ] ] );
		const state = stateWith( {
			a: { isStaged: true, hasBeenCreated: true, ticketId: 12 },
			b: { hasBeenCreated: true, ticketId: 13 },
		} );

		save( state, { created: {}, errors: [] }, () => ( mockEditor.order = [ 'b', 'a' ] ) );

		// The reorder made during the save is still unsaved: the next save carries it.
		const gen = sagas.stagePendingChanges();
		gen.next();
		gen.next( [ 'a', 'b' ] );
		expect( gen.next( { a: { hasBeenCreated: true, isStaged: false, hasChanges: false, ticketId: 12 }, b: { hasBeenCreated: true, isStaged: false, hasChanges: false, ticketId: 13 } } ).value ).toEqual(
			select( selectors.isTicketValid, { clientId: 'b' } )
		);
	} );

	it( 'does not reload a created ticket over changes made to it while the save was out', () => {
		mockEditor.order = [ 'a' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );
		const state = stateWith( { a: { isStaged: true } } );
		prepare( state );
		sagas.rememberBody( 'a', [ [ 'name', 'A, edited again' ] ] );
		mockEditor.record = { id: 10, tec_tickets: { created: { 0: 101 }, errors: [] } };

		const dispatched = run( state, sagas.applyLastSaveResponse );

		expect( dispatched ).toContainEqual( actions.setTicketId( 'a', 101 ) );
		expect( dispatched.filter( ( action ) => action.type === actions.fetchTicket( 'a', 101 ).type ) ).toEqual( [] );
	} );

	it( 'brings back the block of a ticket whose removal or move was refused', () => {
		const state = stateWith( {}, { deletes: [ 40 ], moves: { 50: 9 } } );

		save( state, {
			created: {},
			errors: [
				{ part: 'delete', key: 40, message: 'Not allowed' },
				{ part: 'move', key: 50, message: 'Could not move' },
			],
		} );

		expect( mockEditor.insertBlock ).toHaveBeenCalledWith(
			{ name: 'tribe/tickets-item', attributes: { hasBeenCreated: true, ticketId: 40 } },
			undefined,
			'tickets-parent',
			false
		);
		expect( mockEditor.insertBlock ).toHaveBeenCalledWith(
			{ name: 'tribe/tickets-item', attributes: { hasBeenCreated: true, ticketId: 50 } },
			undefined,
			'tickets-parent',
			false
		);
	} );
} );

describe( 'the review of the stacked PRs, third round', () => {
	// The store as the editor runs it: every action goes through the reducer, the bulk details actions through their sagas.
	let block;
	const dispatch = ( action ) => {
		block = reducer( block, action );
		if ( types.SET_TICKET_DETAILS === action.type ) {
			live( setTicketDetails, action );
		}
		if ( types.SET_TICKET_TEMP_DETAILS === action.type ) {
			live( setTicketTempDetails, action );
		}
	};
	const live = ( saga, ...args ) =>
		runSaga( { dispatch, getState: () => ( { tickets: { blocks: { ticket: block } } } ) }, saga, ...args );
	const prepareLive = () => {
		let edits;
		live( sagas.prepareSave, { id: 10 }, ( value ) => ( edits = value ) );

		return edits;
	};
	const answer = ( response ) => {
		mockEditor.record = { id: 10, tec_tickets: response };
		live( sagas.applyLastSaveResponse );
	};
	const ticket = ( clientId, ticketId = 0 ) => {
		dispatch( actions.registerTicketBlock( clientId ) );
		dispatch( actions.setTicketHasBeenCreated( clientId, !! ticketId ) );
		dispatch( actions.setTicketId( clientId, ticketId ) );
		dispatch( actions.setTicketTempTitle( clientId, 'Original' ) );
		dispatch( actions.setTicketTempPrice( clientId, '10' ) );
		dispatch( actions.setTicketTempCapacityType( clientId, 'unlimited' ) );
		mockEditor.order.push( clientId );
		sagas.rememberPosition( clientId );
	};
	const ticketState = ( clientId ) => block.tickets.byClientId[ clientId ];
	const createdHook = () => doAction.mock.calls.find( ( [ name ] ) => 'tec.tickets.blocks.ticketCreated' === name );

	beforeEach( () => {
		block = DEFAULT_STATE;
	} );

	it( 'leaves out of the save a staged ticket whose latest edit fails the rules, and keeps it staged with its error', () => {
		ticket( 'a' );
		ticket( 'b', 12 );
		ticket( 'c', 13 );
		[ 'a', 'b', 'c' ].forEach( ( clientId ) => live( sagas.stageTicket, clientId, [ [ 'name', 'Original' ] ] ) );
		// The staged new ticket and one staged saved ticket are edited into dates that end before they start.
		[ 'a', 'b' ].forEach( ( clientId ) => {
			dispatch( actions.setTicketHasChanges( clientId, true ) );
			dispatch( actions.setTicketHasDurationError( clientId, true ) );
		} );

		const edits = prepareLive();

		expect( edits.tec_tickets.create ).toEqual( [] );
		expect( Object.keys( edits.tec_tickets.update ) ).toEqual( [ '13' ] );

		answer( { created: {}, errors: [] } );

		// The answer settles only what went out: the left-out tickets keep their staging and the reason they were left out.
		[ 'a', 'b' ].forEach( ( clientId ) => {
			expect( ticketState( clientId ).isStaged ).toBe( true );
			expect( ticketState( clientId ).saveError ).not.toBe( '' );
		} );
		expect( ticketState( 'c' ).isStaged ).toBe( false );
	} );

	it( 'announces a created ticket with the details it was sent with, not an edit confirmed while the save was out', () => {
		ticket( 'a' );
		live( sagas.stageTicket, 'a', [ [ 'name', 'Original' ] ] );
		prepareLive();
		dispatch( actions.setTicketTempTitle( 'a', 'Later edit' ) );
		live( sagas.stageTicket, 'a', [ [ 'name', 'Later edit' ] ] );

		answer( { created: { 0: 101 }, errors: [] } );

		expect( createdHook()[ 3 ].title ).toBe( 'Original' );
		// The later edit stays in the store, staged for the next save.
		expect( ticketState( 'a' ).details.title ).toBe( 'Later edit' );
	} );

	it( 'announces a created ticket with the sale end it was sent with, not one moved with the event start meanwhile', () => {
		const previousStart = '2027-10-06 10:00:00';
		ticket( 'a' );
		dispatch( actions.setTicketTempEndDateMoment( 'a', moment( previousStart ) ) );
		dispatch( actions.setTicketTempEndDate( 'a', '2027-10-06' ) );
		live( sagas.stageTicket, 'a', [ [ 'name', 'Original' ], [ 'end_date', '2027-10-06' ] ] );
		prepareLive();
		// The event start moves while the save is out; the sale end follows it, with no confirm.
		window.tec = { events: { app: { main: { data: { blocks: { datetime: { selectors: { getStart: () => '2027-10-09 10:00:00' } } } } } } } };
		live( syncTicketSaleEndWithEventStart, previousStart, 'a' );
		delete window.tec;
		expect( ticketState( 'a' ).details.endDate ).toBe( '2027-10-09' );

		answer( { created: { 0: 101 }, errors: [] } );

		expect( createdHook()[ 3 ].endDate ).toBe( '2027-10-06' );
	} );

	it( 'sends a ticket created but not finished as an update of that ticket, with every field it was staged with', () => {
		ticket( 'a' );
		live( sagas.stageTicket, 'a', [ [ 'name', 'Original' ], [ 'ticket[capacity]', '5' ] ] );
		prepareLive();

		// The server's answer when the provider failed once the ticket was on the post: its ID, and not saved.
		answer( {
			created: { 0: 101 },
			errors: [ { part: 'create', key: 0, message: 'The ticket was created, but not all of its settings were saved.' } ],
		} );

		expect( ticketState( 'a' ).isStaged ).toBe( true );
		const edits = prepareLive();
		expect( edits.tec_tickets.create ).toEqual( [] );
		expect( edits.tec_tickets.update[ 101 ][ 'tribe-ticket' ].capacity ).toBe( '5' );
	} );
} );

describe( 'the cross-review of the stack', () => {
	const save = ( state, response ) => {
		prepare( state );
		mockEditor.record = { id: 10, tec_tickets: response };

		return run( state, sagas.applyLastSaveResponse );
	};

	it( 'tells ticketDeleted which block the deleted ticket was in', () => {
		const state = stateWith( {}, { deletes: [ 40 ] } );
		run( state, sagas.stageDelete, 'd', 40 );

		save( state, { id: 's1', created: {}, errors: [] } );

		expect( doAction ).toHaveBeenCalledWith( 'tec.tickets.blocks.ticketDeleted', 'd', 40 );
	} );

	it( 'applies the second of two equal error-free answers, which core keeps as the same object only when they are equal', () => {
		mockEditor.order = [ 'u' ];
		const state = stateWith( { u: { isStaged: true, hasBeenCreated: true, ticketId: 30 } } );
		// core-data keeps the previous object when the new value is deep-equal; the server's per-save id tells them apart.
		const kept = ( previous, next ) => ( isEqual( previous, next ) ? previous : next );

		sagas.rememberBody( 'u', [ [ 'name', 'U' ] ] );
		const first = { id: 's1', created: {}, errors: [] };
		save( state, first );
		sagas.rememberBody( 'u', [ [ 'name', 'U' ] ] );
		const second = save( state, kept( first, { id: 's2', created: {}, errors: [] } ) );

		expect( second ).toContainEqual( actions.setTicketIsStaged( 'u', false ) );
		expect( second ).not.toContainEqual(
			actions.setTicketSaveError( 'u', 'The ticket changes were not saved with the post.' )
		);
	} );

	it( 'keeps the payload edits out of the undo stack', () => {
		mockEditor.order = [ 'a' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );

		run( stateWith( { a: { isStaged: true } } ), sagas.refreshPayload );
		run( stateWith( {} ), sagas.refreshPayload );
		sagas.settlePayloadEdit( { id: 's1', created: {}, errors: [] } );

		expect( mockEditor.editPost.mock.calls.length ).toBe( 3 );
		mockEditor.editPost.mock.calls.forEach( ( [ , options ] ) => expect( options ).toEqual( { undoIgnore: true } ) );
	} );

	it( 'sends a preview of a draft without the staged ticket changes, whose answer a preview never applies', () => {
		addFilter.mockClear();
		sagas.createPreSaveChannel();
		const [ , , filter ] = addFilter.mock.calls.find( ( [ hook ] ) => 'editor.preSavePost' === hook );
		const edits = { title: 'Draft', tec_tickets: { create: [ [ [ 'name', 'A' ] ] ], update: {}, delete: [], move: {} } };

		expect( filter( edits, { isPreview: true } ) ).toEqual( { title: 'Draft' } );
		expect( filter( edits, { isAutosave: true } ) ).toEqual( { title: 'Draft' } );
	} );
} );

