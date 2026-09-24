/**
 * Internal dependencies
 */
import { restBodyToTicketData, buildPayload } from '../deferred';
import reducer, { DEFAULT_STATE } from '../reducer';
import * as actions from '../actions';
import * as selectors from '../selectors';

describe( 'restBodyToTicketData', () => {
	it( 'maps the REST body keys to the keys the ticket save accepts', () => {
		const data = restBodyToTicketData( [
			[ 'post_id', '48' ],
			[ 'add_ticket_nonce', 'abc' ],
			[ 'provider', 'tribe_tickets_rsvp' ],
			[ 'name', 'General' ],
			[ 'description', 'Desc' ],
			[ 'price', '10' ],
			[ 'show_description', 'yes' ],
			[ 'start_date', '2026-10-01' ],
			[ 'start_time', '08:00:00' ],
			[ 'end_date', '2026-10-05' ],
			[ 'end_time', '17:00:00' ],
			[ 'sku', 'GEN' ],
			[ 'iac', 'none' ],
			[ 'menu_order', '2' ],
			[ 'ticket[mode]', 'own' ],
			[ 'ticket[capacity]', '40' ],
			[ 'ticket[sale_price][checked]', '1' ],
			[ 'ticket[sale_price][price]', '8' ],
			[ 'ticket[sale_price][start_date]', '2026-09-01' ],
			[ 'ticket[sale_price][end_date]', '2026-09-30' ],
			[ 'ticket[seating][enabled]', '1' ],
			[ 'something_else', 'kept' ],
		] );

		expect( data ).toEqual( {
			ticket_provider: 'tribe_tickets_rsvp',
			ticket_name: 'General',
			ticket_description: 'Desc',
			ticket_price: '10',
			ticket_show_description: 'yes',
			ticket_start_date: '2026-10-01',
			ticket_start_time: '08:00:00',
			ticket_end_date: '2026-10-05',
			ticket_end_time: '17:00:00',
			ticket_sku: 'GEN',
			ticket_iac: 'none',
			ticket_menu_order: '2',
			'tribe-ticket': { mode: 'own', capacity: '40', seating: { enabled: '1' } },
			ticket_add_sale_price: '1',
			ticket_sale_price: '8',
			ticket_sale_start_date: '2026-09-01',
			ticket_sale_end_date: '2026-09-30',
			something_else: 'kept',
		} );
	} );

	it( 'never writes through the prototype chain and never reads inherited mappings', () => {
		const data = restBodyToTicketData( [
			[ 'ticket[__proto__][polluted]', 'yes' ],
			[ '__proto__[isAdmin]', 'true' ],
			[ 'constructor', 'x' ],
			[ 'ticket[constructor][prototype][evil]', '1' ],
			[ 'hasOwnProperty', 'y' ],
			[ 'name', 'Safe' ],
		] );

		expect( {}.polluted ).toBeUndefined();
		expect( {}.isAdmin ).toBeUndefined();
		expect( {}.evil ).toBeUndefined();
		expect( data.ticket_name ).toBe( 'Safe' );
		// A plain key that happens to be a method name is an own property on the data, nothing more.
		expect( Object.keys( data ) ).toEqual( [ 'hasOwnProperty', 'ticket_name' ] );
	} );

	it( 'keeps list keys as arrays', () => {
		expect( restBodyToTicketData( [ [ 'ticket[fees][selected_fees][]', '3' ], [ 'ticket[fees][selected_fees][]', '4' ] ] ) ).toEqual( {
			'tribe-ticket': { fees: { selected_fees: [ '3', '4' ] } },
		} );
	} );
} );

describe( 'buildPayload', () => {
	const ticket = ( overrides ) => ( { ticketId: 0, hasBeenCreated: false, isStaged: false, ...overrides } );

	it( 'puts staged new tickets in create in block order, staged saved ones in update, and carries deletes and moves', () => {
		const { payload, createOrder } = buildPayload( {
			clientIds: [ 'a', 'b', 'c', 'd' ],
			byClientId: {
				a: ticket( { isStaged: true } ),
				b: ticket( { ticketId: 12, hasBeenCreated: true, isStaged: true } ),
				c: ticket( { isStaged: true } ),
				d: ticket( { ticketId: 13, hasBeenCreated: true } ),
			},
			bodies: {
				a: [ [ 'name', 'A' ] ],
				b: [ [ 'name', 'B' ] ],
				c: [ [ 'name', 'C' ] ],
				d: [ [ 'name', 'D' ] ],
			},
			stagedDeletes: [ 20 ],
			stagedMoves: { 21: 99 },
		} );

		expect( payload ).toEqual( {
			create: [ { ticket_name: 'A' }, { ticket_name: 'C' } ],
			update: { 12: { ticket_name: 'B' } },
			delete: [ 20 ],
			move: { 21: 99 },
		} );
		expect( createOrder ).toEqual( [ 'a', 'c' ] );
	} );

	it( 'is empty when nothing is staged', () => {
		expect( buildPayload( { clientIds: [], byClientId: {}, bodies: {}, stagedDeletes: [], stagedMoves: {} } ).payload ).toEqual( {
			create: [],
			update: {},
			delete: [],
			move: {},
		} );
	} );
} );

describe( 'staged state in the store', () => {
	const withTicket = reducer( DEFAULT_STATE, actions.registerTicketBlock( 'a' ) );
	const wrap = ( block ) => ( { tickets: { blocks: { ticket: block } } } );

	it( 'marks a ticket staged and records a save error', () => {
		let block = reducer( withTicket, actions.setTicketIsStaged( 'a', true ) );
		expect( selectors.getTicketIsStaged( wrap( block ), { clientId: 'a' } ) ).toBe( true );

		block = reducer( block, actions.setTicketSaveError( 'a', 'Nope' ) );
		expect( selectors.getTicketSaveError( wrap( block ), { clientId: 'a' } ) ).toBe( 'Nope' );
	} );

	it( 'stages deletes and moves at block level and clears them', () => {
		let block = reducer( withTicket, actions.stageTicketDelete( 12 ) );
		block = reducer( block, actions.stageTicketDelete( 12 ) );
		block = reducer( block, actions.stageTicketMove( 13, 99 ) );
		expect( selectors.getStagedDeletes( wrap( block ) ) ).toEqual( [ 12 ] );
		expect( selectors.getStagedMoves( wrap( block ) ) ).toEqual( { 13: 99 } );

		block = reducer( block, actions.setStagedCreateOrder( [ 'a' ] ) );
		expect( selectors.getStagedCreateOrder( wrap( block ) ) ).toEqual( [ 'a' ] );

		block = reducer( block, actions.clearStagedTickets() );
		expect( selectors.getStagedDeletes( wrap( block ) ) ).toEqual( [] );
		expect( selectors.getStagedMoves( wrap( block ) ) ).toEqual( {} );
		expect( selectors.getStagedCreateOrder( wrap( block ) ) ).toEqual( [] );
	} );

	it( 'keeps the default state unchanged for existing tests', () => {
		expect( DEFAULT_STATE.stagedDeletes ).toEqual( [] );
		expect( DEFAULT_STATE.stagedMoves ).toEqual( {} );
		expect( DEFAULT_STATE.stagedCreateOrder ).toEqual( [] );
	} );
} );
