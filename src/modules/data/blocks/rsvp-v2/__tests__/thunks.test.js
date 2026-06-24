/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import * as actions from '../../rsvp/actions';
import { createRSVP, updateRSVP } from '../thunks';

jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );
jest.mock( '@wordpress/hooks', () => ( {
	doAction: jest.fn(),
} ), { virtual: true } );

jest.mock( '../config', () => ( {
	getV2Config: () => ( {
		ticketsEndpoint: '/tec/v1/tickets',
		ticketType: 'tc-rsvp',
	} ),
} ) );

const createMoment = ( dateStr, timeStr ) => ( {
	isValid: () => true,
	format: () => dateStr,
	clone: () => createMoment( dateStr, timeStr ),
	startOf: () => createMoment( dateStr, timeStr ),
	seconds: () => createMoment( dateStr, timeStr ),
} );

describe( 'rsvp-v2 thunks', () => {
	let dispatch;

	beforeEach( () => {
		dispatch = jest.fn();
		jest.clearAllMocks();
	} );

	describe( 'createRSVP', () => {
		it( 'keeps add/edit open on success', async () => {
			apiFetch.mockResolvedValue( { id: 42 } );

			const payload = {
				capacity: '10',
				notGoingResponses: false,
				startDateMoment: createMoment( '2026-03-05', '00:00:00' ),
				startTime: '00:00:00',
				endDateMoment: createMoment( '2026-03-25', '00:00:00' ),
				endTime: '00:00:00',
				postId: 1,
			};

			await createRSVP( payload )( dispatch, () => ( { tickets: { blocks: { rsvp: { isLoading: false } } } } ) );

			expect( dispatch ).not.toHaveBeenCalledWith( actions.setRSVPIsAddEditOpen( false ) );
			expect( dispatch ).toHaveBeenCalledWith( actions.createRSVP() );
		} );
	} );

	describe( 'updateRSVP', () => {
		it( 'sends unlimited stock_mode when capacity is blank', async () => {
			apiFetch.mockResolvedValue( {} );

			const payload = {
				id: 42,
				capacity: '',
				notGoingResponses: false,
				startDateMoment: createMoment( '2026-03-05', '00:00:00' ),
				startTime: '00:00:00',
				endDateMoment: createMoment( '2026-03-25', '00:00:00' ),
				endTime: '00:00:00',
				startDate: '2026-03-05',
				startDateInput: '3/5/26',
				endDate: '2026-03-25',
				endDateInput: '3/25/26',
				startTimeInput: '12:00 am',
				endTimeInput: '12:00 am',
			};

			await updateRSVP( payload )( dispatch );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					data: expect.objectContaining( {
						stock_mode: 'unlimited',
					} ),
				} )
			);
			expect( apiFetch.mock.calls[ 0 ][ 0 ].data ).not.toHaveProperty( 'capacity' );
		} );

		it( 'sends own stock_mode and capacity when limit is set', async () => {
			apiFetch.mockResolvedValue( {} );

			const payload = {
				id: 42,
				capacity: '50',
				notGoingResponses: true,
				startDateMoment: createMoment( '2026-03-05', '00:00:00' ),
				startTime: '00:00:00',
				endDateMoment: createMoment( '2026-03-25', '00:00:00' ),
				endTime: '00:00:00',
				startDate: '2026-03-05',
				startDateInput: '3/5/26',
				endDate: '2026-03-25',
				endDateInput: '3/25/26',
				startTimeInput: '12:00 am',
				endTimeInput: '12:00 am',
			};

			await updateRSVP( payload )( dispatch );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					data: expect.objectContaining( {
						stock_mode: 'own',
						capacity: 50,
						show_not_going: true,
					} ),
				} )
			);
		} );
	} );
} );
