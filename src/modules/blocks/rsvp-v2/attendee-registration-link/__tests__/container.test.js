/**
 * External dependencies
 */
jest.mock( 'react-redux', () => ( {
	connect: jest.fn( () => ( Component ) => Component ),
} ) );

jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => Component,
} ) );

jest.mock( '../../../rsvp-shared/attendee-registration/container', () => () => null );

jest.mock( '../../../../data/blocks/rsvp-v2', () => ( {
	actions: {
		setRSVPIsModalOpen: jest.fn( ( isOpen ) => ( { type: 'SET_RSVP_IS_MODAL_OPEN', payload: { isOpen } } ) ),
	},
	selectors: {
		getRSVPCreated: jest.fn(),
		getRSVPIsLoading: jest.fn(),
		getRSVPSettingsOpen: jest.fn(),
	},
	thunks: {
		persistRSVP: jest.fn( () => ( { type: 'PERSIST_RSVP' } ) ),
	},
} ) );

/**
 * Internal dependencies
 */
import RSVPAttendeeRegistrationLink, { getIsDisabled, mergeProps } from '../container';
import { actions, selectors, thunks } from '../../../../data/blocks/rsvp-v2';

describe( 'RSVP V2 Attendee Registration Link', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render the shared attendee registration container with V2 link copy', () => {
		expect( RSVPAttendeeRegistrationLink ).toBeDefined();
	} );

	describe( 'getIsDisabled', () => {
		it( 'should be disabled while loading or settings are open', () => {
			selectors.getRSVPIsLoading.mockReturnValue( true );
			selectors.getRSVPSettingsOpen.mockReturnValue( false );

			expect( getIsDisabled( {} ) ).toBe( true );
		} );

		it( 'should not be disabled when the RSVP is not created yet', () => {
			selectors.getRSVPIsLoading.mockReturnValue( false );
			selectors.getRSVPSettingsOpen.mockReturnValue( false );

			// The link must be available before the RSVP is created so attendee
			// information can be collected from the first load of the editor.
			expect( getIsDisabled( {} ) ).toBe( false );
		} );
	} );

	describe( 'mergeProps onClick', () => {
		const createState = ( { created = false, isLoading = false, settingsOpen = false } = {} ) => {
			selectors.getRSVPCreated.mockReturnValue( created );
			selectors.getRSVPIsLoading.mockReturnValue( isLoading );
			selectors.getRSVPSettingsOpen.mockReturnValue( settingsOpen );

			return { tickets: { blocks: { rsvp: {} } } };
		};

		it( 'should persist the RSVP before opening the modal when not created', async () => {
			thunks.persistRSVP.mockImplementation( () => async () => {
				// Simulate the RSVP being created by the thunk.
				selectors.getRSVPCreated.mockReturnValue( true );
			} );

			const dispatch = jest.fn( ( action ) => ( typeof action === 'function' ? action( dispatch ) : action ) );
			const state = createState( { created: false } );
			const store = { getState: () => state };

			const { onClick } = mergeProps( { state }, { dispatch }, { store } );

			await onClick();

			expect( thunks.persistRSVP ).toHaveBeenCalledTimes( 1 );
			expect( dispatch ).toHaveBeenCalledWith( actions.setRSVPIsModalOpen( true ) );
		} );

		it( 'should open the modal directly when the RSVP is already created', async () => {
			const dispatch = jest.fn( ( action ) => ( typeof action === 'function' ? action( dispatch ) : action ) );
			const state = createState( { created: true } );
			const store = { getState: () => state };

			const { onClick } = mergeProps( { state }, { dispatch }, { store } );

			await onClick();

			expect( thunks.persistRSVP ).not.toHaveBeenCalled();
			expect( dispatch ).toHaveBeenCalledWith( actions.setRSVPIsModalOpen( true ) );
		} );

		it( 'should not open the modal when persisting fails to create the RSVP', async () => {
			thunks.persistRSVP.mockImplementation( () => async () => {} );

			const dispatch = jest.fn( ( action ) => ( typeof action === 'function' ? action( dispatch ) : action ) );
			const state = createState( { created: false } );
			const store = { getState: () => state };

			const { onClick } = mergeProps( { state }, { dispatch }, { store } );

			await onClick();

			expect( thunks.persistRSVP ).toHaveBeenCalledTimes( 1 );
			expect( dispatch ).not.toHaveBeenCalledWith( actions.setRSVPIsModalOpen( true ) );
		} );
	} );
} );
