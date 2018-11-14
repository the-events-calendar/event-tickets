import { getValues } from './../schema';

describe( 'getValues', () => {
	it( 'default values on empty items', () => {
		expect( getValues( [] ) ).toEqual( { names: [], total: 0 } );
	} );

	it( 'name list and total', () => {
		const items = [
			{ title: 'VIP', capacity: 10 },
			{ title: 'Early Bird', capacity: 22 },
			{ title: 'Floor', capacity: 8 },
		];
		const expected = {
			names: [ 'VIP', 'Early Bird', 'Floor' ],
			total: 40,
		};
		expect( getValues( items ) ).toEqual( expected );
	} );

	it( 'items with invalid schema', () => {
		const items = [
			{ title: '', total: 10 },
			{ title: '', amount: 22 },
			{ title: '', remaining: 8 },
		];
		const expected = {
			names: [],
			total: 0,
		};
		expect( getValues( items ) ).toEqual( expected );
	} );
} );
