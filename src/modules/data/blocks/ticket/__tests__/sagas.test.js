/**
 * External dependencies
 */
import { takeEvery, put, all, select, call, take, fork } from 'redux-saga/effects';
import { cloneableGenerator } from 'redux-saga/utils';

/**
 * WordPress dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';
import { applyFilters, doAction } from '@wordpress/hooks';

/**
 * Internal Dependencies
 */
import * as constants from '../constants';
import * as types from '../types';
import * as actions from '../actions';
import watchers, * as sagas from '../sagas';
import * as selectors from '../selectors';
import {
	DEFAULT_STATE as TICKET_HEADER_IMAGE_DEFAULT_STATE,
} from '../reducers/header-image';
import * as rsvpActions from '@moderntribe/tickets/data/blocks/rsvp/actions';
import {
	DEFAULT_STATE as RSVP_HEADER_IMAGE_DEFAULT_STATE,
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

jest.mock( '@moderntribe/common/utils/moment', () => ( {
	toMoment: ( date ) => date,
	toDatabaseDate: ( date ) => date,
	toDate: ( date ) => date,
	toDatabaseTime: ( date ) => date,
	toTime: ( date ) => date,
} ) );

const {
	INDEPENDENT,
	SHARED,
	TICKET_TYPES,
	PROVIDER_CLASS_TO_PROVIDER_MAPPING,
	WOO_CLASS,
} = constants;

jest.mock( '@wordpress/data', () => {
	return {
		select: ( key ) => {
			if ( key === 'core/block-editor' ) {
				return {
					getBlockCount: () => {},
					getBlockIndex: () => 0,
					getBlockRootClientId: () => 88,
					getBlocks: () => {},
					getCurrentPostId: () => 10,
					getCurrentPostAttribute: () => {},
					getCurrentPostType: () => 'tribe_events',
					getEditedPostAttribute: ( attr ) => {
						if ( attr === 'date' ) {
							return '2018-11-09T19:48:42';
						}
					},
				};
			}
			if ( key === 'core/editor' ) {
				return  {
					getCurrentPost: () => {
						return {
							id: 10,
							type: 'tec_tickets',
						};
					},
					getBlockCount: () => {},
					getBlockIndex: () => 0,
					getBlockRootClientId: () => 88,
					getBlocks: () => {},
					getCurrentPostId: () => 10,
					getCurrentPostAttribute: () => {},
					getCurrentPostType: () => 'tribe_events',
					getEditedPostAttribute: ( attr ) => {
						if ( attr === 'date' ) {
							return '2018-11-09T19:48:42';
						}
					},
				}
			}
			if ( key === 'core' ) {
				return {
					getPostType: () => ( {
						rest_base: 'tribe_events',
					} ),
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
					types.HANDLE_TICKET_SALE_START_DATE,
					types.HANDLE_TICKET_SALE_END_DATE,
					MOVE_TICKET_SUCCESS,
					types.UPDATE_UNEDITABLE_TICKETS,
				], sagas.handler ),
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.handleEventStartDateChanges ),
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
				call( sagas.setTicketsInitialState, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should reset tickets block', () => {
			action.type = types.RESET_TICKETS_BLOCK;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.resetTicketsBlock ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set ticket initial state', () => {
			action.type = types.SET_TICKET_INITIAL_STATE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setTicketInitialState, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should fetch ticket', () => {
			action.type = types.FETCH_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.fetchTicket, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should create new ticket', () => {
			action.type = types.CREATE_NEW_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.createNewTicket, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should update ticket', () => {
			action.type = types.UPDATE_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.updateTicket, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should delete ticket', () => {
			action.type = types.DELETE_TICKET;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.deleteTicket, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should fetch tickets header image', () => {
			action.type = types.FETCH_TICKETS_HEADER_IMAGE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.fetchTicketsHeaderImage, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should update tickets header image', () => {
			action.type = types.UPDATE_TICKETS_HEADER_IMAGE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.updateTicketsHeaderImage, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should delete tickets header image', () => {
			action.type = types.DELETE_TICKETS_HEADER_IMAGE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.deleteTicketsHeaderImage ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set ticket details', () => {
			action.type = types.SET_TICKET_DETAILS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setTicketDetails, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set ticket temp details', () => {
			action.type = types.SET_TICKET_TEMP_DETAILS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setTicketTempDetails, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket start date', () => {
			action.type = types.HANDLE_TICKET_START_DATE;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketStartDate, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket end date', () => {
			action.type = types.HANDLE_TICKET_END_DATE;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketEndDate, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket start time', () => {
			action.type = types.HANDLE_TICKET_START_TIME;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketStartTime, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketStartTimeInput, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket end time', () => {
			action.type = types.HANDLE_TICKET_END_TIME;
			action.payload = { clientId: 'tribe' };
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketEndTime, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketEndTimeInput, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, action.payload.clientId ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketHasChanges( action.payload.clientId, true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle ticket move', () => {
			action.type = MOVE_TICKET_SUCCESS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketMove ),
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
				call( wpDispatch, 'core/block-editor' ),
			);
			expect( gen.next( wpDispatchCoreEditor ).value ).toEqual(
				call( wpSelect, 'core/block-editor' ),
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
								return JSON.stringify( [ {
									id: 10,
									type: 'tec_tickets'
								} ] );
							default:
								return defaultValue;
						}
					},
				},
			};

			const gen = cloneableGenerator( sagas.setTicketsInitialState )( action );
			expect( gen.next().value ).toEqual(
				{
					id: 10,
					type: 'tec_tickets',
				},
			);

			const clone1 = gen.clone();

			expect( clone1.next().value ).toEqual(
				select( selectors.getTicketsIdsInBlocks ),
			);

			expect( clone1.next( [] ).value ).toEqual(
				call( sagas.createMissingTicketBlocks, [ 10 ] ),
			);
			expect( clone1.next().value ).toEqual(
				all( [
					put( actions.setTicketsSharedCapacity( SHARED_CAPACITY ) ),
					put( actions.setTicketsTempSharedCapacity( SHARED_CAPACITY ) ),
				] ),
			);
			expect( clone1.next().value ).toEqual(
				put( actions.fetchTicketsHeaderImage( HEADER ) ),
			);
			expect( clone1.next().value ).toEqual(
				put( actions.setTicketsProvider( PROVIDER ) ),
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();

			expect( clone2.next().value ).toEqual(
				select( selectors.getTicketsIdsInBlocks ),
			);

			expect( clone2.next().value ).toEqual(
				call( sagas.createMissingTicketBlocks, [ 10 ] ),
			);

			expect( clone2.next().value ).toEqual(
				all( [
					put( actions.setTicketsSharedCapacity( SHARED_CAPACITY ) ),
					put( actions.setTicketsTempSharedCapacity( SHARED_CAPACITY ) ),
				] ),
			);
			expect( clone2.next().value ).toEqual(
				put( actions.fetchTicketsHeaderImage( HEADER ) ),
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketsProvider( PROVIDER ) ),
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
								return JSON.stringify( [ {
									id: 10,
									type: 'tec_tickets'
								} ] );
							default:
								return defaultValue;
						}
					},
				},
			};
			const gen = cloneableGenerator( sagas.setTicketsInitialState )( action );

			expect( gen.next().value ).toEqual(
				{
					id: 10,
					type: 'tec_tickets'
				},
			);

			const clone1 = gen.clone();

			expect( clone1.next().value ).toEqual(
				select( selectors.getTicketsIdsInBlocks ),
			);

			expect( clone1.next( [] ).value ).toEqual(
				call( sagas.createMissingTicketBlocks, [ 10 ] ),
			);
			expect( clone1.next().value ).toEqual(
				select( selectors.getDefaultTicketProvider ),
			);
			expect( clone1.next( DEFAULT_PROVIDER ).value ).toEqual(
				put( actions.setTicketsProvider( DEFAULT_PROVIDER ) ),
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();

			expect( clone2.next().value ).toEqual(
				select( selectors.getTicketsIdsInBlocks ),
			);

			expect( clone2.next().value ).toEqual(
				call( sagas.createMissingTicketBlocks, [ 10 ] ),
			);

			expect( clone2.next().value ).toEqual(
				select( selectors.getDefaultTicketProvider ),
			);

			expect( clone2.next( DEFAULT_PROVIDER ).value ).toEqual(
				put( actions.setTicketsProvider( DEFAULT_PROVIDER ) ),
			);
			expect( clone2.next().done ).toEqual( true );
		} );
	} );

	describe( 'resetTicketsBlock', () => {
		it( 'should reset tickets block', () => {
			const gen = sagas.resetTicketsBlock();
			expect( gen.next().value ).toEqual(
				select( selectors.hasCreatedTickets ),
			);
			expect( gen.next( false ).value ).toEqual(
				all( [
					put( actions.removeTicketBlocks() ),
					put( actions.setTicketsIsSettingsOpen( false ) ),
				] ),
			);
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( {} ).value ).toMatchSnapshot();
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setTicketsSharedCapacity( '' ) ),
					put( actions.setTicketsTempSharedCapacity( '' ) ),
				] ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not reset tickets block', () => {
			const gen = sagas.resetTicketsBlock();
			expect( gen.next().value ).toEqual(
				select( selectors.hasCreatedTickets ),
			);
			expect( gen.next( true ).value ).toEqual(
				all( [
					put( actions.removeTicketBlocks() ),
					put( actions.setTicketsIsSettingsOpen( false ) ),
				] ),
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
		global.window = global.window || {};
		window.tec = {
			events: {
				app: {
					main: {
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
				},
			},
		};
	} );

	afterEach( () => {
		delete window.tec;
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
				call( momentUtil.toMoment, publishDate ),
			);
			expect( gen.next( startMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment ),
			);
			expect( gen.next( startDate ).value ).toEqual(
				call( momentUtil.toDate, startMoment ),
			);
			expect( gen.next( startDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment ),
			);
			expect( gen.next( startTime ).value ).toEqual(
				call( momentUtil.toTime, startMoment ),
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType ),
			);
			expect( gen.next( true ).value ).toEqual(
				select( window.tec.events.app.main.data.blocks.datetime.selectors.getStart ),
			);
			expect( gen.next( eventStart ).value ).toEqual(
				call( momentUtil.toMoment, eventStart ),
			);
			expect( gen.next( endMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment ),
			);
			expect( gen.next( endDate ).value ).toEqual(
				call( momentUtil.toDate, endMoment ),
			);
			expect( gen.next( endDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment ),
			);
			expect( gen.next( endTime ).value ).toEqual(
				call( momentUtil.toTime, endMoment ),
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				select( plugins.selectors.hasPlugin, plugins.constants.TICKETS_PLUS ),
			);
			expect( gen.next( false ).value ).toEqual(
				select( selectors.getTicketsSharedCapacity ),
			);

			const clone1 = gen.clone();
			const blankSharedCapacity = '';

			expect( clone1.next( blankSharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketId( CLIENT_ID, TICKET_ID ) ),
					call( sagas.fetchTicket, { payload: { clientId: CLIENT_ID, ticketId: TICKET_ID } } ),
				] ),
			);
			expect( clone1.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID ),
			);
			expect( clone1.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID ),
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const sharedCapacity = '100';

			expect( clone2.next( sharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketCapacity( CLIENT_ID, sharedCapacity ) ),
					put( actions.setTicketTempCapacity( CLIENT_ID, sharedCapacity ) ),
				] ),
			);
			expect( clone2.next().value ).toEqual(
				all( [
					put( actions.setTicketId( CLIENT_ID, TICKET_ID ) ),
					call( sagas.fetchTicket, { payload: { clientId: CLIENT_ID, ticketId: TICKET_ID } } ),
				] ),
			);
			expect( clone2.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID ),
			);
			expect( clone2.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID ),
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
			window.tec.events.app.main.data.blocks.datetime.selectors.getStart = jest.fn();

			const gen = cloneableGenerator( sagas.setTicketInitialState )( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( publishDate ).value ).toEqual(
				call( momentUtil.toMoment, publishDate ),
			);
			expect( gen.next( startMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment ),
			);
			expect( gen.next( startDate ).value ).toEqual(
				call( momentUtil.toDate, startMoment ),
			);
			expect( gen.next( startDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment ),
			);
			expect( gen.next( startTime ).value ).toEqual(
				call( momentUtil.toTime, startMoment ),
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType ),
			);
			expect( gen.next( true ).value ).toEqual(
				select( window.tec.events.app.main.data.blocks.datetime.selectors.getStart ),
			);
			expect( gen.next( eventStart ).value ).toEqual(
				call( momentUtil.toMoment, eventStart ),
			);
			expect( gen.next( endMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment ),
			);
			expect( gen.next( endDate ).value ).toEqual(
				call( momentUtil.toDate, endMoment ),
			);
			expect( gen.next( endDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment ),
			);
			expect( gen.next( endTime ).value ).toEqual(
				call( momentUtil.toTime, endMoment ),
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				select( plugins.selectors.hasPlugin, plugins.constants.TICKETS_PLUS ),
			);
			expect( gen.next( false ).value ).toEqual(
				select( selectors.getTicketsSharedCapacity ),
			);

			const clone1 = gen.clone();
			const blankSharedCapacity = '';

			expect( clone1.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID ),
			);
			expect( clone1.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID ),
			);
			expect( clone1.next( blankSharedCapacity ).done ).toEqual( true );

			const clone2 = gen.clone();
			const sharedCapacity = '100';

			expect( clone2.next( sharedCapacity ).value ).toEqual(
				all( [
					put( actions.setTicketCapacity( CLIENT_ID, sharedCapacity ) ),
					put( actions.setTicketTempCapacity( CLIENT_ID, sharedCapacity ) ),
				] ),
			);
			expect( clone2.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID ),
			);
			expect( clone2.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID ),
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
			window.tec.events.app.main.data.blocks.datetime.selectors.getStart = jest.fn();

			const gen = cloneableGenerator( sagas.setTicketInitialState )( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( publishDate ).value ).toEqual(
				call( momentUtil.toMoment, publishDate ),
			);
			expect( gen.next( startMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment ),
			);
			expect( gen.next( startDate ).value ).toEqual(
				call( momentUtil.toDate, startMoment ),
			);
			expect( gen.next( startDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment ),
			);
			expect( gen.next( startTime ).value ).toEqual(
				call( momentUtil.toTime, startMoment ),
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType ),
			);
			expect( gen.next( true ).value ).toEqual(
				select( window.tec.events.app.main.data.blocks.datetime.selectors.getStart ),
			);
			expect( gen.next( eventStart ).value ).toEqual(
				call( momentUtil.toMoment, eventStart ),
			);
			expect( gen.next( endMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment ),
			);
			expect( gen.next( endDate ).value ).toEqual(
				call( momentUtil.toDate, endMoment ),
			);
			expect( gen.next( endDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment ),
			);
			expect( gen.next( endTime ).value ).toEqual(
				call( momentUtil.toTime, endMoment ),
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				select( plugins.selectors.hasPlugin, plugins.constants.TICKETS_PLUS ),
			);
			expect( gen.next( true ).value ).toEqual(
				all( [
					put( actions.setTicketCapacityType(
						CLIENT_ID,
						constants.TICKET_TYPES[ constants.SHARED ],
					) ),
					put( actions.setTicketTempCapacityType(
						CLIENT_ID,
						constants.TICKET_TYPES[ constants.SHARED ],
					) ),
				] ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsSharedCapacity ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleTicketDurationError, CLIENT_ID ),
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, CLIENT_ID ),
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
				select( selectors.getTicketProvider, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketsProvider ),
			);
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				select( selectors.getTicketTempTitle, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempDescription, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempPrice, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempStartDate, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempStartTime, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempEndDate, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempEndTime, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempSku, props ),
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getTicketTempIACSetting, props ),
			);
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( menuOrder ).value ).toEqual(
				select( selectors.getTicketTempCapacityType, props ),
			);

			const clone1 = gen.clone();
			const sharedCapacityType = TICKET_TYPES[ SHARED ];

			expect( clone1.next( sharedCapacityType ).value ).toEqual(
				select( selectors.getTicketTempCapacity, props ),
			);
			expect( clone1.next().value ).toEqual(
				select( selectors.getTicketsTempSharedCapacity ),
			);

			expect( clone1.next().value ).toEqual(
				select( selectors.showSalePrice, props ),
			);

			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			const independentCapacityType = TICKET_TYPES[ INDEPENDENT ];

			expect( clone2.next( independentCapacityType ).value ).toEqual(
				select( selectors.getTicketTempCapacity, props ),
			);

			expect( clone2.next().value ).toEqual(
				select( selectors.showSalePrice, props ),
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

			const apiResponse = {
				response: {
					ok: true,
					status: 200,
				},
				data: {
					provider: 'provider',
					title: 'title',
					description: 'description',
					status: 'draft',
					cost_details: {
						values: [ 10 ],
						currency_symbol: 'R',
						currency_position: 'suffix',
					},
					sku: '',
					iac: 'none',
					available_from: '2018-11-09 19:48:42',
					available_until: '2018-11-12 19:48:42',
					capacity_type: 'unlimited',
					capacity: 0,
					supports_attendee_information: true,
					totals: {
						sold: 0,
						stock: 0,
					},
					type: 'default',
					on_sale: true,
					sale_price_data: {
						enabled: true,
						sale_price: '15',
						start_date: '2018-11-09 19:48:42',
						end_date: '2018-11-12 19:48:42',
					},
					attendee_information_fields: null,
				},
			};

			// First convert the dates
			expect( gen.next( apiResponse ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse.data.available_from )
			);

			const startMoment = momentUtil.toMoment( apiResponse.data.available_from );

			expect( gen.next( startMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, startMoment )
			);

			const startDate = momentUtil.toDatabaseDate( startMoment );

			expect( gen.next( startDate ).value ).toEqual(
				call( momentUtil.toDate, startMoment )
			);

			const startDateInput = momentUtil.toDate( startMoment );

			expect( gen.next( startDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, startMoment )
			);

			const startTime = momentUtil.toDatabaseTime( startMoment );

			expect( gen.next( startTime ).value ).toEqual(
				call( momentUtil.toTime, startMoment )
			);

			const startTimeInput = momentUtil.toTime( startMoment );

			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse.data.available_until )
			);

			const endMoment = momentUtil.toMoment( apiResponse.data.available_until );

			expect( gen.next( endMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment )
			);

			const endDate = momentUtil.toDatabaseDate( endMoment );

			expect( gen.next( endDate ).value ).toEqual(
				call( momentUtil.toDate, endMoment )
			);

			const endDateInput = momentUtil.toDate( endMoment );

			expect( gen.next( endDateInput ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment )
			);

			const endTime = momentUtil.toDatabaseTime( endMoment );

			expect( gen.next( endTime ).value ).toEqual(
				call( momentUtil.toTime, endMoment )
			);

			const endTimeInput = momentUtil.toTime( endMoment );

			// The saga processes available_until a second time if it exists
			expect( gen.next( endTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse.data.available_until )
			);

			const endMoment2 = momentUtil.toMoment( apiResponse.data.available_until );

			expect( gen.next( endMoment2 ).value ).toEqual(
				call( momentUtil.toDatabaseDate, endMoment2 )
			);

			const endDate2 = momentUtil.toDatabaseDate( endMoment2 );

			expect( gen.next( endDate2 ).value ).toEqual(
				call( momentUtil.toDate, endMoment2 )
			);

			const endDateInput2 = momentUtil.toDate( endMoment2 );

			expect( gen.next( endDateInput2 ).value ).toEqual(
				call( momentUtil.toDatabaseTime, endMoment2 )
			);

			const endTime2 = momentUtil.toDatabaseTime( endMoment2 );

			expect( gen.next( endTime2 ).value ).toEqual(
				call( momentUtil.toTime, endMoment2 )
			);

			const endTimeInput2 = momentUtil.toTime( endMoment2 );

			expect( gen.next( endTimeInput2 ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse.data.sale_price_data.start_date )
			);

			const saleStartDateMoment = momentUtil.toMoment( apiResponse.data.sale_price_data.start_date );

			expect( gen.next( saleStartDateMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, saleStartDateMoment )
			);

			const saleStartDate = momentUtil.toDatabaseDate( saleStartDateMoment );

			expect( gen.next( saleStartDate ).value ).toEqual(
				call( momentUtil.toDate, saleStartDateMoment )
			);

			const saleStartDateInput = momentUtil.toDate( saleStartDateMoment );

			expect( gen.next( saleStartDateInput ).value ).toEqual(
				call( momentUtil.toMoment, apiResponse.data.sale_price_data.end_date )
			);

			const saleEndDateMoment = momentUtil.toMoment( apiResponse.data.sale_price_data.end_date );

			expect( gen.next( saleEndDateMoment ).value ).toEqual(
				call( momentUtil.toDatabaseDate, saleEndDateMoment )
			);

			const saleEndDate = momentUtil.toDatabaseDate( saleEndDateMoment );

			expect( gen.next( saleEndDate ).value ).toEqual(
				call( momentUtil.toDate, saleEndDateMoment )
			);

			const saleEndDateInput = momentUtil.toDate( saleEndDateMoment );

			const details = {
				title: apiResponse.data.title,
				description: apiResponse.data.description,
				price: apiResponse.data.cost_details.values[ 0 ],
				sku: apiResponse.data.sku,
				iac: apiResponse.data.iac,
				startDate,
				startDateInput,
				startDateMoment: startMoment,
				endDate,
				endDateInput,
				endDateMoment: endMoment,
				startTime,
				endTime,
				startTimeInput,
				endTimeInput,
				capacityType: apiResponse.data.capacity_type,
				capacity: apiResponse.data.capacity,
				attendeeInfoFields: apiResponse.data.attendee_information_fields,
				type: apiResponse.data.type,
				on_sale: apiResponse.data.on_sale,
				salePriceChecked: apiResponse.data.sale_price_data.enabled,
				salePrice: apiResponse.data.sale_price_data.sale_price,
				saleStartDate,
				saleStartDateInput,
				saleStartDateMoment,
				saleEndDate,
				saleEndDateInput,
				saleEndDateMoment,
			};

			expect( gen.next( saleEndDateInput ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( CLIENT_ID, details ) ),
					put( actions.setTicketTempDetails( CLIENT_ID, details ) ),
					put( actions.setTicketSold( CLIENT_ID, apiResponse.data.totals.sold ) ),
					put( actions.setTicketAvailable( CLIENT_ID, apiResponse.data.totals.stock ) ),
					put( actions.setTicketCurrencySymbol( CLIENT_ID, apiResponse.data.cost_details.currency_symbol ) ),
					put( actions.setTicketCurrencyPosition( CLIENT_ID, apiResponse.data.cost_details.currency_position ) ),
					put( actions.setTicketProvider( CLIENT_ID, apiResponse.data.provider ) ),
					put( actions.setTicketHasAttendeeInfoFields( CLIENT_ID, apiResponse.data.supports_attendee_information ) ),
					put( actions.setTicketHasBeenCreated( CLIENT_ID, true ) ),
				] )
			);

			expect( gen.next().value ).toEqual(
				doAction( 'tec.tickets.blocks.fetchTicket', CLIENT_ID, apiResponse.data, details )
			);

			expect( gen.next().value ).toEqual(
				put( actions.setTicketIsLoading( CLIENT_ID, false ) )
			);

			expect( gen.next().done ).toEqual( true );
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
		it( 'should create a new ticket', () => {
			const action = {
				payload: {
					clientId: 'tribe',
					details: {
						title: 'title',
						description: 'description',
						price: 10,
						sku: 'sku',
						startDate: '2018-01-01',
						endDate: '2018-01-02',
						startTime: '10:00',
						endTime: '12:00',
						capacityType: 'unlimited',
						capacity: 100,
					},
				},
			};
			const mockFormData = {
				append: jest.fn(),
			};
			const gen = sagas.createNewTicket( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setBodyDetails, action.payload.clientId ),
			);
			expect( gen.next( mockFormData ).value ).toEqual(
				put( actions.setTicketIsLoading( action.payload.clientId, true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: 'tickets/',
					namespace: 'tribe/tickets/v1',
					initParams: {
						method: 'POST',
						body: mockFormData,
					},
				} ),
			);

			const response = {
				response: {
					ok: true,
				},
				data: {
					id: 123,
					capacity_details: {
						available: 100,
					},
					provider_class: WOO_CLASS,
					sale_price_data: {
						enabled: true,
						sale_price: '15',
					},
				},
			};

			expect( gen.next( response ).value ).toEqual(
				select( selectors.getTicketsSharedCapacity ),
			);

			expect( gen.next( '' ).value ).toEqual(
				select( selectors.getTicketsTempSharedCapacity ),
			);

			expect( gen.next( 0 ).value ).toEqual(
				all( [
					select( selectors.getTicketTempTitle, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempDescription, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempPrice, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempSku, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempIACSetting, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempStartDate, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempStartDateInput, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempStartDateMoment, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempEndDate, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempEndDateInput, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempEndDateMoment, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempStartTime, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempEndTime, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempStartTimeInput, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempEndTimeInput, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempCapacityType, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempCapacity, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempSaleStartDate, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempSaleStartDateInput, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempSaleStartDateMoment, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempSaleEndDate, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempSaleEndDateInput, { clientId: action.payload.clientId } ),
					select( selectors.getTicketTempSaleEndDateMoment, { clientId: action.payload.clientId } ),
				] ),
			);

			const ticketDetails = {
				title: 'title',
				description: 'description',
				price: 10,
				sku: 'sku',
				iac: undefined,
				startDate: '2018-01-01',
				startDateInput: undefined,
				startDateMoment: undefined,
				endDate: '2018-01-02',
				endDateInput: undefined,
				endDateMoment: undefined,
				startTime: '10:00',
				endTime: '12:00',
				startTimeInput: undefined,
				endTimeInput: undefined,
				capacityType: 'unlimited',
				capacity: 100,
				salePriceChecked: true,
				salePrice: '15',
				saleStartDate: undefined,
				saleStartDateInput: undefined,
				saleStartDateMoment: undefined,
				saleEndDate: undefined,
				saleEndDateInput: undefined,
				saleEndDateMoment: undefined,
			};

			expect( gen.next( [
				'title',
				'description',
				10,
				'sku',
				undefined,
				'2018-01-01',
				undefined,
				undefined,
				'2018-01-02',
				undefined,
				undefined,
				'10:00',
				'12:00',
				undefined,
				undefined,
				'unlimited',
				100,
				undefined,
				undefined,
				undefined,
				undefined,
				undefined,
				undefined,
			] ).value ).toEqual(
				all( [
					put( actions.setTicketDetails( action.payload.clientId, ticketDetails ) ),
					put( actions.setTempSalePriceChecked( action.payload.clientId, true ) ),
					put( actions.setTempSalePrice( action.payload.clientId, '15' ) ),
					put( actions.setTicketId( action.payload.clientId, 123 ) ),
					put( actions.setTicketHasBeenCreated( action.payload.clientId, true ) ),
					put( actions.setTicketAvailable( action.payload.clientId, 100 ) ),
					put( actions.setTicketProvider( action.payload.clientId, PROVIDER_CLASS_TO_PROVIDER_MAPPING[WOO_CLASS] ) ),
					put( actions.setTicketHasChanges( action.payload.clientId, false ) ),
				] ),
			);

			expect( gen.next().value ).toEqual(
				fork( sagas.saveTicketWithPostSave, action.payload.clientId ),
			);

			expect( gen.next().value ).toEqual(
				put( actions.setTicketIsLoading( action.payload.clientId, false ) ),
			);

			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'updateTicket', () => {
		it( 'should update a ticket', () => {
			const action = {
				payload: {
					clientId: 'tribe',
					details: {
						title: 'title',
						description: 'description',
						price: 10,
						sku: 'sku',
						startDate: '2018-01-01',
						endDate: '2018-01-02',
						startTime: '10:00',
						endTime: '12:00',
						capacityType: 'unlimited',
						capacity: 100,
					},
				},
			};
			const mockFormData = {
				append: jest.fn(),
				entries: () => [],
			};
			const gen = sagas.updateTicket( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setBodyDetails, action.payload.clientId ),
			);
			expect( gen.next( mockFormData ).value ).toEqual(
				select( selectors.getTicketId, { clientId: action.payload.clientId } ),
			);
			expect( gen.next( 123 ).value ).toEqual(
				put( actions.setTicketIsLoading( action.payload.clientId, true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, {
					path: 'tickets/123',
					namespace: 'tribe/tickets/v1',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
					},
					initParams: {
						method: 'PUT',
						body: '',
					},
				} ),
			);

			const response = {
				response: {
					ok: true,
				},
				data: {
					id: 123,
				},
			};

			expect( gen.next( response ).value ).toEqual(
				put( actions.setTicketIsLoading( action.payload.clientId, false ) ),
			);
			expect( gen.next().done ).toEqual( true );
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

			expect( gen.next().value ).toEqual( call(
				[ window, 'confirm' ],
				'Are you sure you want to delete this ticket? It cannot be undone.',
			) );

			expect( gen.next( true ).value ).toEqual(
				select( selectors.getTicketId, props ),
			);
			expect( gen.next( TICKET_ID ).value ).toEqual(
				select( selectors.getTicketHasBeenCreated, props ),
			);

			const clone1 = gen.clone();
			const hasBeenCreated1 = false;

			expect( clone1.next( hasBeenCreated1 ).value ).toEqual(
				put( actions.setTicketIsSelected( CLIENT_ID, false ) ),
			);
			expect( clone1.next().value ).toEqual(
				put( actions.removeTicketBlock( CLIENT_ID ) ),
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
				put( actions.setTicketIsSelected( CLIENT_ID, false ) ),
			);
			expect( clone2.next().value ).toEqual(
				put( actions.removeTicketBlock( CLIENT_ID ) ),
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
				} ),
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
				put( actions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( wpREST, { path: `media/${ action.payload.id }` } ),
			);

			const clone1 = gen.clone();
			const apiResponseBad = {
				response: {
					ok: false,
				},
				data: {},
			};

			expect( clone1.next( apiResponseBad ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) ),
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
				} ) ),
			);
			expect( clone2.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) ),
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
				put( actions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) ),
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
				} ),
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
				put( actions.setTicketsHeaderImage( headerImage ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPHeaderImage( headerImage ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) ),
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
				put( actions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) ),
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
				} ),
			);

			const apiResponse = {
				response: {
					ok: false,
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) ),
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
				put( actions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) ),
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
				} ),
			);

			const apiResponse = {
				response: {
					ok: true,
				},
			};

			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setTicketsHeaderImage( TICKET_HEADER_IMAGE_DEFAULT_STATE ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPHeaderImage( RSVP_HEADER_IMAGE_DEFAULT_STATE ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not delete tickets header image', () => {
			const postId = 10;

			const gen = sagas.deleteTicketsHeaderImage();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( true ) ),
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
				} ),
			);

			const apiResponse = {
				response: {
					ok: false,
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( rsvpActions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setTicketDetails', () => {
		it( 'should set ticket details', () => {
			const action = {
				payload: {
					clientId: 'tribe',
					details: {
						title: 'title',
						description: 'description',
						price: 10,
						sku: 'sku',
						startDate: '2018-01-01',
						endDate: '2018-01-02',
						startTime: '10:00',
						endTime: '12:00',
						capacityType: 'unlimited',
						capacity: 100,
						startDateMoment: undefined,
						endDateMoment: undefined,
					},
				},
			};
			const gen = sagas.setTicketDetails( action );
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setTicketAttendeeInfoFields( action.payload.clientId, undefined ) ),
					put( actions.setTicketTitle( action.payload.clientId, action.payload.details.title ) ),
					put( actions.setTicketDescription( action.payload.clientId, action.payload.details.description ) ),
					put( actions.setTicketPrice( action.payload.clientId, action.payload.details.price ) ),
					put( actions.setTicketOnSale( action.payload.clientId, undefined ) ),
					put( actions.setTicketSku( action.payload.clientId, action.payload.details.sku ) ),
					put( actions.setTicketIACSetting( action.payload.clientId, undefined ) ),
					put( actions.setTicketStartDate( action.payload.clientId, action.payload.details.startDate ) ),
					put( actions.setTicketStartDateInput( action.payload.clientId, undefined ) ),
					put( actions.setTicketStartDateMoment( action.payload.clientId, action.payload.details.startDateMoment ) ),
					put( actions.setTicketEndDate( action.payload.clientId, action.payload.details.endDate ) ),
					put( actions.setTicketEndDateInput( action.payload.clientId, undefined ) ),
					put( actions.setTicketEndDateMoment( action.payload.clientId, action.payload.details.endDateMoment ) ),
					put( actions.setTicketStartTime( action.payload.clientId, action.payload.details.startTime ) ),
					put( actions.setTicketEndTime( action.payload.clientId, action.payload.details.endTime ) ),
					put( actions.setTicketStartTimeInput( action.payload.clientId, undefined ) ),
					put( actions.setTicketEndTimeInput( action.payload.clientId, undefined ) ),
					put( actions.setTicketCapacityType( action.payload.clientId, action.payload.details.capacityType ) ),
					put( actions.setTicketCapacity( action.payload.clientId, action.payload.details.capacity ) ),
					put( actions.setTicketType( action.payload.clientId, undefined ) ),
					put( actions.setSalePriceChecked( action.payload.clientId, undefined ) ),
					put( actions.setSalePrice( action.payload.clientId, undefined ) ),
					put( actions.setTicketSaleStartDate( action.payload.clientId, undefined ) ),
					put( actions.setTicketSaleStartDateInput( action.payload.clientId, undefined ) ),
					put( actions.setTicketSaleStartDateMoment( action.payload.clientId, undefined ) ),
					put( actions.setTicketSaleEndDate( action.payload.clientId, undefined ) ),
					put( actions.setTicketSaleEndDateInput( action.payload.clientId, undefined ) ),
					put( actions.setTicketSaleEndDateMoment( action.payload.clientId, undefined ) ),
				] ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );
} );
