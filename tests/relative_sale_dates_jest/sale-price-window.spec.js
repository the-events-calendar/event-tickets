import { isValidSalePriceRule, resolveSalePriceDates } from '@tec/tickets/relative-sale-dates/sale-price-window';
import fixtures from '../_data/relative-sale-dates/sale-price-cases.json';

describe( 'resolveSalePriceDates', () => {
	it.each( fixtures.cases.map( ( fixture ) => [ fixture.name, fixture ] ) )(
		'should resolve the shared fixture: %s',
		( name, { rule, timezone, event_start: start, event_end: end, expected } ) => {
			expect( resolveSalePriceDates( rule, { start, end, timezone } ) ).toStrictEqual( expected );
		}
	);
} );

describe( 'isValidSalePriceRule', () => {
	it.each( fixtures.cases.map( ( fixture ) => [ fixture.name, fixture.rule ] ) )(
		'should accept the shared fixture rule: %s',
		( name, rule ) => {
			expect( isValidSalePriceRule( rule ) ).toBe( true );
		}
	);

	it.each( fixtures.invalid_rules.map( ( fixture ) => [ fixture.name, fixture.rule ] ) )(
		'should reject the shared fixture invalid rule: %s',
		( name, rule ) => {
			expect( isValidSalePriceRule( rule ) ).toBe( false );
		}
	);
} );
