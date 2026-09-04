/**
 * Internal dependencies
 */
import { globals } from '@moderntribe/common/utils';
import { formatRsvpWindow } from '../format-rsvp-window';

const createMoment = ( formatted ) => ( {
	isValid: () => true,
	format: jest.fn( () => formatted ),
} );

describe( 'formatRsvpWindow', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		globals.dateSettings.mockReturnValue( { formats: { date: 'F j, Y' } } );
	} );

	it( 'formats dates using the WordPress date format', () => {
		const start = createMoment( 'June 29, 2026' );
		const end = createMoment( 'July 2, 2026' );

		expect( formatRsvpWindow( start, end ) ).toBe( 'June 29, 2026 - July 2, 2026' );
		expect( start.format ).toHaveBeenCalledWith( 'MMMM D, YYYY' );
		expect( end.format ).toHaveBeenCalledWith( 'MMMM D, YYYY' );
	} );

	it( 'handles same-day range', () => {
		const start = createMoment( '2026-03-05' );
		const end = createMoment( '2026-03-05' );

		expect( formatRsvpWindow( start, end ) ).toBe( '2026-03-05 - 2026-03-05' );
	} );

	it( 'returns empty string when dates are missing', () => {
		expect( formatRsvpWindow( null, null ) ).toBe( '' );
		expect( formatRsvpWindow( { isValid: () => false }, { isValid: () => true } ) ).toBe( '' );
	} );

	it( 'returns empty string when startDateMoment is invalid', () => {
		expect( formatRsvpWindow( undefined, createMoment( 'June 29, 2026' ) ) ).toBe( '' );
	} );

	it( 'returns empty string when endDateMoment is invalid', () => {
		expect( formatRsvpWindow( createMoment( 'June 29, 2026' ), undefined ) ).toBe( '' );
	} );
} );
