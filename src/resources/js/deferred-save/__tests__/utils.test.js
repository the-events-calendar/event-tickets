import {
	bracketName,
	buildHiddenFields,
	createState,
	summaryFromFields,
	fieldsWithoutTicketId,
} from '../utils';

describe( 'bracketName', () => {
	it( 'turns a flat input name into a bracket segment', () => {
		expect( bracketName( 'ticket_name' ) ).toBe( '[ticket_name]' );
	} );

	it( 'keeps nested names nested', () => {
		expect( bracketName( 'tribe-ticket[capacity]' ) ).toBe( '[tribe-ticket][capacity]' );
		expect( bracketName( 'tribe-tickets-input[3][label]' ) ).toBe( '[tribe-tickets-input][3][label]' );
	} );

	it( 'keeps list names lists', () => {
		expect( bracketName( 'tribe-ticket[fees][selected_fees][]' ) ).toBe( '[tribe-ticket][fees][selected_fees][]' );
	} );
} );

describe( 'fieldsWithoutTicketId', () => {
	it( 'drops ticket_id so a create never carries one', () => {
		expect( fieldsWithoutTicketId( [ [ 'ticket_id', '12' ], [ 'ticket_name', 'A' ] ] ) ).toEqual( [ [ 'ticket_name', 'A' ] ] );
	} );
} );

describe( 'summaryFromFields', () => {
	it( 'reads name, price, capacity, type and provider', () => {
		const fields = [
			[ 'ticket_name', 'Early bird' ],
			[ 'ticket_price', '12.50' ],
			[ 'tribe-ticket[capacity]', '40' ],
			[ 'ticket_type', 'default' ],
			[ 'ticket_provider', 'TEC\\Tickets\\Commerce\\Module' ],
		];

		expect( summaryFromFields( fields ) ).toEqual( {
			name: 'Early bird',
			price: '12.50',
			capacity: '40',
			type: 'default',
			provider: 'TEC\\Tickets\\Commerce\\Module',
		} );
	} );

	it( 'falls back to empty strings', () => {
		expect( summaryFromFields( [] ) ).toEqual( { name: '', price: '', capacity: '', type: '', provider: '' } );
	} );
} );

describe( 'state', () => {
	const fieldsA = [ [ 'ticket_name', 'A' ], [ 'ticket_price', '1' ], [ 'ticket_provider', 'P' ], [ 'ticket_type', 'default' ] ];
	const fieldsB = [ [ 'ticket_name', 'B' ], [ 'ticket_price', '2' ], [ 'ticket_provider', 'P' ], [ 'ticket_type', 'default' ] ];

	it( 'starts empty', () => {
		const state = createState();
		expect( state.hasChanges() ).toBe( false );
		expect( state.toPayload() ).toEqual( { create: [], update: {}, delete: [], move: {} } );
	} );

	it( 'stages a new ticket and returns its position', () => {
		const state = createState();
		expect( state.stageCreate( fieldsA ) ).toBe( 0 );
		expect( state.stageCreate( fieldsB ) ).toBe( 1 );
		expect( state.hasChanges() ).toBe( true );
		expect( state.toPayload().create.map( ( e ) => e.summary.name ) ).toEqual( [ 'A', 'B' ] );
	} );

	it( 'restages the same position when a staged ticket is edited again', () => {
		const state = createState();
		state.stageCreate( fieldsA );
		state.stageCreate( fieldsB );
		state.restageCreate( 0, [ [ 'ticket_name', 'A2' ], [ 'ticket_provider', 'P' ] ] );
		expect( state.toPayload().create.map( ( e ) => e.summary.name ) ).toEqual( [ 'A2', 'B' ] );
	} );

	it( 'drops a staged ticket and renumbers the rest', () => {
		const state = createState();
		state.stageCreate( fieldsA );
		state.stageCreate( fieldsB );
		state.dropCreate( 0 );
		expect( state.toPayload().create.map( ( e ) => e.summary.name ) ).toEqual( [ 'B' ] );
		expect( state.hasChanges() ).toBe( true );
	} );

	it( 'stages an update for a saved ticket and a delete replaces it', () => {
		const state = createState();
		state.stageUpdate( 12, fieldsA );
		expect( Object.keys( state.toPayload().update ) ).toEqual( [ '12' ] );
		state.stageDelete( 12 );
		expect( state.toPayload().update ).toEqual( {} );
		expect( state.toPayload().delete ).toEqual( [ 12 ] );
		expect( state.isDeleted( 12 ) ).toBe( true );
	} );

	it( 'undoes a staged delete', () => {
		const state = createState();
		state.stageDelete( 12 );
		state.stageDelete( 12 );
		expect( state.toPayload().delete ).toEqual( [ 12 ] );
		state.undoDelete( 12 );
		expect( state.toPayload().delete ).toEqual( [] );
		expect( state.hasChanges() ).toBe( false );
	} );

	it( 'stages a move with the destination title for the marker', () => {
		const state = createState();
		state.stageMove( 12, 99, 'Other event' );
		expect( state.toPayload().move ).toEqual( { 12: 99 } );
		expect( state.getMove( 12 ) ).toEqual( { destinationId: 99, destinationTitle: 'Other event' } );
		state.undoMove( 12 );
		expect( state.toPayload().move ).toEqual( {} );
	} );
} );

describe( 'buildHiddenFields', () => {
	it( 'writes every part in the tec_tickets contract', () => {
		const state = createState();
		state.stageCreate( [ [ 'ticket_name', 'New' ], [ 'tribe-ticket[capacity]', '5' ], [ 'ticket_provider', 'P' ] ] );
		state.stageUpdate( 7, [ [ 'ticket_id', '7' ], [ 'ticket_name', 'Renamed' ], [ 'tribe-ticket[fees][selected_fees][]', '3' ] ] );
		state.stageDelete( 8 );
		state.stageMove( 9, 42, 'Elsewhere' );

		expect( buildHiddenFields( state ) ).toEqual( [
			[ 'tec_tickets[create][0][ticket_name]', 'New' ],
			[ 'tec_tickets[create][0][tribe-ticket][capacity]', '5' ],
			[ 'tec_tickets[create][0][ticket_provider]', 'P' ],
			[ 'tec_tickets[update][7][ticket_id]', '7' ],
			[ 'tec_tickets[update][7][ticket_name]', 'Renamed' ],
			[ 'tec_tickets[update][7][tribe-ticket][fees][selected_fees][]', '3' ],
			[ 'tec_tickets[delete][]', '8' ],
			[ 'tec_tickets[move][9]', '42' ],
		] );
	} );

	it( 'writes nothing for an empty state', () => {
		expect( buildHiddenFields( createState() ) ).toEqual( [] );
	} );
} );
