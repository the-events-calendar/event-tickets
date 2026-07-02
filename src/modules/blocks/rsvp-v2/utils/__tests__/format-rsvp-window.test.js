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
		globals.tecDateSettings.mockReturnValue( { datepickerFormat: 'Y-m-d' } );
	} );

	it( 'formats start and end dates using toFormat when datepickerFormat is set', () => {
		const start = createMoment( '2026-03-05' );
		const end = createMoment( '2026-03-25' );

		expect( formatRsvpWindow( start, end ) ).toBe( '2026-03-05 - 2026-03-25' );
		expect( start.format ).toHaveBeenCalledWith( 'YYYY-MM-DD' );
		expect( end.format ).toHaveBeenCalledWith( 'YYYY-MM-DD' );
	} );

	it( 'formats dates with n/j/Y datepicker format', () => {
		globals.tecDateSettings.mockReturnValue( { datepickerFormat: 'n/j/Y' } );

		const start = createMoment( '6/29/2026' );
		const end = createMoment( '6/29/2026' );

		expect( formatRsvpWindow( start, end ) ).toBe( '6/29/2026 - 6/29/2026' );
		expect( start.format ).toHaveBeenCalledWith( 'M/D/YYYY' );
		expect( end.format ).toHaveBeenCalledWith( 'M/D/YYYY' );
	} );

	it( 'falls back to F j, Y format when datepickerFormat is not set', () => {
		globals.tecDateSettings.mockReturnValue( {} );

		const start = createMoment( 'June 29, 2026' );
		const end = createMoment( 'June 29, 2026' );

		expect( formatRsvpWindow( start, end ) ).toBe( 'June 29, 2026 - June 29, 2026' );
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
