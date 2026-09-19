/**
 * Internal dependencies
 */
import { hydrateRsvpFromEditorConfig } from '../hydrate-rsvp-from-editor-config';
import { getInitialTicket } from '../../config';
import { hydrateRsvpFromTicket } from '../../../rsvp-shared/utils/hydrate-rsvp-from-ticket';

jest.mock( '../../config', () => ( {
	getInitialTicket: jest.fn(),
} ) );

jest.mock( '../../../rsvp-shared/utils/hydrate-rsvp-from-ticket', () => ( {
	hydrateRsvpFromTicket: jest.fn(),
} ) );

describe( 'hydrateRsvpFromEditorConfig', () => {
	let dispatch;
	let actions;

	beforeEach( () => {
		dispatch = jest.fn();
		actions = { setRSVPIAC: jest.fn() };

		getInitialTicket.mockReset();
		hydrateRsvpFromTicket.mockReset();

		window.tribe_editor_config = {};
	} );

	it( 'should seed the global IAC default when there is no initial ticket', () => {
		getInitialTicket.mockReturnValue( null );
		hydrateRsvpFromTicket.mockReturnValue( false );

		window.tribe_editor_config.ticketsPlus = {
			iacVars: { iacDefault: 'allowed' },
		};

		const hydrated = hydrateRsvpFromEditorConfig( dispatch, actions );

		expect( hydrated ).toBe( false );
		expect( actions.setRSVPIAC ).toHaveBeenCalledWith( 'allowed' );
	} );

	it( 'should seed none when there is no initial ticket and no iacDefault is set', () => {
		getInitialTicket.mockReturnValue( null );
		hydrateRsvpFromTicket.mockReturnValue( false );

		const hydrated = hydrateRsvpFromEditorConfig( dispatch, actions );

		expect( hydrated ).toBe( false );
		expect( actions.setRSVPIAC ).toHaveBeenCalledWith( 'none' );
	} );

	it( 'should not seed the default when an initial ticket is hydrated', () => {
		getInitialTicket.mockReturnValue( { id: 42 } );
		hydrateRsvpFromTicket.mockReturnValue( true );

		const hydrated = hydrateRsvpFromEditorConfig( dispatch, actions );

		expect( hydrated ).toBe( true );
		expect( actions.setRSVPIAC ).not.toHaveBeenCalled();
	} );
} );
