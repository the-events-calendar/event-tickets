/**
 * External dependencies
 */
import { runSaga } from 'redux-saga';
import { call, select } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as sagas from '../deferred-sagas';
import { setBodyDetails } from '../sagas';
import reducer, { DEFAULT_STATE } from '../reducer';
import * as actions from '../actions';
import * as selectors from '../selectors';

// The editor stores the tests drive: which blocks exist, in which order, the saved record and the edits.
const mockEditor = {
	order: [],
	record: { id: 10 },
	editPost: jest.fn(),
	createErrorNotice: jest.fn(),
};

jest.mock( '@wordpress/data', () => ( {
	select: () => ( {
		getCurrentPost: () => mockEditor.record,
		getBlock: ( clientId ) => ( mockEditor.order.includes( clientId ) ? {} : null ),
		getBlockIndex: ( clientId ) => mockEditor.order.indexOf( clientId ),
	} ),
	dispatch: () => ( {
		editPost: mockEditor.editPost,
		createErrorNotice: mockEditor.createErrorNotice,
	} ),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	doAction: jest.fn(),
	addAction: jest.fn(),
	addFilter: jest.fn(),
	removeAction: jest.fn(),
	removeFilter: jest.fn(),
	applyFilters: ( name, value ) => value,
} ) );

const { doAction } = require( '@wordpress/hooks' );

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
					{ ticket_show_description: 'yes', ticket_name: 'B', ticket_menu_order: '0' },
					{ ticket_show_description: 'yes', ticket_name: 'A', ticket_menu_order: '1' },
				],
				update: {},
				delete: [],
				move: {},
			},
		} );
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
		expect( gen.next( true ).value ).toEqual( call( setBodyDetails, 'c' ) );
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

	it( 'leaves alone an invalid ticket, an unchanged one and one already staged', () => {
		mockEditor.order = [ 'a', 'b', 'c' ];
		[ 'a', 'b', 'c' ].forEach( sagas.rememberPosition );
		const gen = sagas.stagePendingChanges();

		gen.next();
		gen.next( [ 'a', 'b', 'c' ] );
		const next = gen.next( {
			a: { hasBeenCreated: true, isStaged: false, hasChanges: false, ticketId: 1 },
			b: { hasBeenCreated: true, isStaged: true, hasChanges: true, ticketId: 2 },
			c: { hasBeenCreated: true, isStaged: false, hasChanges: true, ticketId: 3 },
		} );
		expect( next.value ).toEqual( select( selectors.isTicketValid, { clientId: 'c' } ) );
		gen.next( false );
		expect( gen.next( true ).done ).toBe( true );
	} );
} );

describe( 'prepareSave', () => {
	it( 'sends the payload of the blocks that exist and drops a stale edit when nothing is staged', () => {
		mockEditor.order = [ 'a' ];
		sagas.rememberBody( 'a', [ [ 'name', 'A' ] ] );
		sagas.rememberBody( 'r', [ [ 'name', 'Removed' ] ] );

		const edits = prepare( stateWith( { a: { isStaged: true }, r: { isStaged: true } } ), { id: 10, content: 'x' } );

		expect( edits.content ).toBe( 'x' );
		expect( edits.tec_tickets.create ).toEqual( [ { ticket_show_description: 'yes', ticket_name: 'A' } ] );

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
		expect( mockEditor.editPost ).toHaveBeenCalledWith( { tec_tickets: undefined } );
	} );
} );
