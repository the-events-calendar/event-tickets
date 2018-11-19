/**
 * External dependencies
 */
import { takeEvery, put, all, select, call } from 'redux-saga/effects';
import { cloneableGenerator } from 'redux-saga/utils';

/**
 * Internal Dependencies
 */
import * as constants from '../constants';
import * as types from '../types';
import * as actions from '../actions';
import watchers, * as sagas from '../sagas';
import * as selectors from '../selectors';
import {
	DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE
} from '../reducers/header-image';
import * as utils from '@moderntribe/tickets/data/utils';
import { wpREST } from '@moderntribe/common/utils/api';
import { moment as momentUtil } from '@moderntribe/common/utils';

const {
	INDEPENDENT,
	SHARED,
	TICKET_TYPES,
	PROVIDER_CLASS_TO_PROVIDER_MAPPING,
	WOO_CLASS
} = constants;

jest.mock( '@wordpress/data', () => ( {
	select: ( key ) => {
		if ( key === 'core/editor' ) {
			return {
				getCurrentPostId: () => 10,
				getEditedPostAttribute: ( attr ) => {
					if ( attr === 'date' ) {
						return '2018-11-09T19:48:42';
					}
				},
			};
		}
	},
} ) );

describe( 'Ticket Block sagas', () => {
	describe( 'watchers', () => {
		it( 'should watch actions', () => {
			const gen = watchers();
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_TICKETS_INITIAL_STATE, sagas.setTicketsInitialState )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_TICKET_INITIAL_STATE, sagas.setTicketInitialState )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.FETCH_TICKET, sagas.fetchTicket )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.CREATE_NEW_TICKET, sagas.createNewTicket )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.UPDATE_TICKET, sagas.updateTicket )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.DELETE_TICKET, sagas.deleteTicket )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.FETCH_TICKETS_HEADER_IMAGE, sagas.fetchTicketsHeaderImage )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.UPDATE_TICKETS_HEADER_IMAGE, sagas.updateTicketsHeaderImage )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.DELETE_TICKETS_HEADER_IMAGE, sagas.deleteTicketsHeaderImage )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_TICKET_DETAILS, sagas.setTicketDetails )
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_TICKET_TEMP_DETAILS, sagas.setTicketTempDetails )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setTicketsInitialState', () => {
		it( 'should set tickets initial state', () => {
			const HEADER = 13;
			const SHARED_CAPACITY = '100';
			const PROVIDER = 'woo';
			const action = {
				payload: {
					get: ( key ) => {
						switch ( key ) {
							case 'header':
								return HEADER;
							case 'sharedCapacity':
								return SHARED_CAPACITY;
							case 'provider':
								return PROVIDER;
							default:
								return;
						}
					},
				},
			};

			const gen = sagas.setTicketsInitialState( action );
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setTicketsSharedCapacity( SHARED_CAPACITY ) ),
					put( actions.setTicketsTempSharedCapacity( SHARED_CAPACITY ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				put( actions.fetchTicketsHeaderImage( HEADER ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketsProvider( PROVIDER ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set tickets initial state for new event and no provider', () => {
			const HEADER = 0;
			const SHARED_CAPACITY = '0';
			const PROVIDER = '';
			const action = {
				payload: {
					get: ( key ) => {
						switch ( key ) {
							case 'header':
								return HEADER;
							case 'sharedCapacity':
								return SHARED_CAPACITY;
							case 'provider':
								return PROVIDER;
							default:
								return;
						}
					},
				},
			};
			const gen = sagas.setTicketsInitialState( action );
			expect( gen.next().value ).toEqual(
				put( actions.setTicketsProvider( PROVIDER ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setTicketInitialState', () => {
		let publishDate,
			startMoment,
			startDate,
			startDateInput,
			startTime,
			eventStart,
			endMoment,
			endDate,
			endDateInput,
			endTime;

		beforeEach( () => {
			publishDate = '2018-11-09T19:48:42';
			startMoment = momentUtil.toMoment( publishDate );
			startDate = momentUtil.toDatabaseDate( startMoment );
			startDateInput = momentUtil.toDate( startMoment );
			startTime = momentUtil.toDatabaseTime( startMoment );
			eventStart = 'November 30, 2018 12:30:00';
			endMoment = momentUtil.toMoment( eventStart );
			endDate = momentUtil.toDatabaseDate( endMoment );
			endDateInput = momentUtil.toDate( endMoment );
			endTime = momentUtil.toDatabaseTime( endMoment );
			global.tribe = {
				events: {
					blocks: {
						datetime: {
							selectors: {
								getStart: jest.fn(),
							},
						},
					},
				},
			};
		} );

		afterEach( () => {
			delete global.tribe;
		} );

		it( 'should set tickets initial state', () => {
			const TICKET_ID = 99;
			const CLIENT_ID = 'modern-tribe';
			const action = {
				payload: {
					get: ( key ) => {
						if ( key === 'ticketId' ) {
							return TICKET_ID;
						}
					},
					clientId: CLIENT_ID,
				},
			};

			const gen = cloneableGenerator( sagas.setTicketInitialState )( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, publishDate )
			);
			expect( gen.next( startMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment )
			);
			expect( gen.next( startDate ).value ).toEqual(
				call( momentUtil.toDate, startMoment )
			);
			expect( gen.next( startDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment )
			);
			expect( gen.next( startTime ).value ).toEqual(
				all( [
					put( actions.setTicketStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketTempStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketTempStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketTempStartTime( action.payload.clientId, startTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( global.tribe.events.blocks.datetime.selectors.getStart )
			)
			expect( gen.next( eventStart ).value ).toEqual(
				call( momentUtil.toMoment, eventStart )
			);
			expect( gen.next( endMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment )
			);
			expect( gen.next( endDate ).value ).toEqual(
				call( momentUtil.toDate, endMoment )
			);
			expect( gen.next( endDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment )
			);
			expect( gen.next( endTime ).value ).toEqual(
				all( [
					put( actions.setTicketEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketTempEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketTempEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketTempEndTime( action.payload.clientId, endTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsSharedCapacity )
			);

			const clone1 = gen.clone();
			const blankSharedCapacity = '';

			expect( clone1.next( blankSharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketId( CLIENT_ID, TICKET_ID ) ),
					put( actions.fetchTicket( CLIENT_ID, TICKET_ID ) ),
				] )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const sharedCapacity = '100';

			expect( clone2.next( sharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketCapacity( CLIENT_ID, sharedCapacity ) ),
					put( actions.setTicketTempCapacity( CLIENT_ID, sharedCapacity ) ),
				] )
			);
			expect( clone2.next().value ).toEqual(
				all( [
					put( actions.setTicketId( CLIENT_ID, TICKET_ID ) ),
					put( actions.fetchTicket( CLIENT_ID, TICKET_ID ) ),
				] )
			);
			expect( clone2.next().done ).toEqual( true );
		} );

		it( 'should set tickets initial state for new ticket', () => {
			const TICKET_ID = 0;
			const CLIENT_ID = 'modern-tribe';
			const action = {
				payload: {
					get: ( key ) => {
						if ( key === 'ticketId' ) {
							return TICKET_ID;
						}
					},
					clientId: CLIENT_ID,
				},
			};
			global.tribe.events.blocks.datetime.selectors.getStart = jest.fn();

			const gen = cloneableGenerator( sagas.setTicketInitialState )( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, publishDate )
			);
			expect( gen.next( startMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment )
			);
			expect( gen.next( startDate ).value ).toEqual(
				call( momentUtil.toDate, startMoment )
			);
			expect( gen.next( startDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment )
			);
			expect( gen.next( startTime ).value ).toEqual(
				all( [
					put( actions.setTicketStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketTempStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketTempStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketTempStartTime( action.payload.clientId, startTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( global.tribe.events.blocks.datetime.selectors.getStart )
			)
			expect( gen.next( eventStart ).value ).toEqual(
				call( momentUtil.toMoment, eventStart )
			);
			expect( gen.next( endMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment )
			);
			expect( gen.next( endDate ).value ).toEqual(
				call( momentUtil.toDate, endMoment )
			);
			expect( gen.next( endDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment )
			);
			expect( gen.next( endTime ).value ).toEqual(
				all( [
					put( actions.setTicketEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketTempEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketTempEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketTempEndTime( action.payload.clientId, endTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsSharedCapacity )
			);

			const clone1 = gen.clone();
			const blankSharedCapacity = '';

			expect( clone1.next( blankSharedCapacity ).done ).toEqual( true );

			const clone2 = gen.clone();
			const sharedCapacity = '100';

			expect( clone2.next( sharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketCapacity( CLIENT_ID, sharedCapacity ) ),
					put( actions.setTicketTempCapacity( CLIENT_ID, sharedCapacity ) ),
				] )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'setBodyDetails', () => {
		it( 'should set body details', () => {
			const blockId = 'modern-tribe';
			const props = { blockId };
			const gen = cloneableGenerator( sagas.setBodyDetails )( blockId );

			expect( gen.next().value ).toEqual(
				select( selectors.getTicketProvider, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsProvider )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempTitle, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempDescription, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempPrice, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempStartDate, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempStartTime, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempEndDate, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempEndTime, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempSku, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempCapacityType, props )
			);

			const clone1 = gen.clone();
			const sharedCapacityType = TICKET_TYPES[ SHARED ];

			expect( clone1.next( sharedCapacityType ).value ).toEqual(
				select( selectors.getTicketTempCapacity, props )
			);
			expect( clone1.next().value ).toEqual(
				select( selectors.getTicketsTempSharedCapacity )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const independentCapacityType = TICKET_TYPES[ INDEPENDENT ];

			expect( clone2.next( independentCapacityType ).value ).toEqual(
				select( selectors.getTicketTempCapacity, props )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'fetchTicket', () => {
		it( 'should fetch ticket', () => {
			const TICKET_ID = 13;
			const BLOCK_ID = 'modern-tribe';
			const action = {
				payload: {
					ticketId: TICKET_ID,
					blockId: BLOCK_ID,
				},
			};

			const gen = cloneableGenerator( sagas.fetchTicket )( action );
			expect( gen.next().value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tickets/${ TICKET_ID }`,
					namespace: 'tribe/tickets/v1',
				} )
			);

			const clone1 = gen.clone();
			const apiResponse1 = {
				response: {
					ok: false,
				},
				data: {},
			};

			expect( clone1.next( apiResponse1 ).value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponse2 = {
				response: {
					ok: true,
				},
				data: {
					cost_details: {
						values: [ 20 ],
					},
					totals: {
						sold: 10,
						stock: 45,
					},
					available_from: '2018-11-09 19:48:42',
					available_until: '',
					provider: 'woo',
					title: 'title',
					description: 'description',
					sku: '12345678',
					capacity_type: 'own',
					capacity: 100,
				},
			};
			const startMoment2 = momentUtil.toMoment( apiResponse2.data.available_from );
			const startDate2 = momentUtil.toDatabaseDate( startMoment2 );
			const startDateInput2 = momentUtil.toDate( startMoment2 );
			const startTime2 = momentUtil.toDatabaseTime( startMoment2 );
			const endMoment2 = momentUtil.toMoment( '' );
			const endDate2 = '';
			const endDateInput2 = '';
			const endTime2 = '';

			expect( clone2.next( apiResponse2 ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse2.data.available_from )
			);
			expect( clone2.next( startMoment2 ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment2 )
			);
			expect( clone2.next( startDate2 ).value ).toEqual(
				call( momentUtil.toDate, startMoment2 )
			);
			expect( clone2.next( startDateInput2 ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment2 )
			);
			expect( clone2.next( startTime2 ).value ).toEqual(
				call( momentUtil.toMoment, '' )
			);

			const details2 = {
				title: apiResponse2.data.title,
				description: apiResponse2.data.description,
				price: apiResponse2.data.cost_details.values[ 0 ],
				sku: apiResponse2.data.sku,
				startDate: startDate2,
				startDateInput: startDateInput2,
				startDateMoment: startMoment2,
				endDate: endDate2,
				endDateInput: endDateInput2,
				endDateMoment: endMoment2,
				startTime: startTime2,
				endTime: endTime2,
				capacityType: apiResponse2.data.capacity_type,
				capacity: apiResponse2.data.capacity,
			};

			expect( clone2.next( endMoment2 ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( BLOCK_ID, details2 ) ),
					put( actions.setTicketTempDetails( BLOCK_ID, details2 ) ),
					put( actions.setTicketSold( BLOCK_ID, apiResponse2.data.totals.sold ) ),
					put( actions.setTicketAvailable( BLOCK_ID, apiResponse2.data.totals.stock ) ),
					put( actions.setTicketCurrencySymbol( BLOCK_ID, apiResponse2.data.cost_details.currency_symbol ) ),
					put( actions.setTicketCurrencyPosition( BLOCK_ID, apiResponse2.data.cost_details.currency_position ) ),
					put( actions.setTicketProvider( BLOCK_ID, apiResponse2.data.provider ) ),
					put( actions.setTicketHasBeenCreated( BLOCK_ID, true ) ),
				] )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone2.next().done ).toEqual( true );

			const clone3 = gen.clone();
			const apiResponse3 = {
				response: {
					ok: true,
				},
				data: {
					cost_details: {
						values: [ 20 ],
					},
					totals: {
						sold: 10,
						stock: 45,
					},
					available_from: '2018-11-09 19:48:42',
					available_until: '2018-11-12 19:48:42',
					provider: 'woo',
					title: 'title',
					description: 'description',
					sku: '12345678',
					capacity_type: 'own',
					capacity: 100,
				},
			};

			const startMoment3 = momentUtil.toMoment( apiResponse3.data.available_from );
			const startDate3 = momentUtil.toDatabaseDate( startMoment3 );
			const startDateInput3 = momentUtil.toDate( startMoment3 );
			const startTime3 = momentUtil.toDatabaseTime( startMoment3 );
			const endMoment3 = momentUtil.toMoment( apiResponse3.data.available_until );
			const endDate3 = momentUtil.toDatabaseDate( endMoment3 );
			const endDateInput3 = momentUtil.toDate( endMoment3 );
			const endTime3 = momentUtil.toDatabaseTime( endMoment3 );

			expect( clone3.next( apiResponse3 ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse3.data.available_from )
			);
			expect( clone3.next( startMoment3 ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment3 )
			);
			expect( clone3.next( startDate3 ).value ).toEqual(
				call( momentUtil.toDate, startMoment3 )
			);
			expect( clone3.next( startDateInput3 ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment3 )
			);
			expect( clone3.next( startTime3 ).value ).toEqual(
				call( momentUtil.toMoment, '' )
			);
			expect( clone3.next( endMoment2 ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse3.data.available_until )
			);
			expect( clone3.next( endMoment3 ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment3 )
			);
			expect( clone3.next( endDate3 ).value ).toEqual(
				call( momentUtil.toDate, endMoment3 )
			);
			expect( clone3.next( endDateInput3 ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment3 )
			);

			const details3 = {
				title: apiResponse3.data.title,
				description: apiResponse3.data.description,
				price: apiResponse3.data.cost_details.values[ 0 ],
				sku: apiResponse3.data.sku,
				startDate: startDate3,
				startDateInput: startDateInput3,
				startDateMoment: startMoment3,
				endDate: endDate3,
				endDateInput: endDateInput3,
				endDateMoment: endMoment3,
				startTime: startTime3,
				endTime: endTime3,
				capacityType: apiResponse3.data.capacity_type,
				capacity: apiResponse3.data.capacity,
			};

			expect( clone3.next( startTime3 ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( BLOCK_ID, details3 ) ),
					put( actions.setTicketTempDetails( BLOCK_ID, details3 ) ),
					put( actions.setTicketSold( BLOCK_ID, apiResponse3.data.totals.sold ) ),
					put( actions.setTicketAvailable( BLOCK_ID, apiResponse3.data.totals.stock ) ),
					put( actions.setTicketCurrencySymbol( BLOCK_ID, apiResponse3.data.cost_details.currency_symbol ) ),
					put( actions.setTicketCurrencyPosition( BLOCK_ID, apiResponse3.data.cost_details.currency_position ) ),
					put( actions.setTicketProvider( BLOCK_ID, apiResponse3.data.provider ) ),
					put( actions.setTicketHasBeenCreated( BLOCK_ID, true ) ),
				] )
			);
			expect( clone3.next().value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone3.next().done ).toEqual( true );
		} );

		it( 'should not fetch ticket if new ticket', () => {
			const TICKET_ID = 0;
			const BLOCK_ID = 'modern-tribe';
			const action = {
				payload: {
					ticketId: TICKET_ID,
					blockId: BLOCK_ID,
				},
			};

			const gen = sagas.fetchTicket( action );
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'createNewTicket', () => {
		it( 'should create new ticket', () => {
			const title = 'title';
			const description = 'description';
			const price = 10;
			const sku = '12345678';
			const startDate = '2018-11-09 19:48:42';
			const startDateInput = '2018-11-09 19:48:42';
			const startDateMoment = '2018-11-09 19:48:42';
			const endDate = '2018-11-09 19:48:42';
			const endDateInput = '2018-11-09 19:48:42';
			const endDateMoment = '2018-11-09 19:48:42';
			const startTime = '19:48:42';
			const endTime = '19:48:42';
			const capacityType = 'own';
			const capacity = 100;

			const BLOCK_ID = 'modern-tribe';
			const props = { blockId: BLOCK_ID };
			const action = {
				payload: {
					blockId: BLOCK_ID,
				},
			};

			const gen = cloneableGenerator( sagas.createNewTicket )( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setBodyDetails, BLOCK_ID )
			);

			const body = new FormData();

			expect( gen.next( body ).value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: 'tickets/',
					namespace: 'tribe/tickets/v1',
					initParams: {
						method: 'POST',
						body,
					},
				} )
			);

			const clone1 = gen.clone();
			const apiResponse1 = {
				response: {
					ok: true,
				},
				data: {
					ID: 13,
					capacity: 100,
					provider_class: WOO_CLASS,
				},
			};

			expect( clone1.next( apiResponse1 ).value ).toEqual(
				select( selectors.getTicketsSharedCapacity )
			);

			const clone11 = clone1.clone();
			const sharedCapacity11 = '';
			const tempSharedCapacity11 = 100;

			expect( clone11.next( sharedCapacity11 ).value ).toEqual(
				select( selectors.getTicketsTempSharedCapacity )
			);
			expect( clone11.next( tempSharedCapacity11 ).value ).toEqual(
				put( actions.setTicketsSharedCapacity( tempSharedCapacity11 ) )
			);
			expect( clone11.next().value ).toEqual(
				all( [
					select( selectors.getTicketTempTitle, props ),
					select( selectors.getTicketTempDescription, props ),
					select( selectors.getTicketTempPrice, props ),
					select( selectors.getTicketTempSku, props ),
					select( selectors.getTicketTempStartDate, props ),
					select( selectors.getTicketTempStartDateInput, props ),
					select( selectors.getTicketTempStartDateMoment, props ),
					select( selectors.getTicketTempEndDate, props ),
					select( selectors.getTicketTempEndDateInput, props ),
					select( selectors.getTicketTempEndDateMoment, props ),
					select( selectors.getTicketTempStartTime, props ),
					select( selectors.getTicketTempEndTime, props ),
					select( selectors.getTicketTempCapacityType, props ),
					select( selectors.getTicketTempCapacity, props ),
				] )
			);

			expect( clone11.next( [
				title,
				description,
				price,
				sku,
				startDate,
				startDateInput,
				startDateMoment,
				endDate,
				endDateInput,
				endDateMoment,
				startTime,
				endTime,
				capacityType,
				capacity,
			] ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( BLOCK_ID, {
						title,
						description,
						price,
						sku,
						startDate,
						startDateInput,
						startDateMoment,
						endDate,
						endDateInput,
						endDateMoment,
						startTime,
						endTime,
						capacityType,
						capacity,
					} ) ),
					put( actions.setTicketId( BLOCK_ID, apiResponse1.data.ID ) ),
					put( actions.setTicketHasBeenCreated( BLOCK_ID, true ) ),
					put( actions.setTicketAvailable( BLOCK_ID, apiResponse1.data.capacity ) ),
					put( actions.setTicketProvider(
						BLOCK_ID,
						PROVIDER_CLASS_TO_PROVIDER_MAPPING[ apiResponse1.data.provider_class ],
					) ),
					put( actions.setTicketHasChanges( BLOCK_ID, false ) ),
				] )
			);
			expect( clone11.next().value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone11.next().done ).toEqual( true );

			const clone12 = clone1.clone();
			const sharedCapacity12 = 100;
			const tempSharedCapacity12 = 100;

			expect( clone12.next( sharedCapacity12 ).value ).toEqual(
				select( selectors.getTicketsTempSharedCapacity )
			);
			expect( clone12.next( tempSharedCapacity12 ).value ).toEqual(
				all( [
					select( selectors.getTicketTempTitle, props ),
					select( selectors.getTicketTempDescription, props ),
					select( selectors.getTicketTempPrice, props ),
					select( selectors.getTicketTempSku, props ),
					select( selectors.getTicketTempStartDate, props ),
					select( selectors.getTicketTempStartDateInput, props ),
					select( selectors.getTicketTempStartDateMoment, props ),
					select( selectors.getTicketTempEndDate, props ),
					select( selectors.getTicketTempEndDateInput, props ),
					select( selectors.getTicketTempEndDateMoment, props ),
					select( selectors.getTicketTempStartTime, props ),
					select( selectors.getTicketTempEndTime, props ),
					select( selectors.getTicketTempCapacityType, props ),
					select( selectors.getTicketTempCapacity, props ),
				] )
			);

			expect( clone12.next( [
				title,
				description,
				price,
				sku,
				startDate,
				startDateInput,
				startDateMoment,
				endDate,
				endDateInput,
				endDateMoment,
				startTime,
				endTime,
				capacityType,
				capacity,
			] ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( BLOCK_ID, {
						title,
						description,
						price,
						sku,
						startDate,
						startDateInput,
						startDateMoment,
						endDate,
						endDateInput,
						endDateMoment,
						startTime,
						endTime,
						capacityType,
						capacity,
					} ) ),
					put( actions.setTicketId( BLOCK_ID, apiResponse1.data.ID ) ),
					put( actions.setTicketHasBeenCreated( BLOCK_ID, true ) ),
					put( actions.setTicketAvailable( BLOCK_ID, apiResponse1.data.capacity ) ),
					put( actions.setTicketProvider(
						BLOCK_ID,
						PROVIDER_CLASS_TO_PROVIDER_MAPPING[ apiResponse1.data.provider_class ],
					) ),
					put( actions.setTicketHasChanges( BLOCK_ID, false ) ),
				] )
			);
			expect( clone12.next().value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone12.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponse2 = {
				response: {
					ok: false,
				},
			};

			expect( clone2.next( apiResponse2 ).value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'updateTicket', () => {
		it( 'should update ticket', () => {
			const title = 'title';
			const description = 'description';
			const price = 10;
			const sku = '12345678';
			const startDate = '2018-11-09 19:48:42';
			const startDateInput = '2018-11-09 19:48:42';
			const startDateMoment = '2018-11-09 19:48:42';
			const endDate = '2018-11-09 19:48:42';
			const endDateInput = '2018-11-09 19:48:42';
			const endDateMoment = '2018-11-09 19:48:42';
			const startTime = '19:48:42';
			const endTime = '19:48:42';
			const capacityType = 'own';
			const capacity = 100;

			const TICKET_ID = 13;
			const BLOCK_ID = 'modern-tribe';
			const props = { blockId: BLOCK_ID };
			const action = {
				payload: {
					blockId: BLOCK_ID,
				},
			};

			const gen = cloneableGenerator( sagas.updateTicket )( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setBodyDetails, BLOCK_ID )
			);

			const body = new FormData();

			expect( gen.next( body ).value ).toEqual(
				select( selectors.getTicketId, props )
			);
			expect( gen.next( TICKET_ID ).value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, true ) )
			);

			const data = [];
			for ( const pair of body.entries() ) {
				data.push( `${ encodeURIComponent( pair[ 0 ] ) }=${ encodeURIComponent( pair[ 1 ] ) }` );
			}

			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tickets/${ TICKET_ID }`,
					namespace: 'tribe/tickets/v1',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
					},
					initParams: {
						method: 'PUT',
						body: data.join( '&' ),
					},
				} )
			);

			const clone1 = gen.clone();
			const apiResponse1 = {
				response: {
					ok: false,
				},
			};

			expect( clone1.next( apiResponse1 ).value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponse2 = {
				response: {
					ok: true,
				},
			};

			expect( clone2.next( apiResponse2 ).value ).toEqual(
				all( [
					select( selectors.getTicketTempTitle, props ),
					select( selectors.getTicketTempDescription, props ),
					select( selectors.getTicketTempPrice, props ),
					select( selectors.getTicketTempSku, props ),
					select( selectors.getTicketTempStartDate, props ),
					select( selectors.getTicketTempStartDateInput, props ),
					select( selectors.getTicketTempStartDateMoment, props ),
					select( selectors.getTicketTempEndDate, props ),
					select( selectors.getTicketTempEndDateInput, props ),
					select( selectors.getTicketTempEndDateMoment, props ),
					select( selectors.getTicketTempStartTime, props ),
					select( selectors.getTicketTempEndTime, props ),
					select( selectors.getTicketTempCapacityType, props ),
					select( selectors.getTicketTempCapacity, props ),
				] )
			);
			expect( clone2.next( [
				title,
				description,
				price,
				sku,
				startDate,
				startDateInput,
				startDateMoment,
				endDate,
				endDateInput,
				endDateMoment,
				startTime,
				endTime,
				capacityType,
				capacity,
			] ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( BLOCK_ID, {
						title,
						description,
						price,
						sku,
						startDate,
						startDateInput,
						startDateMoment,
						endDate,
						endDateInput,
						endDateMoment,
						startTime,
						endTime,
						capacityType,
						capacity,
					} ) ),
					put( actions.setTicketHasChanges( BLOCK_ID, false ) ),
				] )
			);
			expect( clone2.next( apiResponse1 ).value ).toEqual(
				put( actions.setTicketIsLoading( BLOCK_ID, false ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'deleteTicket', () => {
		it( 'should delete ticket', () => {
			const TICKET_ID = 13;
			const BLOCK_ID = 'modern-tribe';
			const props = { blockId: BLOCK_ID };
			const action = {
				payload: {
					blockId: BLOCK_ID,
				},
			};

			const gen = cloneableGenerator( sagas.deleteTicket )( action );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketId, props )
			);
			expect( gen.next( TICKET_ID ).value ).toEqual(
				select( selectors.getTicketHasBeenCreated, props )
			);

			const clone1 = gen.clone();
			const hasBeenCreated1 = false;

			expect( clone1.next( hasBeenCreated1 ).value ).toEqual(
				put( actions.setTicketIsSelected( BLOCK_ID, false ) )
			);
			expect( clone1.next().value ).toEqual(
				put( actions.removeTicketBlock( BLOCK_ID ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const hasBeenCreated2 = true;
			const body = [
				`${ encodeURIComponent( 'post_id' ) }=${ encodeURIComponent( 10 ) }`,
				`${ encodeURIComponent( 'remove_ticket_nonce' ) }=${ encodeURIComponent( '' ) }`,
			];

			expect( clone2.next( hasBeenCreated2 ).value ).toEqual(
				put( actions.setTicketIsSelected( BLOCK_ID, false ) )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.removeTicketBlock( BLOCK_ID ) )
			);
			expect( clone2.next().value ).toEqual(
				call( wpREST, {
					path: `tickets/${ TICKET_ID }`,
					namespace: 'tribe/tickets/v1',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
					},
					initParams: {
						method: 'DELETE',
						body: body.join( '&' ),
					},
				} )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'fetchTicketsHeaderImage', () => {
		it( 'should fetch tickets header image', () => {
			const action = {
				payload: {
					id: 99,
				},
			};
			const gen = cloneableGenerator( sagas.fetchTicketsHeaderImage )( action );

			expect( gen.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, { path: `media/${ action.payload.id }` } )
			);

			const clone1 = gen.clone();
			const apiResponseBad = {
				response: {
					ok: false,
				},
				data: {},
			};

			expect( clone1.next( apiResponseBad ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponseGood = {
				response: {
					ok: true,
				},
				data: {
					id: 99,
					alt_text: 'tribe',
					media_details: {
						sizes: {
							medium: {
								source_url: '#',
							},
						},
					},
				},
			};

			expect( clone2.next( apiResponseGood ).value ).toEqual(
				put( actions.setTicketsHeaderImage( {
					id: apiResponseGood.data.id,
					alt: apiResponseGood.data.alt_text,
					src: apiResponseGood.data.media_details.sizes.medium.source_url,
				} ) )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'updateTicketsHeaderImage', () => {
		it( 'should update tickets header image', () => {
			const action = {
				payload: {
					image: {
						id: 99,
						alt: 'tribe',
						sizes: {
							medium: {
								url: '#',
							},
						},
					},
				},
			};
			const gen = cloneableGenerator( sagas.updateTicketsHeaderImage )( action );

			expect( gen.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tribe_events/${ 10 }`,
					headers: {
						'Content-Type': 'application/json',
					},
					initParams: {
						method: 'PUT',
						body: JSON.stringify( {
							meta: {
								[ utils.KEY_TICKET_HEADER ]: `${ action.payload.image.id }`,
							},
						} ),
					},
				} )
			);

			const clone1 = gen.clone();
			const apiResponseBad = {
				response: {
					ok: false,
				},
			};

			expect( clone1.next( apiResponseBad ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponseGood = {
				response: {
					ok: true,
				},
			};

			expect( clone2.next( apiResponseGood ).value ).toEqual(
				put( actions.setTicketsHeaderImage( {
					id: action.payload.image.id,
					alt: action.payload.image.alt,
					src: action.payload.image.sizes.medium.url,
				} ) )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'deleteTicketsHeaderImage', () => {
		it( 'should delete tickets header image', () => {
			const gen = cloneableGenerator( sagas.deleteTicketsHeaderImage )();
			expect( gen.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tribe_events/${ 10 }`,
					headers: {
						'Content-Type': 'application/json',
					},
					initParams: {
						method: 'PUT',
						body: JSON.stringify( {
							meta: {
								[ utils.KEY_TICKET_HEADER ]: null,
							},
						} ),
					},
				} )
			);

			const clone1 = gen.clone();
			const apiResponseBad = {
				response: {
					ok: false,
				},
			};

			expect( clone1.next( apiResponseBad ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponseGood = {
				response: {
					ok: true,
				},
			};

			expect( clone2.next( apiResponseGood ).value ).toEqual(
				put( actions.setTicketsHeaderImage( HEADER_IMAGE_DEFAULT_STATE ) )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'setTicketDetails', () => {
		const title = 'title';
		const description = 'description';
		const price = 10;
		const sku = '12345678';
		const startDate = '2018-11-09 19:48:42';
		const startDateInput = '2018-11-09 19:48:42';
		const startDateMoment = '2018-11-09 19:48:42';
		const endDate = '2018-11-09 19:48:42';
		const endDateInput = '2018-11-09 19:48:42';
		const endDateMoment = '2018-11-09 19:48:42';
		const startTime = '19:48:42';
		const endTime = '19:48:42';
		const capacityType = 'own';
		const capacity = 100;

		const BLOCK_ID = 'modern-tribe';
		const action = {
			payload: {
				blockId: BLOCK_ID,
				details: {
					title,
					description,
					price,
					sku,
					startDate,
					startDateInput,
					startDateMoment,
					endDate,
					endDateInput,
					endDateMoment,
					startTime,
					endTime,
					capacityType,
					capacity,
				},
			},
		};

		const gen = sagas.setTicketDetails( action );
		expect( gen.next().value ).toEqual(
			all( [
				put( actions.setTicketTitle( BLOCK_ID, title ) ),
				put( actions.setTicketDescription( BLOCK_ID, description ) ),
				put( actions.setTicketPrice( BLOCK_ID, price ) ),
				put( actions.setTicketSku( BLOCK_ID, sku ) ),
				put( actions.setTicketStartDate( BLOCK_ID, startDate ) ),
				put( actions.setTicketStartDateInput( BLOCK_ID, startDateInput ) ),
				put( actions.setTicketStartDateMoment( BLOCK_ID, startDateMoment ) ),
				put( actions.setTicketEndDate( BLOCK_ID, endDate ) ),
				put( actions.setTicketEndDateInput( BLOCK_ID, endDateInput ) ),
				put( actions.setTicketEndDateMoment( BLOCK_ID, endDateMoment ) ),
				put( actions.setTicketStartTime( BLOCK_ID, startTime ) ),
				put( actions.setTicketEndTime( BLOCK_ID, endTime ) ),
				put( actions.setTicketCapacityType( BLOCK_ID, capacityType ) ),
				put( actions.setTicketCapacity( BLOCK_ID, capacity ) ),
			] )
		);
		expect( gen.next().done ).toEqual( true );
	} );

	describe( 'setTicketTempDetails', () => {
		const title = 'title';
		const description = 'description';
		const price = 10;
		const sku = '12345678';
		const startDate = '2018-11-09 19:48:42';
		const startDateInput = '2018-11-09 19:48:42';
		const startDateMoment = '2018-11-09 19:48:42';
		const endDate = '2018-11-09 19:48:42';
		const endDateInput = '2018-11-09 19:48:42';
		const endDateMoment = '2018-11-09 19:48:42';
		const startTime = '19:48:42';
		const endTime = '19:48:42';
		const capacityType = 'own';
		const capacity = 100;

		const BLOCK_ID = 'modern-tribe';
		const action = {
			payload: {
				blockId: BLOCK_ID,
				tempDetails: {
					title,
					description,
					price,
					sku,
					startDate,
					startDateInput,
					startDateMoment,
					endDate,
					endDateInput,
					endDateMoment,
					startTime,
					endTime,
					capacityType,
					capacity,
				},
			},
		};

		const gen = sagas.setTicketTempDetails( action );
		expect( gen.next().value ).toEqual(
			all( [
				put( actions.setTicketTempTitle( BLOCK_ID, title ) ),
				put( actions.setTicketTempDescription( BLOCK_ID, description ) ),
				put( actions.setTicketTempPrice( BLOCK_ID, price ) ),
				put( actions.setTicketTempSku( BLOCK_ID, sku ) ),
				put( actions.setTicketTempStartDate( BLOCK_ID, startDate ) ),
				put( actions.setTicketTempStartDateInput( BLOCK_ID, startDateInput ) ),
				put( actions.setTicketTempStartDateMoment( BLOCK_ID, startDateMoment ) ),
				put( actions.setTicketTempEndDate( BLOCK_ID, endDate ) ),
				put( actions.setTicketTempEndDateInput( BLOCK_ID, endDateInput ) ),
				put( actions.setTicketTempEndDateMoment( BLOCK_ID, endDateMoment ) ),
				put( actions.setTicketTempStartTime( BLOCK_ID, startTime ) ),
				put( actions.setTicketTempEndTime( BLOCK_ID, endTime ) ),
				put( actions.setTicketTempCapacityType( BLOCK_ID, capacityType ) ),
				put( actions.setTicketTempCapacity( BLOCK_ID, capacity ) ),
			] )
		);
		expect( gen.next().done ).toEqual( true );
	} );
} );
