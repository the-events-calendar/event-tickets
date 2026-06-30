/**
 * Internal dependencies
 */
import { formatRsvpWindow } from '../format-rsvp-window';

const createMoment = ( formatted ) => ( {
	isValid: () => true,
	format: jest.fn( () => formatted ),
} );

describe( 'formatRsvpWindow', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'formats start and end dates using LL format', () => {
		const start = createMoment( 'March 5, 2026' );
		const end = createMoment( 'March 25, 2026' );

		expect( formatRsvpWindow( start, end ) ).toBe( 'March 5, 2026 - March 25, 2026' );
		expect( start.format ).toHaveBeenCalledWith( 'LL' );
		expect( end.format ).toHaveBeenCalledWith( 'LL' );
	} );

	it( 'handles same-day range', () => {
		const start = createMoment( 'March 5, 2026' );
		const end = createMoment( 'March 5, 2026' );

		expect( formatRsvpWindow( start, end ) ).toBe( 'March 5, 2026 - March 5, 2026' );
	} );

	it( 'returns empty string when dates are missing', () => {
		expect( formatRsvpWindow( null, null ) ).toBe( '' );
		expect( formatRsvpWindow( { isValid: () => false }, { isValid: () => true } ) ).toBe( '' );
	} );
} );
