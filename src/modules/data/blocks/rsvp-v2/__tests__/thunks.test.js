/**
 * External dependencies
 */
import moment from 'moment';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { createRSVP, getRSVP, updateRSVP } from '../thunks';
import { types } from '../../rsvp';
import { moment as momentUtil } from '@moderntribe/common/utils';

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
	} );

	describe( 'createRSVP', () => {
		it( 'should include IAC in the REST request when provided', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			await createRSVP( {
				...basePayload,
				postId: 42,
				iac: 'required',
			} )( dispatch );

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
			} )( dispatch );

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
			} )( dispatch );

			const { data } = apiFetch.mock.calls[ 0 ][ 0 ];
			expect( data ).not.toHaveProperty( 'iac' );
		} );

		it( 'should default IAC to none in Redux when not provided', async () => {
			apiFetch.mockResolvedValue( { id: 99 } );

			await createRSVP( {
				...basePayload,
				postId: 42,
			} )( dispatch );

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
			} )( dispatch );

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
			} )( dispatch );

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
			} )( dispatch );

			const { data } = apiFetch.mock.calls[ 0 ][ 0 ];
			expect( data ).not.toHaveProperty( 'iac' );
		} );
	} );

	describe( 'getRSVP', () => {
		it( 'should sync IAC from the REST response into Redux', async () => {
			apiFetch.mockResolvedValue( [
				{
					id: 99,
					type: 'tc-rsvp',
					iac: 'required',
					start_date: '2026-06-01 09:00:00',
					end_date: '2026-06-02 17:00:00',
					capacity: 10,
					show_not_going: true,
					going_count: 0,
					not_going_count: 0,
					has_attendee_info_fields: false,
				},
			] );

			await getRSVP( 42 )( dispatch );

			expect( dispatch ).toHaveBeenCalledWith( {
				type: types.SET_RSVP_IAC,
				payload: { iac: 'required' },
			} );
		} );
	} );
} );
