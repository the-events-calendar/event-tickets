/**
 * Internal dependencies
 */
import { formatRsvpWindow } from '../format-rsvp-window';

jest.mock( '@moderntribe/common/utils', () => ( {
	globals: {
		tecDateSettings: jest.fn(),
	},
	moment: {
		toFormat: jest.fn( ( format ) => `converted-${ format }` ),
	},
} ) );

const { globals, moment: momentUtil } = require( '@moderntribe/common/utils' );

const createMoment = ( formatted ) => ( {
	isValid: () => true,
	format: jest.fn( () => formatted ),
} );

describe( 'formatRsvpWindow', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		globals.tecDateSettings.mockReturnValue( { datepickerFormat: 'm/d/y' } );
		momentUtil.toFormat.mockImplementation( ( format ) => `converted-${ format }` );
	} );

	it( 'formats start and end dates with the compact date format', () => {
		const start = createMoment( '3/5/26' );
		const end = createMoment( '3/25/26' );

		expect( formatRsvpWindow( start, end ) ).toBe( '3/5/26 - 3/25/26' );
		expect( momentUtil.toFormat ).toHaveBeenCalledWith( 'm/d/y' );
	} );

	it( 'handles same-day range', () => {
		const start = createMoment( '3/5/26' );
		const end = createMoment( '3/5/26' );

		expect( formatRsvpWindow( start, end ) ).toBe( '3/5/26 - 3/5/26' );
	} );

	it( 'returns empty string when dates are missing', () => {
		expect( formatRsvpWindow( null, null ) ).toBe( '' );
		expect( formatRsvpWindow( { isValid: () => false }, { isValid: () => true } ) ).toBe( '' );
	} );

	it( 'uses LL format when datepickerFormat is not set', () => {
		globals.tecDateSettings.mockReturnValue( { datepickerFormat: false } );
		const start = createMoment( 'March 5, 2026' );
		const end = createMoment( 'March 25, 2026' );

		expect( formatRsvpWindow( start, end ) ).toBe( 'March 5, 2026 - March 25, 2026' );
		expect( start.format ).toHaveBeenCalledWith( 'LL' );
	} );
} );
