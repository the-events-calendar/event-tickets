import { validateFields, duplicateFields } from '../utils';

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

	it( 'requires the sale window start not to be after its end', () => {
		expect(
			validateFields( fields( { ticket_add_sale_price: '1', ticket_sale_price: '5', ticket_sale_start_date: '2026-10-02', ticket_sale_end_date: '2026-10-01' } ) )
		).toEqual( [ 'sale_window' ] );
		expect(
			validateFields( fields( { ticket_add_sale_price: '1', ticket_sale_price: '5', ticket_sale_start_date: '2026-10-01', ticket_sale_end_date: '2026-10-01' } ) )
		).toEqual( [] );
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
