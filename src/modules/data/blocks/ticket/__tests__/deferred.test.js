/**
 * Internal dependencies
 */
import { restBodyToTicketData, buildPayload, reconcileSaveResponse } from '../deferred';
import reducer, { DEFAULT_STATE } from '../reducer';
import * as actions from '../actions';
import * as selectors from '../selectors';

// The shared mock replaces one placeholder; these messages use two positional ones.
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
	sprintf: ( text, ...args ) => text.replace( /%(\d+)\$[ds]/g, ( match, position ) => String( args[ position - 1 ] ) ),
} ) );

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

	it( 'sends the attendee fields ET+ adds as JSON as the list the ticket save reads, and leaves out an empty or unreadable one', () => {
		// ET+ Ticket Presets appends them through `tec.tickets.blocks.setBodyDetails`; its `Meta::save_meta()` filters an array.
		const fields = [ { type: 'text', label: 'Name', required: 'on' } ];

		expect( restBodyToTicketData( [ [ 'tribe-tickets-input', JSON.stringify( fields ) ] ] ) ).toEqual( {
			'tribe-tickets-input': fields,
		} );
		// The REST save sent these as a request var that ET+ decodes and, when empty, ignores.
		expect( restBodyToTicketData( [ [ 'tribe-tickets-input', '[]' ] ] ) ).toEqual( {} );
		expect( restBodyToTicketData( [ [ 'tribe-tickets-input', 'not json' ] ] ) ).toEqual( {} );
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
			// Each create carries its block's client ID as its key, so a save sent again never creates it twice.
			create: [
				{ ticket_show_description: 'yes', ticket_name: 'A', tec_tickets_create_key: 'a' },
				{ ticket_show_description: 'yes', ticket_name: 'C', tec_tickets_create_key: 'c' },
			],
			update: { 12: { ticket_show_description: 'yes', ticket_name: 'B' } },
			delete: [ 20 ],
			move: { 21: 99 },
		} );
		expect( createOrder ).toEqual( [ 'a', 'c' ] );
	} );

	it( 'fills what the tickets REST endpoint defaults when the body leaves it out', () => {
		const build = ( body ) =>
			buildPayload( {
				clientIds: [ 'a' ],
				byClientId: { a: ticket( { isStaged: true } ) },
				bodies: { a: body },
				stagedDeletes: [],
				stagedMoves: {},
			} ).payload.create[ 0 ];

		// The block never sends `show_description`; the endpoint stored 'yes', and a missing key makes `ticket_add()` store 'no'.
		expect( build( [ [ 'name', 'A' ] ] ).ticket_show_description ).toBe( 'yes' );
		expect( build( [ [ 'show_description', 'no' ] ] ).ticket_show_description ).toBe( 'no' );
	} );

	it( 'never names a ticket in two parts: a delete wins over a move and an edit, a move over an edit', () => {
		const { payload } = buildPayload( {
			clientIds: [ 'a', 'b' ],
			byClientId: {
				a: ticket( { ticketId: 12, hasBeenCreated: true, isStaged: true } ),
				b: ticket( { ticketId: 13, hasBeenCreated: true, isStaged: true } ),
			},
			bodies: { a: [ [ 'name', 'A' ] ], b: [ [ 'name', 'B' ] ] },
			stagedDeletes: [ 12, 14 ],
			stagedMoves: { 13: 99, 14: 98 },
		} );

		expect( payload.update ).toEqual( {} );
		expect( payload.delete ).toEqual( [ 12, 14 ] );
		expect( payload.move ).toEqual( { 13: 99 } );
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

		block = reducer( block, actions.clearStagedTickets( { deletes: [ 12 ], moves: [ 13 ] } ) );
		expect( selectors.getStagedDeletes( wrap( block ) ) ).toEqual( [] );
		expect( selectors.getStagedMoves( wrap( block ) ) ).toEqual( {} );
	} );

	it( 'settles only the deletes and moves a save sent', () => {
		let block = reducer( withTicket, actions.stageTicketDelete( 12 ) );
		block = reducer( block, actions.stageTicketDelete( 14 ) );
		block = reducer( block, actions.stageTicketMove( 13, 99 ) );
		block = reducer( block, actions.stageTicketMove( 15, 98 ) );

		block = reducer( block, actions.clearStagedTickets( { deletes: [ 12 ], moves: [ 13 ] } ) );

		expect( selectors.getStagedDeletes( wrap( block ) ) ).toEqual( [ 14 ] );
		expect( selectors.getStagedMoves( wrap( block ) ) ).toEqual( { 15: 98 } );
	} );

	it( 'keeps the default state unchanged for existing tests', () => {
		expect( DEFAULT_STATE.stagedDeletes ).toEqual( [] );
		expect( DEFAULT_STATE.stagedMoves ).toEqual( {} );
		// The order staged creates were sent in is kept with what each save sent, not in the store.
		expect( DEFAULT_STATE ).not.toHaveProperty( 'stagedCreateOrder' );
	} );
} );

describe( 'reconcileSaveResponse', () => {
	const bodyA = [ [ 'name', 'A' ] ];
	const bodyB = [ [ 'name', 'B' ] ];
	const bodyU = [ [ 'name', 'U' ] ];
	const sent = {
		createOrder: [ 'a', 'b' ],
		updates: { u: 30 },
		bodies: { a: bodyA, b: bodyB, u: bodyU },
		deletes: [ 40 ],
		moves: { 50: 9 },
	};
	const live = { clientIds: [ 'a', 'b', 'u' ], bodies: { a: bodyA, b: bodyB, u: bodyU } };

	it( 'settles what went through: created IDs by position, saved updates, deletes and moves', () => {
		const outcome = reconcileSaveResponse( { response: { created: { 0: 101, 1: 102 }, errors: [] }, sent, live } );

		expect( outcome.blocks ).toEqual( [
			{ clientId: 'a', ticketId: 101, staged: false, error: '', hook: 'created' },
			{ clientId: 'b', ticketId: 102, staged: false, error: '', hook: 'created' },
			{ clientId: 'u', ticketId: 30, staged: false, error: '', hook: 'updated' },
		] );
		expect( outcome.deleted ).toEqual( [ 40 ] );
		expect( outcome.settle ).toEqual( { deletes: [ 40 ], moves: [ 50 ] } );
		expect( outcome.notices ).toEqual( [] );
	} );

	it( 'leaves a rejected entry staged with its message', () => {
		const outcome = reconcileSaveResponse( {
			response: {
				created: { 0: 101 },
				errors: [
					{ part: 'create', key: 1, message: 'Bad provider' },
					{ part: 'update', key: 30, message: 'Not yours' },
				],
			},
			sent,
			live,
		} );

		expect( outcome.blocks ).toEqual( [
			{ clientId: 'a', ticketId: 101, staged: false, error: '', hook: 'created' },
			{ clientId: 'b', staged: true, error: 'Bad provider', hook: null },
			{ clientId: 'u', staged: true, error: 'Not yours', hook: null },
		] );
	} );

	it( 'keeps everything staged and says why when the whole payload was refused', () => {
		const outcome = reconcileSaveResponse( {
			response: { created: {}, errors: [ { part: null, key: null, message: 'Too many ticket changes' } ] },
			sent,
			live,
		} );

		expect( outcome.blocks ).toEqual( [
			{ clientId: 'a', staged: true, error: 'Too many ticket changes', hook: null },
			{ clientId: 'b', staged: true, error: 'Too many ticket changes', hook: null },
			{ clientId: 'u', staged: true, error: 'Too many ticket changes', hook: null },
		] );
		expect( outcome.deleted ).toEqual( [] );
		expect( outcome.settle ).toEqual( { deletes: [], moves: [] } );
		expect( outcome.notices ).toEqual( [ 'Too many ticket changes' ] );
	} );

	it( 'reports refused deletes and moves, whose blocks are gone, as notices', () => {
		const outcome = reconcileSaveResponse( {
			response: {
				created: { 0: 101, 1: 102 },
				errors: [
					{ part: 'delete', key: 40, message: 'Not allowed' },
					{ part: 'move', key: '50', message: 'Could not move' },
				],
			},
			sent,
			live,
		} );

		expect( outcome.deleted ).toEqual( [] );
		expect( outcome.settle ).toEqual( { deletes: [ 40 ], moves: [ 50 ] } );
		expect( outcome.notices ).toEqual( [ 'Ticket 40: Not allowed', 'Ticket 50: Could not move' ] );
		// Both tickets are still on the post: their blocks come back.
		expect( outcome.restore ).toEqual( [ 40, 50 ] );
	} );

	it( 'brings back the block of a refused ticket only when it is still on the post and no block holds it', () => {
		const outcome = reconcileSaveResponse( {
			response: {
				created: { 0: 101, 1: 102 },
				errors: [
					{ part: 'delete', key: 40, message: 'Ticket 40 does not belong to this post.', not_on_post: true },
					{ part: 'move', key: 50, message: 'Could not move' },
				],
			},
			sent,
			// Undo already brought ticket 50's block back.
			live: { ...live, ticketIds: [ 50 ] },
		} );

		expect( outcome.restore ).toEqual( [] );
		// The refusals are still reported, and neither ticket was deleted.
		expect( outcome.notices ).toHaveLength( 2 );
		expect( outcome.deleted ).toEqual( [] );
	} );

	it( 'does not name the ticket twice when the server\'s reason already names it', () => {
		const outcome = reconcileSaveResponse( {
			response: {
				created: { 0: 101, 1: 102 },
				errors: [
					{ part: 'delete', key: 40, message: 'Ticket 40 does not belong to this post.' },
					{ part: 'move', key: 50, message: 'Ticket 50 could not be moved to post 9.' },
				],
			},
			sent,
			live,
		} );

		expect( outcome.notices ).toEqual( [
			'Ticket 40 does not belong to this post.',
			'Ticket 50 could not be moved to post 9.',
		] );
	} );

	it( 'says a delete or move happened when only what runs after it failed, and brings back no block', () => {
		const outcome = reconcileSaveResponse( {
			response: {
				created: { 0: 101, 1: 102 },
				errors: [
					{ part: 'delete', key: 40, message: 'Deleted, then a listener failed', applied: true },
					{ part: 'move', key: 50, message: 'Moved, then a listener failed', applied: true },
				],
			},
			sent,
			live,
		} );

		// The ticket is gone from this post either way: its block must not come back.
		expect( outcome.restore ).toEqual( [] );
		expect( outcome.deleted ).toEqual( [ 40 ] );
		expect( outcome.settle ).toEqual( { deletes: [ 40 ], moves: [ 50 ] } );
		expect( outcome.notices ).toEqual( [
			'Ticket 40: Deleted, then a listener failed',
			'Ticket 50: Moved, then a listener failed',
		] );
	} );

	it( 'keeps a ticket whose save did not finish staged, with its ID and the error', () => {
		const outcome = reconcileSaveResponse( {
			response: { created: { 0: 101, 1: 102 }, errors: [ { part: 'create', key: 1, message: 'Could not be saved' } ] },
			sent,
			live,
		} );

		// The ticket exists: it gets its ID, so the next save sends an update and never creates it again.
		expect( outcome.blocks[ 1 ] ).toEqual( {
			clientId: 'b',
			ticketId: 102,
			staged: true,
			error: 'Could not be saved',
			hook: 'created',
		} );
	} );

	it( 'settles a create or update that was saved when only what runs after it failed, and keeps the warning', () => {
		const outcome = reconcileSaveResponse( {
			response: {
				created: { 0: 101, 1: 102 },
				errors: [
					{ part: 'create', key: 1, message: 'Saved, then a listener failed', applied: true },
					{ part: 'update', key: 30, message: 'Saved, then a listener failed', applied: true },
				],
			},
			sent,
			live,
		} );

		// Saved: the hooks fire and nothing is sent again; the warning stays on the block.
		expect( outcome.blocks ).toEqual( [
			{ clientId: 'a', ticketId: 101, staged: false, error: '', hook: 'created' },
			{ clientId: 'b', ticketId: 102, staged: false, error: 'Saved, then a listener failed', hook: 'created' },
			{ clientId: 'u', ticketId: 30, staged: false, error: 'Saved, then a listener failed', hook: 'updated' },
		] );
	} );

	it( 'keeps a block staged when it was staged again after the request left', () => {
		const newer = [ [ 'name', 'U2' ] ];
		const outcome = reconcileSaveResponse( {
			response: { created: { 0: 101, 1: 102 }, errors: [] },
			sent,
			live: { clientIds: [ 'a', 'b', 'u' ], bodies: { a: bodyA, b: [ [ 'name', 'B2' ] ], u: newer } },
		} );

		expect( outcome.blocks ).toEqual( [
			{ clientId: 'a', ticketId: 101, staged: false, error: '', hook: 'created' },
			{ clientId: 'b', ticketId: 102, staged: true, error: '', hook: 'created' },
			{ clientId: 'u', ticketId: 30, staged: true, error: '', hook: 'updated' },
		] );
	} );

	it( 'skips blocks removed since the request left and flags a create with no answer', () => {
		const outcome = reconcileSaveResponse( {
			response: { created: { 1: 102 }, errors: [] },
			sent,
			live: { clientIds: [ 'a' ], bodies: { a: bodyA } },
		} );

		expect( outcome.blocks ).toEqual( [
			{ clientId: 'a', staged: true, error: 'The ticket changes were not saved with the post.', hook: null },
		] );
	} );
} );
