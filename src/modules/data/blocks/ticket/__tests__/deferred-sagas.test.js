/**
 * External dependencies
 */
import { call, put, select } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as sagas from '../deferred-sagas';
import * as actions from '../actions';
import * as selectors from '../selectors';

jest.mock( '@wordpress/data', () => ( {
	select: () => ( {
		getCurrentPost: () => ( { id: 10, tec_tickets: { created: { 0: 501, 1: 502 }, errors: [] } } ),
		isAutosavingPost: () => false,
	} ),
	dispatch: () => ( { editPost: () => {} } ),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	doAction: jest.fn(),
	addAction: jest.fn(),
	applyFilters: ( name, value ) => value,
} ) );

describe( 'rememberBody / refreshPayload', () => {
	it( 'rebuilds the payload from the store and hands it to the editor as an edit', () => {
		sagas.rememberBody( 'a', [ [ 'name', 'A' ], [ 'provider', 'rsvp' ] ] );
		const gen = sagas.refreshPayload();

		expect( gen.next().value ).toEqual( select( selectors.getTicketsAllClientIds ) );
		expect( gen.next( [ 'a' ] ).value ).toEqual( select( selectors.getTicketsByClientId ) );
		expect( gen.next( { a: { isStaged: true, hasBeenCreated: false, ticketId: 0 } } ).value ).toEqual( select( selectors.getStagedDeletes ) );
		expect( gen.next( [ 7 ] ).value ).toEqual( select( selectors.getStagedMoves ) );
		expect( gen.next( { 8: 99 } ).value ).toEqual( put( actions.setStagedCreateOrder( [ 'a' ] ) ) );
		expect( gen.next().value ).toEqual(
			call( sagas.editPostPayload, {
				create: [ { ticket_name: 'A', ticket_provider: 'rsvp' } ],
				update: {},
				delete: [ 7 ],
				move: { 8: 99 },
			} )
		);
		expect( gen.next().done ).toBe( true );
		sagas.forgetBody( 'a' );
	} );
} );

describe( 'stageTicket', () => {
	it( 'keeps the temp details as the shown details, marks the ticket staged and refreshes the payload', () => {
		const gen = sagas.stageTicket( 'a', [ [ 'name', 'A' ] ] );

		expect( gen.next().value ).toEqual( select( selectors.getTicketTempDetails, { clientId: 'a' } ) );
		const temp = { title: 'A' };
		expect( gen.next( temp ).value ).toEqual( put( actions.setTicketDetails( 'a', temp ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketIsStaged( 'a', true ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketSaveError( 'a', '' ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketHasChanges( 'a', false ) ) );
		expect( gen.next().value ).toEqual( call( sagas.refreshPayload ) );
		expect( gen.next().done ).toBe( true );
		sagas.forgetBody( 'a' );
	} );
} );

describe( 'stageDelete and stageMove', () => {
	it( 'stages a delete for a saved ticket and refreshes', () => {
		const gen = sagas.stageDelete( 'a', 12 );
		expect( gen.next().value ).toEqual( put( actions.stageTicketDelete( 12 ) ) );
		expect( gen.next().value ).toEqual( call( sagas.refreshPayload ) );
		expect( gen.next().done ).toBe( true );
	} );

	it( 'stages a move and refreshes', () => {
		const gen = sagas.stageMove( 12, 99 );
		expect( gen.next().value ).toEqual( put( actions.stageTicketMove( 12, 99 ) ) );
		expect( gen.next().value ).toEqual( call( sagas.refreshPayload ) );
		expect( gen.next().done ).toBe( true );
	} );
} );

describe( 'applySaveResponse', () => {
	const response = {
		created: { 0: 501, 1: 502 },
		errors: [
			{ part: 'create', key: 2, message: 'Bad provider' },
			{ part: 'update', key: 33, message: 'Not yours' },
		],
	};

	it( 'gives created tickets their IDs by position, clears saved updates, and leaves rejected ones with their error', () => {
		const gen = sagas.applySaveResponse( response );

		expect( gen.next().value ).toEqual( select( selectors.getStagedCreateOrder ) );
		expect( gen.next( [ 'a', 'b', 'c' ] ).value ).toEqual( select( selectors.getTicketsAllClientIds ) );
		expect( gen.next( [ 'a', 'b', 'c', 'd', 'e' ] ).value ).toEqual( select( selectors.getTicketsByClientId ) );
		const byClientId = {
			a: { isStaged: true, hasBeenCreated: false, ticketId: 0 },
			b: { isStaged: true, hasBeenCreated: false, ticketId: 0 },
			c: { isStaged: true, hasBeenCreated: false, ticketId: 0 },
			d: { isStaged: true, hasBeenCreated: true, ticketId: 33 },
			e: { isStaged: true, hasBeenCreated: true, ticketId: 44 },
		};
		expect( gen.next( byClientId ).value ).toEqual( select( selectors.getStagedDeletes ) );

		// Created: a → 501, b → 502.
		expect( gen.next( [ 70 ] ).value ).toEqual( put( actions.setTicketId( 'a', 501 ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketHasBeenCreated( 'a', true ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketIsStaged( 'a', false ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketSaveError( 'a', '' ) ) );
		expect( gen.next().value ).toEqual( call( sagas.hydrateTicket, 'a', 501 ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketId( 'b', 502 ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketHasBeenCreated( 'b', true ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketIsStaged( 'b', false ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketSaveError( 'b', '' ) ) );
		expect( gen.next().value ).toEqual( call( sagas.hydrateTicket, 'b', 502 ) );

		// Rejected create at position 2 → c keeps its changes with the message.
		expect( gen.next().value ).toEqual( put( actions.setTicketSaveError( 'c', 'Bad provider' ) ) );

		// Saved updates, in block order: d was rejected, e succeeded.
		expect( gen.next().value ).toEqual( put( actions.setTicketSaveError( 'd', 'Not yours' ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketIsStaged( 'e', false ) ) );
		expect( gen.next().value ).toEqual( put( actions.setTicketSaveError( 'e', '' ) ) );

		// Deletes done, staged lists cleared, editor edit settled, payload rebuilt for what is still staged.
		expect( gen.next().value ).toEqual( put( actions.clearStagedTickets() ) );
		expect( gen.next().value ).toEqual( call( sagas.settlePayloadEdit, response ) );
		expect( gen.next().value ).toEqual( call( sagas.refreshPayload ) );
		expect( gen.next().done ).toBe( true );
	} );

	it( 'does nothing without a response field', () => {
		const gen = sagas.applySaveResponse( undefined );
		expect( gen.next().done ).toBe( true );
	} );
} );
