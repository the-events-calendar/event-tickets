import { validateFields, duplicateFields, copyOfSaved, createState, DUPLICATE_OF } from '../utils';

const fields = ( overrides = {} ) =>
	Object.entries( {
		ticket_name: 'Early bird',
		ticket_price: '10',
		'tribe-ticket[capacity]': '50',
		ticket_add_sale_price: '',
		ticket_sale_price: '',
		ticket_sale_start_date: '',
		ticket_sale_end_date: '',
		...overrides,
	} ).map( ( [ name, value ] ) => [ name, value ] );

describe( 'validateFields', () => {
	it( 'passes a plain ticket', () => {
		expect( validateFields( fields() ) ).toEqual( [] );
	} );

	it( 'requires a name', () => {
		expect( validateFields( fields( { ticket_name: '   ' } ) ) ).toEqual( [ 'name' ] );
	} );

	it( 'requires a non-negative numeric price when one is given', () => {
		expect( validateFields( fields( { ticket_price: 'abc' } ) ) ).toEqual( [ 'price' ] );
		expect( validateFields( fields( { ticket_price: '-1' } ) ) ).toEqual( [ 'price' ] );
		expect( validateFields( fields( { ticket_price: '' } ) ) ).toEqual( [] );
		expect( validateFields( fields( { ticket_price: '0' } ) ) ).toEqual( [] );
	} );

	it( 'requires a sale price below the price when sale price is on', () => {
		expect( validateFields( fields( { ticket_add_sale_price: '1', ticket_sale_price: '12' } ) ) ).toEqual( [ 'sale_price' ] );
		expect( validateFields( fields( { ticket_add_sale_price: '1', ticket_sale_price: '10' } ) ) ).toEqual( [ 'sale_price' ] );
		expect( validateFields( fields( { ticket_add_sale_price: '1', ticket_sale_price: '8' } ) ) ).toEqual( [] );
		expect( validateFields( fields( { ticket_add_sale_price: '', ticket_sale_price: '12' } ) ) ).toEqual( [] );
	} );

	it( 'reads prices with the site\'s decimal separator, whatever the number of decimals', () => {
		const sale = ( price, salePrice ) => fields( { ticket_price: price, ticket_add_sale_price: '1', ticket_sale_price: salePrice } );

		// A three-decimal currency: 9.750 is below 10.000, not 9750.
		expect( validateFields( sale( '10.000', '9.750' ), { decimal: '.' } ) ).toEqual( [] );
		expect( validateFields( sale( '10,000', '9,750' ), { decimal: ',' } ) ).toEqual( [] );
		expect( validateFields( sale( '10,000', '10,500' ), { decimal: ',' } ) ).toEqual( [ 'sale_price' ] );
		// A thousands separator is the other mark; one more decimal separator is not a price.
		expect( validateFields( fields( { ticket_price: '1,234.50' } ), { decimal: '.' } ) ).toEqual( [] );
		expect( validateFields( fields( { ticket_price: '1.234,50' } ), { decimal: ',' } ) ).toEqual( [] );
		expect( validateFields( fields( { ticket_price: '1.000.5' } ), { decimal: '.' } ) ).toEqual( [ 'price' ] );
	} );

	it( 'requires the sale window start not to be after its end', () => {
		expect(
			validateFields( fields( { ticket_add_sale_price: '1', ticket_sale_price: '5', ticket_sale_start_date: '2026-10-02', ticket_sale_end_date: '2026-10-01' } ) )
		).toEqual( [ 'sale_window' ] );
		expect(
			validateFields( fields( { ticket_add_sale_price: '1', ticket_sale_price: '5', ticket_sale_start_date: '2026-10-01', ticket_sale_end_date: '2026-10-01' } ) )
		).toEqual( [] );
	} );

	it( 'reads the sale window in the site\'s date format', () => {
		const window = ( start, end ) =>
			fields( { ticket_add_sale_price: '1', ticket_sale_price: '5', ticket_sale_start_date: start, ticket_sale_end_date: end } );

		// d/m/Y (format 4): 12 September to 5 October is a valid window; read as m/d/Y it would not be.
		expect( validateFields( window( '12/09/2026', '05/10/2026' ), { dateFormat: 4 } ) ).toEqual( [] );
		expect( validateFields( window( '05/10/2026', '12/09/2026' ), { dateFormat: 4 } ) ).toEqual( [ 'sale_window' ] );
		// d.m.Y (format 11) and j-n-Y (format 7).
		expect( validateFields( window( '12.09.2026', '05.10.2026' ), { dateFormat: 11 } ) ).toEqual( [] );
		expect( validateFields( window( '5-10-2026', '12-9-2026' ), { dateFormat: 7 } ) ).toEqual( [ 'sale_window' ] );
		// A date the format cannot read is left to the server, never reported as a bad window.
		expect( validateFields( window( 'soon', '05/10/2026' ), { dateFormat: 4 } ) ).toEqual( [] );
	} );

	it( 'reads prices written with a thousands separator', () => {
		expect( validateFields( fields( { ticket_price: '1,234.50' } ) ) ).toEqual( [] );
		expect( validateFields( fields( { ticket_price: '1.234,50' } ) ) ).toEqual( [] );
		expect(
			validateFields( fields( { ticket_price: '1,234.50', ticket_add_sale_price: '1', ticket_sale_price: '999.99' } ) )
		).toEqual( [] );
		expect(
			validateFields( fields( { ticket_price: '1.234,50', ticket_add_sale_price: '1', ticket_sale_price: '1.300,00' } ) )
		).toEqual( [ 'sale_price' ] );
	} );

	it( 'requires the capacity not to fall below tickets sold, when sold is known', () => {
		expect( validateFields( fields( { 'tribe-ticket[capacity]': '3' } ), { sold: 5 } ) ).toEqual( [ 'capacity' ] );
		expect( validateFields( fields( { 'tribe-ticket[capacity]': '5' } ), { sold: 5 } ) ).toEqual( [] );
		expect( validateFields( fields( { 'tribe-ticket[capacity]': '' } ), { sold: 5 } ) ).toEqual( [] );
		expect( validateFields( fields( { 'tribe-ticket[capacity]': '3' } ) ) ).toEqual( [] );
	} );

	it( 'reports every failing rule', () => {
		expect( validateFields( fields( { ticket_name: '', ticket_price: 'x' } ) ) ).toEqual( [ 'name', 'price' ] );
	} );
} );

describe( 'duplicateFields', () => {
	it( 'copies the fields with a "(copy)" name and without id or sku', () => {
		const copy = duplicateFields( [ [ 'ticket_id', '9' ], [ 'ticket_name', 'VIP' ], [ 'ticket_sku', 'VIP-1' ], [ 'ticket_price', '20' ] ] );

		expect( copy ).toEqual( [ [ 'ticket_name', 'VIP (copy)' ], [ 'ticket_price', '20' ] ] );
	} );
} );

describe( 'duplicateFields with a translated suffix', () => {
	it( 'names the copy with the suffix the site translates', () => {
		expect( duplicateFields( [ [ 'ticket_name', 'VIP' ] ], '(copie)' ) ).toEqual( [ [ 'ticket_name', 'VIP (copie)' ] ] );
		expect( copyOfSaved( [ [ 'ticket_name', 'VIP' ] ], 9, '(copie)' )[ 0 ] ).toEqual( [ 'ticket_name', 'VIP (copie)' ] );
	} );
} );

describe( 'copyOfSaved', () => {
	it( 'names the saved ticket it copies, so the server copies what the form does not carry', () => {
		const copy = copyOfSaved( [ [ 'ticket_id', '9' ], [ 'ticket_name', 'VIP' ] ], 9 );

		expect( copy ).toEqual( [
			[ 'ticket_name', 'VIP (copy)' ],
			[ DUPLICATE_OF, '9' ],
		] );
	} );

	it( 'keeps the ticket it copies when the copy is edited again', () => {
		const state = createState();
		const position = state.stageCreate( copyOfSaved( [ [ 'ticket_name', 'VIP' ] ], 9 ) );

		// The edit panel has no field for it.
		state.restageCreate( position, [ [ 'ticket_name', 'VIP renamed' ] ] );

		expect( state.getCreate( position ).fields ).toEqual( [
			[ 'ticket_name', 'VIP renamed' ],
			[ DUPLICATE_OF, '9' ],
		] );
	} );
} );
