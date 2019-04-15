/**
 * External dependencies
 */
import { takeEvery, put, all, select, call, take, fork } from 'redux-saga/effects';
import { cloneableGenerator } from 'redux-saga/utils';

/**
 * WordPress dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';

/**
 * Internal Dependencies
 */
import * as constants from '../constants';
import * as types from '../types';
import * as actions from '../actions';
import watchers, * as sagas from '../sagas';
import * as selectors from '../selectors';
import {
	DEFAULT_STATE as TICKET_HEADER_IMAGE_DEFAULT_STATE
} from '../reducers/header-image';
import * as rsvpActions from '@moderntribe/tickets/data/blocks/rsvp/actions';
import {
	DEFAULT_STATE as RSVP_HEADER_IMAGE_DEFAULT_STATE
} from '@moderntribe/tickets/data/blocks/rsvp/reducers/header-image';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';
import * as utils from '@moderntribe/tickets/data/utils';
import { wpREST } from '@moderntribe/common/utils/api';
import {
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';
import { plugins } from '@moderntribe/common/data';
import {
	isTribeEventPostType,
	createWPEditorSavingChannel,
	createWPEditorNotSavingChannel,
	hasPostTypeChannel,
	createDates,
} from '@moderntribe/tickets/data/shared/sagas';

const {
	INDEPENDENT,
	SHARED,
	TICKET_TYPES,
	PROVIDER_CLASS_TO_PROVIDER_MAPPING,
	WOO_CLASS
} = constants;

jest.mock( '@wordpress/data', () => {
	return {
		select: ( key ) => {
			if ( key === 'core/editor' ) {
				return {
					getBlockCount: () => {},
					getBlockIndex: () => 0,
					getBlockRootClientId: () => 88,
					getBlocks: () => {},
					getCurrentPostId: () => 10,
					getCurrentPostAttribute: () => {},
					getEditedPostAttribute: ( attr ) => {
						if ( attr === 'date' ) {
							return '2018-11-09T19:48:42';
						}
					},
				};
			}
		},
		dispatch: () => ( {
			editPost: () => {},
			insertBlocks: () => {},
			removeBlocks: () => {},
			clearSelectedBlock: () => {},
		} ),
	};
} );

describe( 'Ticket Block sagas', () => {
	describe( 'watchers', () => {
		it( 'should watch actions', () => {
			const gen = watchers();
			expect( gen.next().value ).toEqual(
				takeEvery( [
					types.SET_TICKETS_INITIAL_STATE,
					types.RESET_TICKETS_BLOCK,
					types.SET_TICKET_INITIAL_STATE,
					types.FETCH_TICKET,
					types.CREATE_NEW_TICKET,
					types.UPDATE_TICKET,
					types.DELETE_TICKET,
					types.FETCH_TICKETS_HEADER_IMAGE,
					types.UPDATE_TICKETS_HEADER_IMAGE,
					types.DELETE_TICKETS_HEADER_IMAGE,
					types.SET_TICKET_DETAILS,
					types.SET_TICKET_TEMP_DETAILS,
					types.HANDLE_TICKET_START_DATE,
					types.HANDLE_TICKET_END_DATE,
					types.HANDLE_TICKET_START_TIME,
					types.HANDLE_TICKET_END_TIME,
					MOVE_TICKET_SUCCESS,
				], sagas.handler )
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.handleEventStartDateChanges )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handler', () => {
		let action;

		beforeEach( () => {
			action = { type: null };
		} );

		it( 'should set tickets initial state', () => {
			action.type = types.SET_TICKETS_INITIAL_STATE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setTicketsInitialState, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should reset tickets block', () => {
			action.type = types.RESET_TICKETS_BLOCK;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.resetTicketsBlock )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set ticket initial state', () => {
			action.type = types.SET_TICKET_INITIAL_STATE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setTicketInitialState, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should fetch ticket', () => {
			action.type = types.FETCH_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.fetchTicket, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should create new ticket', () => {
			action.type = types.CREATE_NEW_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.createNewTicket, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should update ticket', () => {
			action.type = types.UPDATE_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.updateTicket, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should delete ticket', () => {
			action.type = types.DELETE_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.deleteTicket, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should fetch tickets header image', () => {
			action.type = types.FETCH_TICKETS_HEADER_IMAGE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.fetchTicketsHeaderImage, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should update tickets header image', () => {
			action.type = types.UPDATE_TICKETS_HEADER_IMAGE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.updateTicketsHeaderImage, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should delete tickets header image', () => {
			action.type = types.DELETE_TICKETS_HEADER_IMAGE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.deleteTicketsHeaderImage )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set ticket details', () => {
			action.type = types.SET_TICKET_DETAILS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setTicketDetails, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set ticket temp details', () => {
			action.type = types.SET_TICKET_TEMP_DETAILS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setTicketTempDetails, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket start date', () => {
			action.type = types.HANDLE_TICKET_START_DATE;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketStartDate, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket end date', () => {
			action.type = types.HANDLE_TICKET_END_DATE;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketEndDate, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket start time', () => {
			action.type = types.HANDLE_TICKET_START_TIME;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketStartTime, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketStartTimeInput, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket end time', () => {
			action.type = types.HANDLE_TICKET_END_TIME;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketEndTime, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketEndTimeInput, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket move', () => {
			action.type = MOVE_TICKET_SUCCESS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketMove )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'createMissingTicketBlocks', () => {
		it( 'should create missing ticket blocks', () => {
			const wpDispatchCoreEditor = {
				insertBlocks: () => {},
			};
			const wpSelectCoreEditor = {
				getBlockCount: () => {},
				getBlocks: () => [],
			};
			const tickets = [ 'tribe' ];
			const gen = sagas.createMissingTicketBlocks( tickets );
			expect( gen.next().value ).toEqual(
				call( wpDispatch, 'core/editor' )
			);
			expect( gen.next( wpDispatchCoreEditor ).value ).toEqual(
				call( wpSelect, 'core/editor' )
			);
			expect( gen.next( wpSelectCoreEditor ).value ).toMatchSnapshot();
			expect( gen.next( [] ).done ).toEqual( true );
		} );
	} );

	describe( 'setTicketsInitialState', () => {
		it( 'should set tickets initial state', () => {
			const HEADER = 13;
			const SHARED_CAPACITY = '100';
			const PROVIDER = 'woo';
			const action = {
				payload: {
					get: ( key, defaultValue ) => {
						switch ( key ) {
							case 'header':
								return HEADER;
							case 'sharedCapacity':
								return SHARED_CAPACITY;
							case 'provider':
								return PROVIDER;
							case 'tickets':
								return [ 'tribe' ];
							default:
								return defaultValue;
						}
					},
				},
			};

			const gen = cloneableGenerator( sagas.setTicketsInitialState )( action );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsIdsInBlocks )
			);

			const clone1 = gen.clone();
			expect( clone1.next( [] ).value ).toEqual(
				call( sagas.createMissingTicketBlocks, [ 'tribe' ] )
			);
			expect( clone1.next().value ).toEqual(
				all( [
					put( actions.setTicketsSharedCapacity( SHARED_CAPACITY ) ),
					put( actions.setTicketsTempSharedCapacity( SHARED_CAPACITY ) ),
				] )
			);
			expect( clone1.next().value ).toEqual(
				put( actions.fetchTicketsHeaderImage( HEADER ) )
			);
			expect( clone1.next().value ).toEqual(
				put( actions.setTicketsProvider( PROVIDER ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			expect( clone2.next( [ 'tribe' ] ).value ).toEqual(
				all( [
					put( actions.setTicketsSharedCapacity( SHARED_CAPACITY ) ),
					put( actions.setTicketsTempSharedCapacity( SHARED_CAPACITY ) ),
				] )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.fetchTicketsHeaderImage( HEADER ) )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketsProvider( PROVIDER ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );

		it( 'should set tickets initial state for new event and no provider', () => {
			const HEADER = 0;
			const SHARED_CAPACITY = '0';
			const PROVIDER = '';
			const DEFAULT_PROVIDER = 'woo';
			const action = {
				payload: {
					get: ( key, defaultValue ) => {
						switch ( key ) {
							case 'header':
								return HEADER;
							case 'sharedCapacity':
								return SHARED_CAPACITY;
							case 'provider':
								return PROVIDER;
							case 'tickets':
								return [ 'tribe' ];
							default:
								return defaultValue;
						}
					},
				},
			};
			const gen = cloneableGenerator( sagas.setTicketsInitialState )( action );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsIdsInBlocks )
			);

			const clone1 = gen.clone();
			expect( clone1.next( [] ).value ).toEqual(
				call( sagas.createMissingTicketBlocks, [ 'tribe' ] )
			);
			expect( clone1.next().value ).toEqual(
				select( selectors.getDefaultTicketProvider )
			);
			expect( clone1.next( DEFAULT_PROVIDER ).value ).toEqual(
				put( actions.setTicketsProvider( DEFAULT_PROVIDER ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			expect( clone2.next( [ 'tribe' ] ).value ).toEqual(
				select( selectors.getDefaultTicketProvider )
			);
			expect( clone2.next( DEFAULT_PROVIDER ).value ).toEqual(
				put( actions.setTicketsProvider( DEFAULT_PROVIDER ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'resetTicketsBlock', () => {
		it( 'should reset tickets block', () => {
			const gen = sagas.resetTicketsBlock();
			expect( gen.next().value ).toEqual(
				select( selectors.hasCreatedTickets )
			);
			expect( gen.next( false ).value ).toEqual(
				all( [
					put( actions.removeTicketBlocks() ),
					put( actions.setTicketsIsSettingsOpen( false ) ),
				] )
			);
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( {} ).value ).toMatchSnapshot();
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setTicketsSharedCapacity( '' ) ),
					put( actions.setTicketsTempSharedCapacity( '' ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not reset tickets block', () => {
			const gen = sagas.resetTicketsBlock();
			expect( gen.next().value ).toEqual(
				select( selectors.hasCreatedTickets )
			);
			expect( gen.next( true ).value ).toEqual(
				all( [
					put( actions.removeTicketBlocks() ),
					put( actions.setTicketsIsSettingsOpen( false ) ),
				] )
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
					data: {
						blocks: {
							datetime: {
								selectors: {
									getStart: jest.fn(),
								},
							},
						},
					},
				},
			};
		} );

		afterEach( () => {
			delete global.tribe;
		} );

		it( 'should set ticket initial state', () => {
			const TICKET_ID = 99;
			const CLIENT_ID = 'modern-tribe';
			const HAS_BEEN_CREATED = true;
			const action = {
				payload: {
					get: ( key ) => {
						if ( key === 'ticketId' ) {
							return TICKET_ID;
						} else if ( key === 'hasBeenCreated' ) {
							return HAS_BEEN_CREATED;
						}
					},
					clientId: CLIENT_ID,
				},
			};

			const gen = cloneableGenerator( sagas.setTicketInitialState )( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( publishDate ).value ).toEqual(
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
				call( momentUtil.toTime, startMoment )
			);
			expect( gen.next( startTime ).value ).toEqual(
				all( [
					put( actions.setTicketStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketStartTimeInput( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketTempStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketTempStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketTempStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartTimeInput( action.payload.clientId, startTime ) ),
					put( actions.setTicketHasBeenCreated( action.payload.clientId, HAS_BEEN_CREATED ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
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
				call( momentUtil.toTime, endMoment )
			);
			expect( gen.next( endTime ).value ).toEqual(
				all( [
					put( actions.setTicketEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketEndTimeInput( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketTempEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketTempEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketTempEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndTimeInput( action.payload.clientId, endTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( plugins.selectors.hasPlugin, plugins.constants.TICKETS_PLUS )
			);
			expect( gen.next( false ).value ).toEqual(
				select( selectors.getTicketsSharedCapacity )
			);

			const clone1 = gen.clone();
			const blankSharedCapacity = '';

			expect( clone1.next( blankSharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketId( CLIENT_ID, TICKET_ID ) ),
					call( sagas.fetchTicket, { payload: { clientId: CLIENT_ID, ticketId: TICKET_ID } } ),
				] )
			);
			expect( clone1.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID )
			);
			expect( clone1.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID )
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
					call( sagas.fetchTicket, { payload: { clientId: CLIENT_ID, ticketId: TICKET_ID } } ),
				] )
			);
			expect( clone2.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID )
			);
			expect( clone2.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID )
			);
			expect( clone2.next().done ).toEqual( true );
		} );

		it( 'should set tickets initial state for new ticket', () => {
			const TICKET_ID = 0;
			const CLIENT_ID = 'modern-tribe';
			const HAS_BEEN_CREATED = true;
			const action = {
				payload: {
					get: ( key ) => {
						if ( key === 'ticketId' ) {
							return TICKET_ID;
						} else if ( key === 'hasBeenCreated' ) {
							return HAS_BEEN_CREATED;
						}
					},
					clientId: CLIENT_ID,
				},
			};
			global.tribe.events.data.blocks.datetime.selectors.getStart = jest.fn();

			const gen = cloneableGenerator( sagas.setTicketInitialState )( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( publishDate ).value ).toEqual(
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
				call( momentUtil.toTime, startMoment )
			);
			expect( gen.next( startTime ).value ).toEqual(
				all( [
					put( actions.setTicketStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketStartTimeInput( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketTempStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketTempStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketTempStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartTimeInput( action.payload.clientId, startTime ) ),
					put( actions.setTicketHasBeenCreated( action.payload.clientId, HAS_BEEN_CREATED ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
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
				call( momentUtil.toTime, endMoment )
			);
			expect( gen.next( endTime ).value ).toEqual(
				all( [
					put( actions.setTicketEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketEndTimeInput( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketTempEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketTempEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketTempEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndTimeInput( action.payload.clientId, endTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( plugins.selectors.hasPlugin, plugins.constants.TICKETS_PLUS )
			);
			expect( gen.next( false ).value ).toEqual(
				select( selectors.getTicketsSharedCapacity )
			);

			const clone1 = gen.clone();
			const blankSharedCapacity = '';

			expect( clone1.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID )
			);
			expect( clone1.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID )
			);
			expect( clone1.next( blankSharedCapacity ).done ).toEqual( true );

			const clone2 = gen.clone();
			const sharedCapacity = '100';

			expect( clone2.next( sharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketCapacity( CLIENT_ID, sharedCapacity ) ),
					put( actions.setTicketTempCapacity( CLIENT_ID, sharedCapacity ) ),
				] )
			);
			expect( clone2.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID )
			);
			expect( clone2.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID )
			);
			expect( clone2.next().done ).toEqual( true );
		} );

		it( 'should set capacity type to shared if tickets plus is active', () => {
			const TICKET_ID = 0;
			const CLIENT_ID = 'modern-tribe';
			const HAS_BEEN_CREATED = true;
			const action = {
				payload: {
					get: ( key ) => {
						if ( key === 'ticketId' ) {
							return TICKET_ID;
						} else if ( key === 'hasBeenCreated' ) {
							return HAS_BEEN_CREATED;
						}
					},
					clientId: CLIENT_ID,
				},
			};
			global.tribe.events.data.blocks.datetime.selectors.getStart = jest.fn();

			const gen = cloneableGenerator( sagas.setTicketInitialState )( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( publishDate ).value ).toEqual(
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
				call( momentUtil.toTime, startMoment )
			);
			expect( gen.next( startTime ).value ).toEqual(
				all( [
					put( actions.setTicketStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketStartTimeInput( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartDate( action.payload.clientId, startDate ) ),
					put( actions.setTicketTempStartDateInput( action.payload.clientId, startDateInput ) ),
					put( actions.setTicketTempStartDateMoment( action.payload.clientId, startMoment ) ),
					put( actions.setTicketTempStartTime( action.payload.clientId, startTime ) ),
					put( actions.setTicketTempStartTimeInput( action.payload.clientId, startTime ) ),
					put( actions.setTicketHasBeenCreated( action.payload.clientId, HAS_BEEN_CREATED ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
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
				call( momentUtil.toTime, endMoment )
			);
			expect( gen.next( endTime ).value ).toEqual(
				all( [
					put( actions.setTicketEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketEndTimeInput( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndDate( action.payload.clientId, endDate ) ),
					put( actions.setTicketTempEndDateInput( action.payload.clientId, endDateInput ) ),
					put( actions.setTicketTempEndDateMoment( action.payload.clientId, endMoment ) ),
					put( actions.setTicketTempEndTime( action.payload.clientId, endTime ) ),
					put( actions.setTicketTempEndTimeInput( action.payload.clientId, endTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( plugins.selectors.hasPlugin, plugins.constants.TICKETS_PLUS )
			);
			expect( gen.next( true ).value ).toEqual(
				all( [
					put( actions.setTicketCapacityType( CLIENT_ID, constants.TICKET_TYPES[ constants.SHARED ] ) ),
					put( actions.setTicketTempCapacityType( CLIENT_ID, constants.TICKET_TYPES[ constants.SHARED ] ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsSharedCapacity )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID )
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID )
			);
			expect( gen.next( '' ).done ).toEqual( true );
		} );
	} );

	describe( 'setBodyDetails', () => {
		it( 'should set body details', () => {
			const postId = 10;
			const rootClientId = 0;
			const clientId = 'modern-tribe';
			const menuOrder = 0;
			const props = { clientId };
			const gen = cloneableGenerator( sagas.setBodyDetails )( clientId );

			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( rootClientId ).value ).toEqual(
				select( selectors.getTicketProvider, props )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsProvider )
			);
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
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
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( menuOrder ).value ).toEqual(
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
			const CLIENT_ID = 'modern-tribe';
			const action = {
				payload: {
					ticketId: TICKET_ID,
					clientId: CLIENT_ID,
				},
			};

			const gen = cloneableGenerator( sagas.fetchTicket )( action );
			expect( gen.next().value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, true ) )
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
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
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
					supports_attendee_information: true,
				},
			};
			const startMoment2 = momentUtil.toMoment( apiResponse2.data.available_from );
			const startDate2 = momentUtil.toDatabaseDate( startMoment2 );
			const startDateInput2 = momentUtil.toDate( startMoment2 );
			const startTime2 = momentUtil.toDatabaseTime( startMoment2 );
			const startTimeInput2 = momentUtil.toTime( startMoment2 );
			const endMoment2 = momentUtil.toMoment( '' );
			const endDate2 = '';
			const endDateInput2 = '';
			const endTime2 = '';
			const endTimeInput2 = '';

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
				call( momentUtil.toTime, startMoment2 )
			);
			expect( clone2.next( startTimeInput2 ).value ).toEqual(
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
				startTimeInput: startTimeInput2,
				endTimeInput: endTimeInput2,
				capacityType: apiResponse2.data.capacity_type,
				capacity: apiResponse2.data.capacity,
			};

			expect( clone2.next( endMoment2 ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( CLIENT_ID, details2 ) ),
					put( actions.setTicketTempDetails( CLIENT_ID, details2 ) ),
					put( actions.setTicketSold( CLIENT_ID, apiResponse2.data.totals.sold ) ),
					put( actions.setTicketAvailable( CLIENT_ID, apiResponse2.data.totals.stock ) ),
					put( actions.setTicketCurrencySymbol( CLIENT_ID, apiResponse2.data.cost_details.currency_symbol ) ),
					put( actions.setTicketCurrencyPosition( CLIENT_ID, apiResponse2.data.cost_details.currency_position ) ),
					put( actions.setTicketProvider( CLIENT_ID, apiResponse2.data.provider ) ),
					put( actions.setTicketHasAttendeeInfoFields( CLIENT_ID, apiResponse2.data.supports_attendee_information ) ),
					put( actions.setTicketHasBeenCreated( CLIENT_ID, true ) ),
				] )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
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
					supports_attendee_information: true,
				},
			};

			const startMoment3 = momentUtil.toMoment( apiResponse3.data.available_from );
			const startDate3 = momentUtil.toDatabaseDate( startMoment3 );
			const startDateInput3 = momentUtil.toDate( startMoment3 );
			const startTime3 = momentUtil.toDatabaseTime( startMoment3 );
			const startTimeInput3 = momentUtil.toTime( startMoment3 );
			const endMoment3 = momentUtil.toMoment( apiResponse3.data.available_until );
			const endDate3 = momentUtil.toDatabaseDate( endMoment3 );
			const endDateInput3 = momentUtil.toDate( endMoment3 );
			const endTime3 = momentUtil.toDatabaseTime( endMoment3 );
			const endTimeInput3 = momentUtil.toTime( endMoment3 );

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
				call( momentUtil.toTime, startMoment3 )
			);
			expect( clone3.next( startTimeInput3 ).value ).toEqual(
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
			expect( clone3.next( startTime3 ).value ).toEqual(
				call( momentUtil.toTime, endMoment3 )
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
				startTimeInput: startTimeInput3,
				endTimeInput: endTimeInput3,
				capacityType: apiResponse3.data.capacity_type,
				capacity: apiResponse3.data.capacity,
			};

			expect( clone3.next( startTimeInput3 ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( CLIENT_ID, details3 ) ),
					put( actions.setTicketTempDetails( CLIENT_ID, details3 ) ),
					put( actions.setTicketSold( CLIENT_ID, apiResponse3.data.totals.sold ) ),
					put( actions.setTicketAvailable( CLIENT_ID, apiResponse3.data.totals.stock ) ),
					put( actions.setTicketCurrencySymbol( CLIENT_ID, apiResponse3.data.cost_details.currency_symbol ) ),
					put( actions.setTicketCurrencyPosition( CLIENT_ID, apiResponse3.data.cost_details.currency_position ) ),
					put( actions.setTicketProvider( CLIENT_ID, apiResponse3.data.provider ) ),
					put( actions.setTicketHasAttendeeInfoFields( CLIENT_ID, apiResponse3.data.supports_attendee_information ) ),
					put( actions.setTicketHasBeenCreated( CLIENT_ID, true ) ),
				] )
			);
			expect( clone3.next().value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
			);
			expect( clone3.next().done ).toEqual( true );
		} );

		it( 'should not fetch ticket if new ticket', () => {
			const TICKET_ID = 0;
			const CLIENT_ID = 'modern-tribe';
			const action = {
				payload: {
					ticketId: TICKET_ID,
					clientId: CLIENT_ID,
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
			const startTimeInput = '19:48:42';
			const endTimeInput = '19:48:42';
			const capacityType = 'own';
			const capacity = 100;

			const CLIENT_ID = 'modern-tribe';
			const props = { clientId: CLIENT_ID };
			const action = {
				payload: {
					clientId: CLIENT_ID,
				},
			};

			const gen = cloneableGenerator( sagas.createNewTicket )( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setBodyDetails, CLIENT_ID )
			);

			const body = new FormData();

			expect( gen.next( body ).value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, true ) )
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
					id: 13,
					capacity_details: {
						available: 100,
					},
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
					select( selectors.getTicketTempStartTimeInput, props ),
					select( selectors.getTicketTempEndTimeInput, props ),
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
				startTimeInput,
				endTimeInput,
				capacityType,
				capacity,
			] ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( CLIENT_ID, {
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
						startTimeInput,
						endTimeInput,
						capacityType,
						capacity,
					} ) ),
					put( actions.setTicketId( CLIENT_ID, apiResponse1.data.id ) ),
					put( actions.setTicketHasBeenCreated( CLIENT_ID, true ) ),
					put( actions.setTicketAvailable(
						CLIENT_ID,
						apiResponse1.data.capacity_details.available,
					) ),
					put( actions.setTicketProvider(
						CLIENT_ID,
						PROVIDER_CLASS_TO_PROVIDER_MAPPING[ apiResponse1.data.provider_class ],
					) ),
					put( actions.setTicketHasChanges( CLIENT_ID, false ) ),
				] )
			);
			expect( clone11.next().value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
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
					select( selectors.getTicketTempStartTimeInput, props ),
					select( selectors.getTicketTempEndTimeInput, props ),
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
				startTimeInput,
				endTimeInput,
				capacityType,
				capacity,
			] ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( CLIENT_ID, {
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
						startTimeInput,
						endTimeInput,
						capacityType,
						capacity,
					} ) ),
					put( actions.setTicketId( CLIENT_ID, apiResponse1.data.id ) ),
					put( actions.setTicketHasBeenCreated( CLIENT_ID, true ) ),
					put( actions.setTicketAvailable(
						CLIENT_ID,
						apiResponse1.data.capacity_details.available,
					) ),
					put( actions.setTicketProvider(
						CLIENT_ID,
						PROVIDER_CLASS_TO_PROVIDER_MAPPING[ apiResponse1.data.provider_class ],
					) ),
					put( actions.setTicketHasChanges( CLIENT_ID, false ) ),
				] )
			);
			expect( clone12.next().value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
			);
			expect( clone12.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponse2 = {
				response: {
					ok: false,
				},
			};

			expect( clone2.next( apiResponse2 ).value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
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
			const startTimeInput = '19:48:42';
			const endTimeInput = '19:48:42';
			const capacityType = 'own';
			const capacity = 100;

			const TICKET_ID = 13;
			const CLIENT_ID = 'modern-tribe';
			const props = { clientId: CLIENT_ID };
			const action = {
				payload: {
					clientId: CLIENT_ID,
				},
			};

			const gen = cloneableGenerator( sagas.updateTicket )( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setBodyDetails, CLIENT_ID )
			);

			const body = new FormData();

			expect( gen.next( body ).value ).toEqual(
				select( selectors.getTicketId, props )
			);
			expect( gen.next( TICKET_ID ).value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, true ) )
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
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const apiResponse2 = {
				response: {
					ok: true,
				},
				data: {
					capacity_details: {
						available: 100,
						sold: 10,
					},
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
					select( selectors.getTicketTempStartTimeInput, props ),
					select( selectors.getTicketTempEndTimeInput, props ),
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
				startTimeInput,
				endTimeInput,
				capacityType,
				capacity,
			] ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( CLIENT_ID, {
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
						startTimeInput,
						endTimeInput,
						capacityType,
						capacity,
					} ) ),
					put( actions.setTicketSold(
						CLIENT_ID,
						apiResponse2.data.capacity_details.sold,
					) ),
					put( actions.setTicketAvailable(
						CLIENT_ID,
						apiResponse2.data.capacity_details.available,
					) ),
					put( actions.setTicketHasChanges( CLIENT_ID, false ) ),
				] )
			);
			expect( clone2.next( apiResponse1 ).value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'deleteTicket', () => {
		it( 'should delete ticket', () => {
			const TICKET_ID = 13;
			const POST_ID = 10;
			const CLIENT_ID = 'modern-tribe';
			const props = { clientId: CLIENT_ID };
			const action = {
				payload: {
					clientId: CLIENT_ID,
				},
			};

			const gen = cloneableGenerator( sagas.deleteTicket )( action );

			expect( gen.next().value ).toEqual(
				call( [window, 'confirm'], 'Are you sure you want to delete this ticket? It cannot be undone.' )
			);

			expect( gen.next( true ).value ).toEqual(
				select( selectors.getTicketId, props )
			);
			expect( gen.next( TICKET_ID ).value ).toEqual(
				select( selectors.getTicketHasBeenCreated, props )
			);

			const clone1 = gen.clone();
			const hasBeenCreated1 = false;

			expect( clone1.next( hasBeenCreated1 ).value ).toEqual(
				put( actions.setTicketIsSelected( CLIENT_ID, false ) )
			);
			expect( clone1.next().value ).toEqual(
				put( actions.removeTicketBlock( CLIENT_ID ) )
			);
			expect( clone1.next().value ).toMatchSnapshot();
			expect( clone1.next().value ).toMatchSnapshot();
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const hasBeenCreated2 = true;
			const body = [
				`${ encodeURIComponent( 'post_id' ) }=${ encodeURIComponent( POST_ID ) }`,
				`${ encodeURIComponent( 'remove_ticket_nonce' ) }=${ encodeURIComponent( '' ) }`,
			];

			expect( clone2.next( hasBeenCreated2 ).value ).toEqual(
				put( actions.setTicketIsSelected( CLIENT_ID, false ) )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.removeTicketBlock( CLIENT_ID ) )
			);
			expect( clone2.next().value ).toMatchSnapshot();
			expect( clone2.next().value ).toMatchSnapshot();
			expect( clone2.next().value ).toMatchSnapshot();
			expect( clone2.next( POST_ID ).value ).toEqual(
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
			const postId = 10;
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
			const gen = sagas.updateTicketsHeaderImage( action );

			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tribe_events/${ postId }`,
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

			const apiResponse = {
				response: {
					ok: true,
				},
			};
			const headerImage = {
				id: action.payload.image.id,
				alt: action.payload.image.alt,
				src: action.payload.image.sizes.medium.url,
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setTicketsHeaderImage( headerImage ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPHeaderImage( headerImage ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not update tickets header image', () => {
			const postId = 10;
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
			const gen = sagas.updateTicketsHeaderImage( action );

			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tribe_events/${ postId }`,
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

			const apiResponse = {
				response: {
					ok: false,
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'deleteTicketsHeaderImage', () => {
		it( 'should delete tickets header image', () => {
			const postId = 10;

			const gen = sagas.deleteTicketsHeaderImage();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tribe_events/${ postId }`,
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

			const apiResponse = {
				response: {
					ok: true,
				},
			};

			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setTicketsHeaderImage( TICKET_HEADER_IMAGE_DEFAULT_STATE ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPHeaderImage( RSVP_HEADER_IMAGE_DEFAULT_STATE ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not delete tickets header image', () => {
			const postId = 10;

			const gen = sagas.deleteTicketsHeaderImage();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) )
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: `tribe_events/${ postId }`,
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

			const apiResponse = {
				response: {
					ok: false,
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) )
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setTicketDetails', () => {
		it( 'should set ticket details', () => {
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
			const startTimeInput = '19:48:42';
			const endTimeInput = '19:48:42';
			const capacityType = 'own';
			const capacity = 100;

			const CLIENT_ID = 'modern-tribe';
			const action = {
				payload: {
					clientId: CLIENT_ID,
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
						startTimeInput,
						endTimeInput,
						capacityType,
						capacity,
					},
				},
			};

			const gen = sagas.setTicketDetails( action );
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setTicketTitle( CLIENT_ID, title ) ),
					put( actions.setTicketDescription( CLIENT_ID, description ) ),
					put( actions.setTicketPrice( CLIENT_ID, price ) ),
					put( actions.setTicketSku( CLIENT_ID, sku ) ),
					put( actions.setTicketStartDate( CLIENT_ID, startDate ) ),
					put( actions.setTicketStartDateInput( CLIENT_ID, startDateInput ) ),
					put( actions.setTicketStartDateMoment( CLIENT_ID, startDateMoment ) ),
					put( actions.setTicketEndDate( CLIENT_ID, endDate ) ),
					put( actions.setTicketEndDateInput( CLIENT_ID, endDateInput ) ),
					put( actions.setTicketEndDateMoment( CLIENT_ID, endDateMoment ) ),
					put( actions.setTicketStartTime( CLIENT_ID, startTime ) ),
					put( actions.setTicketEndTime( CLIENT_ID, endTime ) ),
					put( actions.setTicketStartTimeInput( CLIENT_ID, startTimeInput ) ),
					put( actions.setTicketEndTimeInput( CLIENT_ID, endTimeInput ) ),
					put( actions.setTicketCapacityType( CLIENT_ID, capacityType ) ),
					put( actions.setTicketCapacity( CLIENT_ID, capacity ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setTicketTempDetails', () => {
		it( 'should set ticket temp details', () => {
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
			const startTimeInput = '19:48:42';
			const endTimeInput = '19:48:42';
			const capacityType = 'own';
			const capacity = 100;

			const CLIENT_ID = 'modern-tribe';
			const action = {
				payload: {
					clientId: CLIENT_ID,
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
						startTimeInput,
						endTimeInput,
						capacityType,
						capacity,
					},
				},
			};

			const gen = sagas.setTicketTempDetails( action );
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setTicketTempTitle( CLIENT_ID, title ) ),
					put( actions.setTicketTempDescription( CLIENT_ID, description ) ),
					put( actions.setTicketTempPrice( CLIENT_ID, price ) ),
					put( actions.setTicketTempSku( CLIENT_ID, sku ) ),
					put( actions.setTicketTempStartDate( CLIENT_ID, startDate ) ),
					put( actions.setTicketTempStartDateInput( CLIENT_ID, startDateInput ) ),
					put( actions.setTicketTempStartDateMoment( CLIENT_ID, startDateMoment ) ),
					put( actions.setTicketTempEndDate( CLIENT_ID, endDate ) ),
					put( actions.setTicketTempEndDateInput( CLIENT_ID, endDateInput ) ),
					put( actions.setTicketTempEndDateMoment( CLIENT_ID, endDateMoment ) ),
					put( actions.setTicketTempStartTime( CLIENT_ID, startTime ) ),
					put( actions.setTicketTempEndTime( CLIENT_ID, endTime ) ),
					put( actions.setTicketTempStartTimeInput( CLIENT_ID, startTimeInput ) ),
					put( actions.setTicketTempEndTimeInput( CLIENT_ID, endTimeInput ) ),
					put( actions.setTicketTempCapacityType( CLIENT_ID, capacityType ) ),
					put( actions.setTicketTempCapacity( CLIENT_ID, capacity ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketDurationError', () => {
		it( 'should set has duration error to true if start or end moment is invalid', () => {
			const CLIENT_ID = 'tribe';
			const gen = sagas.handleTicketDurationError( CLIENT_ID );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempStartDateMoment, { clientId: CLIENT_ID } )
			);
			expect( gen.next( undefined ).value ).toEqual(
				select( selectors.getTicketTempEndDateMoment, { clientId: CLIENT_ID } )
			);
			expect( gen.next( undefined ).value ).toEqual(
				put( actions.setTicketHasDurationError( CLIENT_ID, true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set thas duration error to true if start date time is after end date time', () => {
			const CLIENT_ID = 'tribe';
			const START_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const END_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const START_TIME = '12:00:00';
			const END_TIME = '13:00:00';
			const START_TIME_SECONDS = 43200;
			const END_TIME_SECONDS = 46800;
			const gen = sagas.handleTicketDurationError( CLIENT_ID );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempStartDateMoment, { clientId: CLIENT_ID } )
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				select( selectors.getTicketTempEndDateMoment, { clientId: CLIENT_ID } )
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				select( selectors.getTicketTempStartTime, { clientId: CLIENT_ID } )
			);
			expect( gen.next( START_TIME ).value ).toEqual(
				select( selectors.getTicketTempEndTime, { clientId: CLIENT_ID } )
			);
			expect( gen.next( END_TIME ).value ).toEqual(
				call( timeUtil.toSeconds, START_TIME, timeUtil.TIME_FORMAT_HH_MM_SS )
			);
			expect( gen.next( START_TIME_SECONDS ).value ).toEqual(
				call( timeUtil.toSeconds, END_TIME, timeUtil.TIME_FORMAT_HH_MM_SS )
			);
			expect( gen.next( END_TIME_SECONDS ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, START_DATE_MOMENT.clone(), START_TIME_SECONDS )
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, END_DATE_MOMENT.clone(), END_TIME_SECONDS )
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				call( [ START_DATE_MOMENT, 'isSameOrAfter' ], END_DATE_MOMENT )
			);
			expect( gen.next( true ).value ).toEqual(
				put( actions.setTicketHasDurationError( CLIENT_ID, true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set thas duration error to false if start date time is before end date time', () => {
			const CLIENT_ID = 'tribe';
			const START_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const END_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const START_TIME = '12:00:00';
			const END_TIME = '13:00:00';
			const START_TIME_SECONDS = 43200;
			const END_TIME_SECONDS = 46800;
			const gen = sagas.handleTicketDurationError( CLIENT_ID );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempStartDateMoment, { clientId: CLIENT_ID } )
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				select( selectors.getTicketTempEndDateMoment, { clientId: CLIENT_ID } )
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				select( selectors.getTicketTempStartTime, { clientId: CLIENT_ID } )
			);
			expect( gen.next( START_TIME ).value ).toEqual(
				select( selectors.getTicketTempEndTime, { clientId: CLIENT_ID } )
			);
			expect( gen.next( END_TIME ).value ).toEqual(
				call( timeUtil.toSeconds, START_TIME, timeUtil.TIME_FORMAT_HH_MM_SS )
			);
			expect( gen.next( START_TIME_SECONDS ).value ).toEqual(
				call( timeUtil.toSeconds, END_TIME, timeUtil.TIME_FORMAT_HH_MM_SS )
			);
			expect( gen.next( END_TIME_SECONDS ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, START_DATE_MOMENT.clone(), START_TIME_SECONDS )
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, END_DATE_MOMENT.clone(), END_TIME_SECONDS )
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				call( [ START_DATE_MOMENT, 'isSameOrAfter' ], END_DATE_MOMENT )
			);
			expect( gen.next( false ).value ).toEqual(
				put( actions.setTicketHasDurationError( CLIENT_ID, false ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketStartDate', () => {
		let action;

		beforeEach( () => {
			action = {
				payload: {
					clientId: 'tribe',
					date: undefined,
					dayPickerInput: {
						state: {
							value: '',
						}
					}
				}
			}
		} );

		it( 'should handle undefined ticket start date', () => {
			const gen = sagas.handleTicketStartDate( action );
			expect( gen.next().value ).toEqual( undefined );
			expect( gen.next( undefined ).value ).toEqual( '' );
			expect( gen.next( '' ).value ).toEqual(
				put( actions.setTicketTempStartDate( action.payload.clientId, '' ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempStartDateInput( action.payload.clientId, action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempStartDateMoment( action.payload.clientId, undefined ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket start date', () => {
			action.payload.date = 'January 1, 2018';
			action.payload.dayPickerInput.state.value = 'January 1, 2018';
			const gen = sagas.handleTicketStartDate( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				call( momentUtil.toDatabaseDate, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				put( actions.setTicketTempStartDate( action.payload.clientId, action.payload.date ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempStartDateInput( action.payload.clientId, action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempStartDateMoment( action.payload.clientId, action.payload.date ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketEndDate', () => {
		let action;

		beforeEach( () => {
			action = {
				payload: {
					clientId: 'tribe',
					date: undefined,
					dayPickerInput: {
						state: {
							value: '',
						}
					}
				}
			}
		} );

		it( 'should handle undefined ticket end date', () => {
			const gen = sagas.handleTicketEndDate( action );
			expect( gen.next().value ).toEqual( undefined );
			expect( gen.next( undefined ).value ).toEqual( '' );
			expect( gen.next( '' ).value ).toEqual(
				put( actions.setTicketTempEndDate( action.payload.clientId, '' ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempEndDateInput( action.payload.clientId, action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempEndDateMoment( action.payload.clientId, undefined ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket end date', () => {
			action.payload.date = 'January 1, 2018';
			action.payload.dayPickerInput.state.value = 'January 1, 2018';
			const gen = sagas.handleTicketEndDate( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				call( momentUtil.toDatabaseDate, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				put( actions.setTicketTempEndDate( action.payload.clientId, action.payload.date ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempEndDateInput( action.payload.clientId, action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketTempEndDateMoment( action.payload.clientId, action.payload.date ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketStartTime', () => {
		it( 'should handle ticket start time', () => {
			const action = {
				payload: {
					clientId: 'tribe',
					seconds: 3600,
				},
			};
			const startTime = '01:00';
			const gen = sagas.handleTicketStartTime( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( startTime ).value ).toEqual(
				put( actions.setTicketTempStartTime( action.payload.clientId, `${ startTime }:00` ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketStartTimeInput', () => {
		it( 'should handle ticket start time input', () => {
			const startTimeInput = '01:00';
			const action = {
				payload: {
					clientId: 'tribe',
					seconds: 3600,
				},
			};
			const gen = sagas.handleTicketStartTimeInput( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, startTimeInput, momentUtil.TIME_FORMAT, false )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toTime, startTimeInput )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				put( actions.setTicketTempStartTimeInput( action.payload.clientId, startTimeInput ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketEndTime', () => {
		it( 'should handle ticket end time', () => {
			const action = {
				payload: {
					clientId: 'tribe',
					seconds: 3600,
				},
			};
			const endTime = '01:00';
			const gen = sagas.handleTicketEndTime( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( endTime ).value ).toEqual(
				put( actions.setTicketTempEndTime( action.payload.clientId, `${ endTime }:00` ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketEndTimeInput', () => {
		it( 'should handle ticket end time input', () => {
			const startTimeInput = '01:00';
			const action = {
				payload: {
					clientId: 'tribe',
					seconds: 3600,
				},
			};
			const gen = sagas.handleTicketStartTimeInput( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, startTimeInput, momentUtil.TIME_FORMAT, false )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toTime, startTimeInput )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				put( actions.setTicketTempStartTimeInput( action.payload.clientId, startTimeInput ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleTicketMove', () => {
		it( 'should handle ticket move', () => {
			const ticketIds = [ 42 ];
			const gen = cloneableGenerator( sagas.handleTicketMove )();
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsAllClientIds )
			);
			expect( gen.next( ticketIds ).value ).toEqual(
				select( moveSelectors.getModalClientId )
			);

			const clone1 = gen.clone();
			expect( clone1.next( 0 ).done ).toEqual( true );

			const clone2 = gen.clone();
			expect( clone2.next( 42 ).value ).toEqual(
				put( actions.setTicketIsSelected( 42, false ) )
			);
			expect( clone2.next().value ).toEqual(
				put( actions.removeTicketBlock( 42 ) )
			);
			expect( clone2.next().value ).toMatchSnapshot();
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'syncTicketSaleEndWithEventStart', () => {
		let prevDate, state, momentMock, clientId;
		beforeEach( () => {
			clientId = 'clientId';
			prevDate = '2018-01-01 00:00:00';
			state = {
				startDate: 'January 1, 2018',
				startTime: '12:34',
				endDate: 'January 4, 2018',
				endTime: '23:32',
			};
			global.tribe = {
				events: {
					data: {
						blocks: {
							datetime: {
								selectors: {
									getStart: jest.fn(),
								},
							},
						},
					},
				},
			};
			momentMock = {
				local: jest.fn(),
				isSame: jest.fn(),
				format: jest.fn(),
			};
		} );

		afterEach( () => {
			delete global.tribe;
		} );

		it( 'should not sync', () => {
			const gen = sagas.syncTicketSaleEndWithEventStart( prevDate, clientId );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempEndDateMoment, { clientId } )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				select( selectors.getTicketEndDateMoment, { clientId } )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( createDates, prevDate )
			);
			expect( gen.next( { moment: momentMock } ).value ).toMatchSnapshot();
			expect( gen.next( false ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should sync', () => {
			const gen = sagas.syncTicketSaleEndWithEventStart( prevDate, clientId );
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempEndDateMoment, { clientId } )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				select( selectors.getTicketEndDateMoment, { clientId } )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( createDates, prevDate )
			);
			expect( gen.next( { moment: momentMock } ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();

			expect( gen.next( true ).value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
			);
			expect( gen.next( '2018-02-02 02:00:00' ).value ).toEqual(
				call( createDates, '2018-02-02 02:00:00' )
			);

			expect( gen.next( {
				moment: '2018-02-02',
				date: '2018-02-02',
				dateInput: '2018-02-02',
				time: '02:00:00',
				timeInput: '02:00:00',
			} ).value ).toMatchSnapshot();

			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleEventStartDateChanges', () => {
		let channel;
		beforeEach( () => {
			global.tribe = {
				events: {
					data: {
						blocks: {
							datetime: {
								selectors: {
									getStart: jest.fn(),
								},
								types: {
									SET_START_DATE_TIME: 'SET_START_DATE_TIME',
									SET_START_TIME: 'SET_START_TIME',
								},
							},
						},
					},
				},
			};
			channel = { name, take: jest.fn(), close: jest.fn() };
		} );

		afterEach( () => {
			delete global.tribe;
		} );

		it( 'should handle start time changes', () => {
			const gen = sagas.handleEventStartDateChanges();

			expect(gen.next().value).toEqual(
				call( hasPostTypeChannel )
			);
			expect(gen.next(channel).value).toMatchSnapshot();
			expect(gen.next().value).toEqual(
				call( [ channel, 'close' ] )
			);

			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType )
			);

			expect( gen.next( true ).value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
			);

			expect( gen.next( '2018-01-01 12:00:00' ).value ).toEqual(
				take( [ 'SET_START_DATE_TIME', 'SET_START_TIME' ] )
			);

			expect( gen.next().value ).toEqual(
				fork( sagas.syncTicketsSaleEndWithEventStart, '2018-01-01 12:00:00' )
			);

			expect( gen.next().done ).toEqual( false );
		} );
	} );

	describe( 'saveTicketWithPostSave', () => {
		let channel, clientId;

		beforeEach( () => {
			channel = { name, take: jest.fn(), close: jest.fn() };
			clientId = 'clientId';
		} );

		it( 'should update when channel saves', () => {
			const gen = sagas.saveTicketWithPostSave( clientId );

			expect( gen.next().value ).toEqual(
				select( selectors.getTicketHasBeenCreated, { clientId } )
			);

			expect( gen.next( true ).value ).toEqual(
				call( createWPEditorSavingChannel )
			);

			expect( gen.next( channel ).value ).toEqual(
				call( createWPEditorNotSavingChannel )
			);

			expect( gen.next( channel ).value ).toEqual(
				take( channel )
			);

			expect( gen.next().value ).toEqual(
				call( sagas.updateTicket, { payload: { clientId } } )
			);

			expect( gen.next( channel ).value ).toEqual(
				take( channel )
			);

			expect( gen.next( channel ).value ).toEqual(
				take( channel )
			);

			expect( gen.next().value ).toEqual(
				call( sagas.updateTicket, { payload: { clientId } } )
			);

			expect( gen.next( channel ).value ).toEqual(
				take( channel )
			);
		} );
		it( 'should do nothing', () => {
			const gen = sagas.saveTicketWithPostSave( clientId );

			expect( gen.next().value ).toEqual(
				select( selectors.getTicketHasBeenCreated, { clientId } )
			);

			expect( gen.next( false ).done ).toEqual( true );
		} );
	} );
} );
