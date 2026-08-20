/**
 * External dependencies
 */
import moment from 'moment';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { select } from '@wordpress/data';

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { createRSVP, getRSVP, persistRSVP, updateRSVP } from '../thunks';
import { types } from '../../rsvp';
import { moment as momentUtil } from '@moderntribe/common/utils';

const getState = () => ( {
	tickets: {
		blocks: {
			rsvp: {
				isLoading: false,
			},
		},
	},
} );

const startDateMoment = moment( '2026-06-01' ).startOf( 'day' );
const endDateMoment = moment( '2026-06-02' ).startOf( 'day' );

const basePayload = {
	capacity: 10,
	notGoingResponses: true,
	startDateMoment,
	startTime: '09:00',
	endDateMoment,
	endTime: '17:00',
};

const configureMomentMocks = () => {
	momentUtil.toMoment.mockImplementation( ( date ) => moment( date ) );
	momentUtil.toDate.mockImplementation( ( m ) => m.toDate() );
	momentUtil.toDatabaseTime.mockImplementation( ( m ) => m.format( 'HH:mm:ss' ) );
	momentUtil.toTime.mockImplementation( ( m ) => m.format( 'HH:mm' ) );
	momentUtil.toFormat.mockImplementation( ( format ) => format );
};

describe( 'RSVP V2 thunks', () => {
	let dispatch;

	beforeEach( () => {
		dispatch = jest.fn();
		apiFetch.mockReset();
		configureMomentMocks();

		window.tribe_editor_config = {
			tickets: {
				rsvpV2: {
					enabled: true,
					ticketsEndpoint: '/tec/v1/tickets',
					ticketType: 'tc-rsvp',
				},
			},
		};

		select.mockReturnValue( { getCurrentPostId: () => 42 } );
	} );

	describe( 'createRSVP', () => {
		it( 'should include IAC in the REST request when provided', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			await createRSVP( {
				...basePayload,
				postId: 42,
				iac: 'required',
			} )( dispatch, getState );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'POST',
					path: '/tec/v1/tickets',
					data: expect.objectContaining( {
						event: 42,
						iac: 'required',
					} ),
				} )
			);
		} );

		it( 'should sync IAC to Redux after a successful create', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			await createRSVP( {
				...basePayload,
				postId: 42,
				iac: 'allowed',
			} )( dispatch, getState );

			expect( dispatch ).toHaveBeenCalledWith( {
				type: types.SET_RSVP_IAC,
				payload: { iac: 'allowed' },
			} );
		} );

		it( 'should omit IAC from the REST request when not provided', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			await createRSVP( {
				...basePayload,
				postId: 42,
			} )( dispatch, getState );

			const { data } = apiFetch.mock.calls[ 0 ][ 0 ];
			expect( data ).not.toHaveProperty( 'iac' );
		} );

		it( 'should default IAC to the global default in Redux when not provided', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			window.tribe_editor_config.ticketsPlus = {
				iacVars: { iacDefault: 'allowed' },
			};

			await createRSVP( {
				...basePayload,
				postId: 42,
			} )( dispatch, getState );

			expect( dispatch ).toHaveBeenCalledWith( {
				type: types.SET_RSVP_IAC,
				payload: { iac: 'allowed' },
			} );
		} );

		it( 'should default IAC to none in Redux when no iacVars are present', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			await createRSVP( {
				...basePayload,
				postId: 42,
			} )( dispatch, getState );

			expect( dispatch ).toHaveBeenCalledWith( {
				type: types.SET_RSVP_IAC,
				payload: { iac: 'none' },
			} );
		} );
	} );

	describe( 'updateRSVP', () => {
		it( 'should include IAC in the REST request when provided', async () => {
			apiFetch.mockResolvedValue( {} );

			await updateRSVP( {
				...basePayload,
				id: 99,
				iac: 'required',
			} )( dispatch, getState );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'PUT',
					path: '/tec/v1/tickets/99',
					data: expect.objectContaining( {
						iac: 'required',
					} ),
				} )
			);
		} );

		it( 'should sync IAC to Redux after a successful update', async () => {
			apiFetch.mockResolvedValue( {} );

			await updateRSVP( {
				...basePayload,
				id: 99,
				iac: 'allowed',
			} )( dispatch, getState );

			expect( dispatch ).toHaveBeenCalledWith( {
				type: types.SET_RSVP_IAC,
				payload: { iac: 'allowed' },
			} );
		} );

		it( 'should omit IAC from the REST request when not provided', async () => {
			apiFetch.mockResolvedValue( {} );

			await updateRSVP( {
				...basePayload,
				id: 99,
			} )( dispatch, getState );

			const { data } = apiFetch.mock.calls[ 0 ][ 0 ];
			expect( data ).not.toHaveProperty( 'iac' );
		} );

		it( 'should default IAC to the global default in Redux when not provided', async () => {
			apiFetch.mockResolvedValue( {} );

			window.tribe_editor_config.ticketsPlus = {
				iacVars: { iacDefault: 'required' },
			};

			await updateRSVP( {
				...basePayload,
				id: 99,
			} )( dispatch, getState );

			expect( dispatch ).toHaveBeenCalledWith( {
				type: types.SET_RSVP_IAC,
				payload: { iac: 'required' },
			} );
		} );
	} );

	describe( 'getRSVP', () => {
		it( 'should hydrate the RSVP from the REST response into Redux', async () => {
			apiFetch.mockResolvedValue( [
				{
					id: 99,
					type: 'tc-rsvp',
					iac: 'required',
					start_date: '2026-06-01 09:00:00',
					end_date: '2026-06-02 17:00:00',
					capacity: 10,
					stock: 10,
					show_not_going: true,
					going_count: 0,
					not_going_count: 0,
					has_attendee_info_fields: false,
				},
			] );

			await getRSVP( 42 )( dispatch );

			expect( dispatch ).toHaveBeenCalledWith( {
				type: types.SET_RSVP_ID,
				payload: { id: 99 },
			} );
		} );
	} );

	describe( 'persistRSVP', () => {
		// Wraps dispatch so thunks dispatched by persistRSVP are actually executed.
		const dispatchThunks = ( getState ) => {
			const dispatch = jest.fn( ( action ) => {
				if ( typeof action === 'function' ) {
					return action( dispatch, getState );
				}
			} );

			return dispatch;
		};

		it( 'should not create the RSVP when there is a duration error (e.g. the event has already started)', async () => {
			const getStateWithDurationError = () => ( {
				tickets: {
					blocks: {
						rsvp: {
							isLoading: false,
							hasDurationError: true,
							created: false,
						},
					},
				},
			} );

			await persistRSVP()( dispatch, getStateWithDurationError );

			expect( apiFetch ).not.toHaveBeenCalled();
			expect( dispatch ).not.toHaveBeenCalled();
		} );

		it( 'should create the RSVP with the current post ID when there is no duration error and the RSVP has not been created yet', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			const getStateWithValidDuration = () => ( {
				tickets: {
					blocks: {
						rsvp: {
							isLoading: false,
							hasDurationError: false,
							created: false,
							details: {},
							tempDetails: {},
						},
					},
				},
			} );
			const thunkDispatch = dispatchThunks( getStateWithValidDuration );

			await persistRSVP()( thunkDispatch, getStateWithValidDuration );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'POST',
					path: '/tec/v1/tickets',
					data: expect.objectContaining( {
						event: 42,
					} ),
				} )
			);
		} );

		it( 'should update the existing RSVP when there is no duration error and the RSVP has already been created', async () => {
			apiFetch.mockResolvedValue( {} );

			const getStateWithCreatedRsvp = () => ( {
				tickets: {
					blocks: {
						rsvp: {
							isLoading: false,
							hasDurationError: false,
							created: true,
							id: 77,
							details: {},
							tempDetails: {},
						},
					},
				},
			} );
			const thunkDispatch = dispatchThunks( getStateWithCreatedRsvp );

			await persistRSVP()( thunkDispatch, getStateWithCreatedRsvp );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'PUT',
					path: '/tec/v1/tickets/77',
				} )
			);
		} );

		it( 'should not persist when the RSVP is marked as created but has no ID yet', async () => {
			const getStateWithoutRsvpId = () => ( {
				tickets: {
					blocks: {
						rsvp: {
							isLoading: false,
							hasDurationError: false,
							created: true,
							id: null,
							details: {},
							tempDetails: {},
						},
					},
				},
			} );

			await persistRSVP()( dispatch, getStateWithoutRsvpId );

			expect( apiFetch ).not.toHaveBeenCalled();
		} );
	} );
} );
