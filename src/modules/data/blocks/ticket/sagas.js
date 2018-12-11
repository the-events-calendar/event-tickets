/**
 * External Dependencies
 */
import { put, all, select, takeEvery, call, fork, take, cancel } from 'redux-saga/effects';
import { includes } from 'lodash';

/**
 * Wordpress dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import * as constants from './constants';
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import { DEFAULT_STATE } from './reducer';
import {
	DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE
} from './reducers/header-image';
import {
	DEFAULT_STATE as TICKET_DEFAULT_STATE,
} from './reducers/tickets/ticket';
import * as utils from '@moderntribe/tickets/data/utils';
import {
	api,
	globals,
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';
import { isTribeEventPostType, createWPEditorSavingChannel, hasPostTypeChannel, createDates } from '@moderntribe/tickets/data/shared/sagas';


const {
	UNLIMITED,
	SHARED,
	TICKET_TYPES,
	PROVIDER_CLASS_TO_PROVIDER_MAPPING,
} = constants;
const {
	tickets: ticketsConfig,
	restNonce,
	tecDateSettings,
} = globals;
const { wpREST } = api;

export function* createMissingTicketBlocks( tickets ) {
	const { insertBlock } = yield call( wpDispatch, 'core/editor' );
	const { getBlockCount, getBlocks } = yield call( wpSelect, 'core/editor' );
	const ticketsBlocks = yield call( [ getBlocks(), 'filter' ], ( block ) => block.name === 'tribe/tickets' );

	ticketsBlocks.forEach( ( { clientId } ) => {
		tickets.forEach( ( ticketId ) => {
			const attributes = {
				hasBeenCreated: true,
				ticketId,
			};
			const nextChildPosition = getBlockCount( clientId );
			const block = createBlock( 'tribe/tickets-item', attributes );
			insertBlock( block, nextChildPosition, clientId );
		} );
	} );
}

export function* setTicketsInitialState( action ) {
	const { get } = action.payload;

	const header = parseInt( get( 'header', HEADER_IMAGE_DEFAULT_STATE.id ), 10 );
	const sharedCapacity = get( 'sharedCapacity' );
	const ticketsList = get( 'tickets', [] );
	const ticketsInBlock = yield select( selectors.getTicketsIdsInBlocks );
	// Get only the IDs of the tickets that are not in the block list already
	const ticketsDiff = ticketsList.filter( ( item ) => ! includes( ticketsInBlock, item ) );

	if ( ticketsDiff.length >= 1 ) {
		yield call( createMissingTicketBlocks, ticketsDiff );
	}

	// Meta value is '0' however fields use empty string as default
	if ( sharedCapacity !== '0' ) {
		yield all( [
			put( actions.setTicketsSharedCapacity( sharedCapacity ) ),
			put( actions.setTicketsTempSharedCapacity( sharedCapacity ) ),
		] );
	}

	if ( ! isNaN( header ) && header !== 0 ) {
		yield put( actions.fetchTicketsHeaderImage( header ) );
	}

	const tickets = ticketsConfig();
	const defaultProvider = tickets.default_provider || '';
	const provider = get( 'provider', DEFAULT_STATE.provider );
	yield put( actions.setTicketsProvider( provider || defaultProvider ) );
}

export function* setTicketInitialState( action ) {
	const { clientId, get } = action.payload;
	const ticketId = get( 'ticketId', TICKET_DEFAULT_STATE.ticketId );
	const hasBeenCreated = get( 'hasBeenCreated', TICKET_DEFAULT_STATE.hasBeenCreated );

	const datePickerFormat = tecDateSettings().datepickerFormat
	const publishDate = yield call( [ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ], 'date' );
	const startMoment = yield call( momentUtil.toMoment, publishDate );
	const startDate = yield call( momentUtil.toDatabaseDate, startMoment );
	const startDateInput = yield datePickerFormat
		? call( momentUtil.toDate, startMoment, datePickerFormat )
		: call( momentUtil.toDate, startMoment );
	const startTime = yield call( momentUtil.toDatabaseTime, startMoment );
	const startTimeInput = yield call( momentUtil.toTime, startMoment );

	yield all( [
		put( actions.setTicketStartDate( clientId, startDate ) ),
		put( actions.setTicketStartDateInput( clientId, startDateInput ) ),
		put( actions.setTicketStartDateMoment( clientId, startMoment ) ),
		put( actions.setTicketStartTime( clientId, startTime ) ),
		put( actions.setTicketStartTimeInput( clientId, startTimeInput ) ),
		put( actions.setTicketTempStartDate( clientId, startDate ) ),
		put( actions.setTicketTempStartDateInput( clientId, startDateInput ) ),
		put( actions.setTicketTempStartDateMoment( clientId, startMoment ) ),
		put( actions.setTicketTempStartTime( clientId, startTime ) ),
		put( actions.setTicketTempStartTimeInput( clientId, startTimeInput ) ),
		put( actions.setTicketHasBeenCreated( clientId, hasBeenCreated ) ),
	] );

	try {
		// NOTE: This requires TEC to be installed, if not installed, do not set an end date
		const eventStart = yield select( tribe.events.data.blocks.datetime.selectors.getStart ); // Ticket purchase window should end when event starts
		const endMoment = yield call( momentUtil.toMoment, eventStart );
		const endDate = yield call( momentUtil.toDatabaseDate, endMoment );
		const endDateInput = yield datePickerFormat
			? call( momentUtil.toDate, endMoment, datePickerFormat )
			: call( momentUtil.toDate, endMoment );
		const endTime = yield call( momentUtil.toDatabaseTime, endMoment );
		const endTimeInput = yield call( momentUtil.toTime, endMoment );

		yield all( [
			put( actions.setTicketEndDate( clientId, endDate ) ),
			put( actions.setTicketEndDateInput( clientId, endDateInput ) ),
			put( actions.setTicketEndDateMoment( clientId, endMoment ) ),
			put( actions.setTicketEndTime( clientId, endTime ) ),
			put( actions.setTicketEndTimeInput( clientId, endTimeInput ) ),
			put( actions.setTicketTempEndDate( clientId, endDate ) ),
			put( actions.setTicketTempEndDateInput( clientId, endDateInput ) ),
			put( actions.setTicketTempEndDateMoment( clientId, endMoment ) ),
			put( actions.setTicketTempEndTime( clientId, endTime ) ),
			put( actions.setTicketTempEndTimeInput( clientId, endTimeInput ) ),
		] );
	} catch ( err ) {
		console.error( err );
		// ¯\_(ツ)_/¯
	}

	const sharedCapacity = yield select( selectors.getTicketsSharedCapacity );
	if ( sharedCapacity ) {
		yield all( [
			put( actions.setTicketCapacity( clientId, sharedCapacity ) ),
			put( actions.setTicketTempCapacity( clientId, sharedCapacity ) ),
		] );
	}

	if ( ticketId !== 0 ) {
		yield all( [
			put( actions.setTicketId( clientId, ticketId ) ),
			put( actions.fetchTicket( clientId, ticketId ) ),
		] );
	}
}

export function* setBodyDetails( blockId ) {
	const body = new FormData();
	const props = { blockId };
	const ticketProvider = yield select( selectors.getTicketProvider, props );
	const ticketsProvider = yield select( selectors.getTicketsProvider );

	body.append( 'post_id', wpSelect( 'core/editor' ).getCurrentPostId() );
	body.append( 'provider', ticketProvider || ticketsProvider );
	body.append( 'name', yield select( selectors.getTicketTempTitle, props ) );
	body.append( 'description', yield select( selectors.getTicketTempDescription, props ) );
	body.append( 'price', yield select( selectors.getTicketTempPrice, props ) );
	body.append( 'start_date', yield select( selectors.getTicketTempStartDate, props ) );
	body.append( 'start_time', yield select( selectors.getTicketTempStartTime, props ) );
	body.append( 'end_date', yield select( selectors.getTicketTempEndDate, props ) );
	body.append( 'end_time', yield select( selectors.getTicketTempEndTime, props ) );
	body.append( 'sku', yield select( selectors.getTicketTempSku, props ) );

	const capacityType = yield select( selectors.getTicketTempCapacityType, props );
	const capacity = yield select( selectors.getTicketTempCapacity, props );

	const isUnlimited = capacityType === TICKET_TYPES[ UNLIMITED ];
	body.append( 'ticket[mode]', isUnlimited ? '' : capacityType );
	body.append( 'ticket[capacity]', isUnlimited ? '' : capacity );

	if ( capacityType === TICKET_TYPES[ SHARED ] ) {
		body.append( 'ticket[event_capacity]', yield select( selectors.getTicketsTempSharedCapacity ) );
	}

	return body;
}

export function* removeTicketBlock( blockId ) {
	const { removeBlock } = wpDispatch( 'core/editor' );

	yield all( [
		put( actions.removeTicketBlock( blockId ) ),
		call( removeBlock, blockId ),
	] );
}

export function* fetchTicket( action ) {
	const { ticketId, blockId } = action.payload;

	if ( ticketId === 0 ) {
		return;
	}

	yield put( actions.setTicketIsLoading( blockId, true ) );

	try {
		const { response, data: ticket } = yield call( wpREST, {
			path: `tickets/${ ticketId }`,
			namespace: 'tribe/tickets/v1',
		} );

		const { status = '', provider } = ticket;

		if ( response.status === 404 || status === 'trash' || provider === constants.RSVP ) {
			yield call( removeTicketBlock, blockId );
			return;
		}

		if ( response.ok ) {
			const {
				totals = {},
				available_from,
				available_until,
				cost_details,
				title,
				description,
				sku,
				capacity_type,
				capacity,
			} = ticket;

			const datePickerFormat = tecDateSettings().datepickerFormat;

			const startMoment = yield call( momentUtil.toMoment, available_from );
			const startDate = yield call( momentUtil.toDatabaseDate, startMoment );
			const startDateInput = yield datePickerFormat
				? call( momentUtil.toDate, startMoment, datePickerFormat )
				: call( momentUtil.toDate, startMoment );
			const startTime = yield call( momentUtil.toDatabaseTime, startMoment );
			const startTimeInput = yield call( momentUtil.toTime, startMoment );

			let endMoment = yield call( momentUtil.toMoment, '' );
			let endDate = '';
			let endDateInput = '';
			let endTime = '';
			let endTimeInput = '';

			if ( available_until ) {
				endMoment = yield call( momentUtil.toMoment, available_until );
				endDate = yield call( momentUtil.toDatabaseDate, endMoment );
				endDateInput = yield datePickerFormat
					? call( momentUtil.toDate, endMoment, datePickerFormat )
					: call( momentUtil.toDate, endMoment );
				endTime = yield call( momentUtil.toDatabaseTime, endMoment );
				endTimeInput = yield call( momentUtil.toTime, endMoment );
			}

			const details = {
				title,
				description,
				price: cost_details.values[ 0 ],
				sku,
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
				capacityType: capacity_type,
				capacity,
			};

			yield all( [
				put( actions.setTicketDetails( blockId, details ) ),
				put( actions.setTicketTempDetails( blockId, details ) ),
				put( actions.setTicketSold( blockId, totals.sold ) ),
				put( actions.setTicketAvailable( blockId, totals.stock ) ),
				put( actions.setTicketCurrencySymbol( blockId, cost_details.currency_symbol ) ),
				put( actions.setTicketCurrencyPosition( blockId, cost_details.currency_position ) ),
				put( actions.setTicketProvider( blockId, provider ) ),
				put( actions.setTicketHasBeenCreated( blockId, true ) ),
			] );
		}
	} catch ( e ) {
		console.error( e ) ;
		/**
		 * @todo handle error scenario
		 */
	}

	yield put( actions.setTicketIsLoading( blockId, false ) );
}

export function* createNewTicket( action ) {
	const { blockId } = action.payload;
	const props = { blockId };

	const { add_ticket_nonce = '' } = restNonce();
	const body = yield call( setBodyDetails, blockId );
	body.append( 'add_ticket_nonce', add_ticket_nonce );

	try {
		yield put( actions.setTicketIsLoading( blockId, true ) );
		const { response, data: ticket } = yield call( wpREST, {
			path: 'tickets/',
			namespace: 'tribe/tickets/v1',
			initParams: {
				method: 'POST',
				body,
			},
		} );

		if ( response.ok ) {
			const sharedCapacity = yield select( selectors.getTicketsSharedCapacity );
			const tempSharedCapacity = yield select( selectors.getTicketsTempSharedCapacity );
			if (
				sharedCapacity === ''
					&& ( ! isNaN( tempSharedCapacity ) && tempSharedCapacity > 0 )
			) {
				yield put( actions.setTicketsSharedCapacity( tempSharedCapacity ) );
			}

			const [
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
			] = yield all( [
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
			] );

			yield all( [
				put( actions.setTicketDetails( blockId, {
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
				put( actions.setTicketId( blockId, ticket.ID ) ),
				put( actions.setTicketHasBeenCreated( blockId, true ) ),
				put( actions.setTicketAvailable( blockId, ticket.capacity ) ),
				put( actions.setTicketProvider( blockId, PROVIDER_CLASS_TO_PROVIDER_MAPPING[ ticket.provider_class ] ) ),
				put( actions.setTicketHasChanges( blockId, false ) ),
			] );
		}
	} catch ( e ) {
		console.error( e );
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketIsLoading( blockId, false ) );
	}
}

export function* updateTicket( action ) {
	const { blockId } = action.payload;
	const props = { blockId };

	const { edit_ticket_nonce = '' } = restNonce();
	const body = yield call( setBodyDetails, blockId );
	body.append( 'edit_ticket_nonce', edit_ticket_nonce );

	const ticketId = yield select( selectors.getTicketId, props );

	try {
		const data = [];
		for ( const [ key, value ] of body.entries() ) {
			data.push( `${ encodeURIComponent( key ) }=${ encodeURIComponent( value ) }` );
		}

		yield put( actions.setTicketIsLoading( blockId, true ) );
		const { response } = yield call( wpREST, {
			path: `tickets/${ ticketId }`,
			namespace: 'tribe/tickets/v1',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
			},
			initParams: {
				method: 'PUT',
				body: data.join( '&' ),
			},
		} );

		if ( response.ok ) {
			const [
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
			] = yield all( [
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
			] );

			yield all( [
				put( actions.setTicketDetails( blockId, {
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
				put( actions.setTicketHasChanges( blockId, false ) ),
			] );
		}
	} catch ( e ) {
		console.error( e );
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketIsLoading( blockId, false ) );
	}
}

export function* deleteTicket( action ) {
	const { blockId } = action.payload;
	const props = { blockId };

	const shouldDelete = yield call( [ window, 'confirm' ], __( 'Are you sure you want to delete this ticket? It cannot be undone.' ) );

	if ( shouldDelete ) {
		const ticketId = yield select( selectors.getTicketId, props );
		const hasBeenCreated = yield select( selectors.getTicketHasBeenCreated, props );

		yield put( actions.setTicketIsSelected( blockId, false ) );
		yield put( actions.removeTicketBlock( blockId ) );
		yield call( [ wpDispatch( 'core/editor' ), 'removeBlocks' ], [ blockId ] );

		if ( hasBeenCreated ) {
			const { remove_ticket_nonce = '' } = restNonce();
			const postId = wpSelect( 'core/editor' ).getCurrentPostId();

			/**
			 * Encode params to be passed into the DELETE request as PHP doesn’t transform the request body
			 * of a DELETE request into a super global.
			 */
			const body = [
				`${ encodeURIComponent( 'post_id' ) }=${ encodeURIComponent( postId ) }`,
				`${ encodeURIComponent( 'remove_ticket_nonce' ) }=${ encodeURIComponent( remove_ticket_nonce ) }`,
			];

			try {
				yield call( wpREST, {
					path: `tickets/${ ticketId }`,
					namespace: 'tribe/tickets/v1',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
					},
					initParams: {
						method: 'DELETE',
						body: body.join( '&' ),
					},
				} );
			} catch ( e ) {
				/**
				 * @todo handle error on removal
				 */
			}
		}
	}
}

export function* fetchTicketsHeaderImage( action ) {
	const { id } = action.payload;
	yield put( actions.setTicketsIsSettingsLoading( true ) );

	try {
		const { response, data: media } = yield call( wpREST, { path: `media/${ id }` } );

		if ( response.ok ) {
			const headerImage = {
				id: media.id,
				alt: media.alt_text,
				src: media.media_details.sizes.medium.source_url,
			};
			yield put( actions.setTicketsHeaderImage( headerImage ) );
		}
	} catch ( e ) {
		console.error( e );
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketsIsSettingsLoading( false ) );
	}
}

export function* updateTicketsHeaderImage( action ) {
	const { image } = action.payload;
	const postId = wpSelect( 'core/editor' ).getCurrentPostId();
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: `${ image.id }`,
		},
	};

	try {
		yield put( actions.setTicketsIsSettingsLoading( true ) );
		const { response } = yield call( wpREST, {
			path: `tribe_events/${ postId }`,
			headers: {
				'Content-Type': 'application/json',
			},
			initParams: {
				method: 'PUT',
				body: JSON.stringify( body ),
			},
		} );

		if ( response.ok ) {
			yield put( actions.setTicketsHeaderImage( {
				id: image.id,
				alt: image.alt,
				src: image.sizes.medium.url,
			} ) );
		}
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketsIsSettingsLoading( false ) );
	}
}

export function* deleteTicketsHeaderImage() {
	const postId = wpSelect( 'core/editor' ).getCurrentPostId();
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: null,
		},
	};

	try {
		yield put( actions.setTicketsIsSettingsLoading( true ) );
		const { response } = yield call( wpREST, {
			path: `tribe_events/${ postId }`,
			headers: {
				'Content-Type': 'application/json',
			},
			initParams: {
				method: 'PUT',
				body: JSON.stringify( body ),
			},
		} );

		if ( response.ok ) {
			yield put( actions.setTicketsHeaderImage( HEADER_IMAGE_DEFAULT_STATE ) );
		}
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketsIsSettingsLoading( false ) );
	}
}

export function* setTicketDetails( action ) {
	const { blockId, details } = action.payload;
	const {
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
	} = details;

	yield all( [
		put( actions.setTicketTitle( blockId, title ) ),
		put( actions.setTicketDescription( blockId, description ) ),
		put( actions.setTicketPrice( blockId, price ) ),
		put( actions.setTicketSku( blockId, sku ) ),
		put( actions.setTicketStartDate( blockId, startDate ) ),
		put( actions.setTicketStartDateInput( blockId, startDateInput ) ),
		put( actions.setTicketStartDateMoment( blockId, startDateMoment ) ),
		put( actions.setTicketEndDate( blockId, endDate ) ),
		put( actions.setTicketEndDateInput( blockId, endDateInput ) ),
		put( actions.setTicketEndDateMoment( blockId, endDateMoment ) ),
		put( actions.setTicketStartTime( blockId, startTime ) ),
		put( actions.setTicketEndTime( blockId, endTime ) ),
		put( actions.setTicketStartTimeInput( blockId, startTimeInput ) ),
		put( actions.setTicketEndTimeInput( blockId, endTimeInput ) ),
		put( actions.setTicketCapacityType( blockId, capacityType ) ),
		put( actions.setTicketCapacity( blockId, capacity ) ),
	] );
}

export function* setTicketTempDetails( action ) {
	const { blockId, tempDetails } = action.payload;
	const {
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
	} = tempDetails;

	yield all( [
		put( actions.setTicketTempTitle( blockId, title ) ),
		put( actions.setTicketTempDescription( blockId, description ) ),
		put( actions.setTicketTempPrice( blockId, price ) ),
		put( actions.setTicketTempSku( blockId, sku ) ),
		put( actions.setTicketTempStartDate( blockId, startDate ) ),
		put( actions.setTicketTempStartDateInput( blockId, startDateInput ) ),
		put( actions.setTicketTempStartDateMoment( blockId, startDateMoment ) ),
		put( actions.setTicketTempEndDate( blockId, endDate ) ),
		put( actions.setTicketTempEndDateInput( blockId, endDateInput ) ),
		put( actions.setTicketTempEndDateMoment( blockId, endDateMoment ) ),
		put( actions.setTicketTempStartTime( blockId, startTime ) ),
		put( actions.setTicketTempEndTime( blockId, endTime ) ),
		put( actions.setTicketTempStartTimeInput( blockId, startTimeInput ) ),
		put( actions.setTicketTempEndTimeInput( blockId, endTimeInput ) ),
		put( actions.setTicketTempCapacityType( blockId, capacityType ) ),
		put( actions.setTicketTempCapacity( blockId, capacity ) ),
	] );
}

/**
 * Allows the Ticket to be saved at the same time a post is being saved.
 * Avoids the user having to open up the Ticket block, and then click update again there, when changing the event start date.
 *
 * @export
 */
export function* saveTicketWithPostSave( blockId ) {
	let saveChannel;
	try {
		// Do nothing when not already created
		if ( yield select( selectors.getTicketHasBeenCreated, { blockId } ) ) {
			// Create channel for use
			saveChannel = yield call( createWPEditorSavingChannel );

			// Wait for channel to save
			yield take( saveChannel );

			// Update when saving
			yield call( updateTicket, { payload: { blockId } } );
		}
	} catch ( error ) {
		console.error( error );
	} finally {
		// Close channel if exists
		if ( saveChannel ) {
			yield call( [ saveChannel, 'close' ] );
		}
	}
}

/**
 * Will sync all tickets
 * @param {String} prevStartDate Previous start date before latest set date time changes
 * @export
 */
export function* syncTicketsSaleEndWithEventStart( prevStartDate ) {
	const ticketIds = yield select( selectors.getAllTicketIds );
	for (let index = 0; index < ticketIds.length; index++) {
		const blockId = ticketIds[index];
		yield call( syncTicketSaleEndWithEventStart, prevStartDate, blockId );
	}
}

/**
 * Will sync Tickets sale end to be the same as event start date and time, if field has not been manually edited
 * @borrows TEC - Functionality requires TEC to be enabled
 * @param {String} prevStartDate Previous start date before latest set date time changes
 * @export
 */
export function* syncTicketSaleEndWithEventStart(prevStartDate, blockId){
	try {
		const tempEndMoment = yield select( selectors.getTicketTempEndDateMoment, { blockId } );
		const endMoment = yield select( selectors.getTicketEndDateMoment, { blockId } );
		const { moment: prevEventStartMoment } = yield call( createDates, prevStartDate );

		// NOTE: Mutation
		// Convert to use local timezone
		yield all( [
			call( [ tempEndMoment, 'local' ] ),
			call( [ endMoment, 'local' ] ),
			call( [ prevEventStartMoment, 'local' ] ),
		] );

		// If initial end and current end are the same, the RSVP has not been modified
		const isNotManuallyEdited = yield call( [ tempEndMoment, 'isSame' ], endMoment, 'minute' );
		const isSyncedToEventStart = yield call( [ tempEndMoment, 'isSame' ], prevEventStartMoment, 'minute' );

		if ( isNotManuallyEdited && isSyncedToEventStart ) {
			const eventStart = yield select( window.tribe.events.data.blocks.datetime.selectors.getStart );
			const {
				moment: endDateMoment,
				date: endDate,
				dateInput: endDateInput,
				time: endTime,
				timeInput: endTimeInput,
			} = yield call( createDates, eventStart );

			console.warn(endDate, endDateInput, endTime, endTimeInput);

			yield all( [
				put( actions.setTicketTempEndDate( blockId, endDate ) ),
				put( actions.setTicketTempEndDateInput( blockId, endDateInput ) ),
				put( actions.setTicketTempEndDateMoment( blockId, endDateMoment ) ),
				put( actions.setTicketTempEndTime( blockId, endTime ) ),
				put( actions.setTicketTempEndTimeInput( blockId, endTimeInput ) ),

				// Sync Ticket end items as well so as not to make state 'manually edited'
				put( actions.setTicketEndDate( blockId, endDate ) ),
				put( actions.setTicketEndDateInput( blockId, endDateInput ) ),
				put( actions.setTicketEndDateMoment( blockId, endDateMoment ) ),
				put( actions.setTicketEndTime( blockId, endTime ) ),
				put( actions.setTicketEndTimeInput( blockId, endTimeInput ) ),

				// Trigger UI button
				put( actions.setTicketHasChanges( blockId, true ) ),
			] );

			yield fork( saveTicketWithPostSave, blockId );
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		console.error( error );
	}
}

/**
 * Listens for event start date and time changes after RSVP block is loaded.
 * @borrows TEC - Functionality requires TEC to be enabled and post type to be event
 * @export
 */
export function* handleEventStartDateChanges() {
	try {
		// Ensure we have a postType set before proceeding
		const postTypeChannel = yield call( hasPostTypeChannel );
		yield take( postTypeChannel );
		yield call( [ postTypeChannel, 'close' ] );

		const isEvent = yield call( isTribeEventPostType );
		if ( isEvent && window.tribe.events ) {
			const { SET_START_DATE_TIME, SET_START_TIME } = window.tribe.events.data.blocks.datetime.types;

			let syncTask;
			while ( true ) {
				// Cache current event start date for comparison
				const eventStart = yield select( window.tribe.events.data.blocks.datetime.selectors.getStart );

				// Wait til use changes date or time on TEC datetime block
				yield take( [ SET_START_DATE_TIME, SET_START_TIME ] );

				// Important to cancel any pre-existing forks to prevent bad data from being sent
				if ( syncTask ) {
					yield cancel( syncTask );
				}
				syncTask = yield fork( syncTicketsSaleEndWithEventStart, eventStart );
			}
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		console.error( error );
	}
}

export function* handleTicketStartDate( action ) {
	const { blockId, date, dayPickerInput } = action.payload;
	const startDateMoment = yield date ? call( momentUtil.toMoment, date ) : undefined;
	const startDate = yield date ? call( momentUtil.toDatabaseDate, startDateMoment ) : '';
	yield put( actions.setTicketTempStartDate( blockId, startDate ) );
	yield put( actions.setTicketTempStartDateInput( blockId, dayPickerInput.state.value ) );
	yield put( actions.setTicketTempStartDateMoment( blockId, startDateMoment ) );
}

export function* handleTicketEndDate( action ) {
	const { blockId, date, dayPickerInput } = action.payload;
	const endDateMoment = yield date ? call( momentUtil.toMoment, date ) : undefined;
	const endDate = yield date ? call( momentUtil.toDatabaseDate, endDateMoment ) : '';
	yield put( actions.setTicketTempEndDate( blockId, endDate ) );
	yield put( actions.setTicketTempEndDateInput( blockId, dayPickerInput.state.value ) );
	yield put( actions.setTicketTempEndDateMoment( blockId, endDateMoment ) );
}

export function* handleTicketStartTime( action ) {
	const { blockId, seconds } = action.payload;
	const startTime = yield call( timeUtil.fromSeconds, seconds, timeUtil.TIME_FORMAT_HH_MM );
	yield put( actions.setTicketTempStartTime( blockId, `${ startTime }:00` ) );
}

export function* handleTicketStartTimeInput( action ) {
	const { blockId, seconds } = action.payload;
	const startTime = yield call( timeUtil.fromSeconds, seconds, timeUtil.TIME_FORMAT_HH_MM );
	const startTimeMoment = yield call( momentUtil.toMoment, startTime, momentUtil.TIME_FORMAT, false );
	const startTimeInput = yield call( momentUtil.toTime, startTimeMoment );
	yield put( actions.setTicketTempStartTimeInput( blockId, startTimeInput ) );
}

export function* handleTicketEndTime( action ) {
	const { blockId, seconds } = action.payload;
	const endTime = yield call( timeUtil.fromSeconds, seconds, timeUtil.TIME_FORMAT_HH_MM );
	yield put( actions.setTicketTempEndTime( blockId, `${ endTime }:00` ) );
}

export function* handleTicketEndTimeInput( action ) {
	const { blockId, seconds } = action.payload;
	const endTime = yield call( timeUtil.fromSeconds, seconds, timeUtil.TIME_FORMAT_HH_MM );
	const endTimeMoment = yield call( momentUtil.toMoment, endTime, momentUtil.TIME_FORMAT, false );
	const endTimeInput = yield call( momentUtil.toTime, endTimeMoment );
	yield put( actions.setTicketTempEndTimeInput( blockId, endTimeInput ) );
}

export function* handleTicketMove() {
	const ticketBlockIds = yield select( selectors.getAllTicketIds );
	const modalBlockId = yield select( moveSelectors.getModalBlockId );

	if ( ticketBlockIds.includes( modalBlockId ) ) {
		yield put( actions.setTicketIsSelected( modalBlockId, false ) );
		yield put( actions.removeTicketBlock( modalBlockId ) );
		yield call( [ wpDispatch( 'core/editor' ), 'removeBlocks' ], [ modalBlockId ] );
	}
}

export function* handler( action ) {
	switch ( action.type ) {
		case types.SET_TICKETS_INITIAL_STATE:
			yield call( setTicketsInitialState, action );
			break;

		case types.SET_TICKET_INITIAL_STATE:
			yield call( setTicketInitialState, action );
			break;

		case types.FETCH_TICKET:
			yield call( fetchTicket, action );
			break;

		case types.CREATE_NEW_TICKET:
			yield call( createNewTicket, action );
			break;

		case types.UPDATE_TICKET:
			yield call( updateTicket, action );
			break;

		case types.DELETE_TICKET:
			yield call( deleteTicket, action );
			break;

		case types.FETCH_TICKETS_HEADER_IMAGE:
			yield call( fetchTicketsHeaderImage, action );
			break;

		case types.UPDATE_TICKETS_HEADER_IMAGE:
			yield call( updateTicketsHeaderImage, action );
			break;

		case types.DELETE_TICKETS_HEADER_IMAGE:
			yield call( deleteTicketsHeaderImage );
			break;

		case types.SET_TICKET_DETAILS:
			yield call( setTicketDetails, action );
			break;

		case types.SET_TICKET_TEMP_DETAILS:
			yield call( setTicketTempDetails, action );
			break;

		case types.HANDLE_TICKET_START_DATE:
			yield call( handleTicketStartDate, action );
			yield put( actions.setTicketHasChanges( action.payload.blockId, true ) );
			break;

		case types.HANDLE_TICKET_END_DATE:
			yield call( handleTicketEndDate, action );
			yield put( actions.setTicketHasChanges( action.payload.blockId, true ) );
			break;

		case types.HANDLE_TICKET_START_TIME:
			yield call( handleTicketStartTime, action );
			yield call( handleTicketStartTimeInput, action );
			yield put( actions.setTicketHasChanges( action.payload.blockId, true ) );
			break;

		case types.HANDLE_TICKET_END_TIME:
			yield call( handleTicketEndTime, action );
			yield call( handleTicketEndTimeInput, action );
			yield put( actions.setTicketHasChanges( action.payload.blockId, true ) );
			break;

		case MOVE_TICKET_SUCCESS:
			yield call( handleTicketMove );
			break;

		default:
			break;
	}
}

export default function* watchers() {
	yield takeEvery( [
		types.SET_TICKETS_INITIAL_STATE,
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
	], handler );

	yield fork( handleEventStartDateChanges );
}
