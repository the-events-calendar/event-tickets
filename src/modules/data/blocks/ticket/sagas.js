/**
 * External Dependencies
 */
import { put, all, select, takeEvery, call } from 'redux-saga/effects';

/**
 * Wordpress dependencies
 */
import { select as wpSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as constants from './constants';
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import {
	DEFAULT_STATE as DEFAULT_UI_STATE,
} from '@moderntribe/tickets/data/blocks/ticket/reducers/ui';
import {
	DEFAULT_STATE as DEFAULT_TICKET_STATE,
} from '@moderntribe/tickets/data/blocks/ticket/reducers/ticket';
import { wpREST } from '@moderntribe/common/utils/api';
import { config, restNonce } from '@moderntribe/common/src/modules/utils/globals';
import { TICKET_TYPES } from '@moderntribe/tickets/data/utils';
import { blocks } from '@moderntribe/events/data';
import { moment as momentUtil } from '@moderntribe/common/utils';

/**
 * @todo missing tests.
 */
export function* setEditInTicketBlock( action ) {
	const { blockId } = action.payload;
	const currentId = yield select( selectors.getActiveBlockId );
	const hasBeenCreated = yield select( selectors.getTicketHasBeenCreated, { blockId } );

	if ( hasBeenCreated ) {
		return;
	}

	if ( currentId !== '' ) {
		yield put( actions.setTicketIsEditing( currentId, false ) );
	}

	yield all( [
		put( actions.setActiveChildBlockId( blockId ) ),
		put( actions.setTicketIsEditing( blockId, true ) ),
	] );
}

/**
 * @todo missing tests.
 */
export function* removeActiveTicketBlock( action ) {
	const { blockId } = action.payload;
	const currentId = yield select( selectors.getActiveBlockId );

	if ( currentId === blockId ) {
		yield put( actions.setActiveChildBlockId( '' ) );
	}

	const hasBeenCreated = yield select( selectors.getTicketHasBeenCreated, { blockId } );

	if ( ! hasBeenCreated ) {
		yield put( actions.removeTicketBlock( blockId ) );
		return;
	}

	const ticketId = yield select( selectors.getTicketId, { blockId } );
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
		yield all( [
			put( actions.setParentBlockIsLoading( true ) ),
			put( actions.setTicketIsLoading( blockId, true ) ),
		] );
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
		yield put( actions.removeTicketBlock( blockId ) );
	} catch ( e ) {
		/**
		 * @todo handle error on removal
		 */
	} finally {
		yield put( actions.setParentBlockIsLoading( false ) );
	}
}

export function* setBodyDetails( blockId ) {
	const body = new FormData();
	const props = { blockId };

	body.append( 'post_id', wpSelect( 'core/editor' ).getCurrentPostId() );
	body.append( 'provider', yield select( selectors.getSelectedProvider ) );
	body.append( 'name', yield select( selectors.getTicketTitle, props ) );
	body.append( 'description', yield select( selectors.getTicketDescription, props ) );
	body.append( 'price', yield select( selectors.getTicketPrice, props ) );
	body.append( 'start_date', yield select( selectors.getTicketStartDate, props ) );
	body.append( 'start_time', yield select( selectors.getTicketStartTime, props ) );
	body.append( 'sku', yield select( selectors.getTicketSKU, props ) );

	const expires = yield select( selectors.getTicketExpires, props );
	if ( expires ) {
		body.append( 'end_date', yield select( selectors.getTicketEndDate, props ) );
		body.append( 'end_time', yield select( selectors.getTicketEndTime, props ) );
	}

	const capacity = {
		type: yield select( selectors.getTicketCapacityType, props ),
		amount: yield select( selectors.getTicketCapacity, props ),
	};

	const isUnlimited = capacity.type === TICKET_TYPES.unlimited;
	body.append( 'ticket[mode]', isUnlimited ? '' : capacity.type );
	body.append( 'ticket[capacity]', isUnlimited ? '' : capacity.amount );

	if ( capacity.type === TICKET_TYPES.shared ) {
		body.append( 'ticket[event_capacity]', yield select( selectors.getSharedCapacity ) );
	}

	return body;
}

/**
 * @todo missing tests.
 */
export function* createNewTicket( action ) {
	const { blockId } = action.payload;

	yield call( setGlobalSharedCapacity );
	const { add_ticket_nonce = '' } = restNonce();
	const body = yield call( setBodyDetails, blockId );
	body.append( 'add_ticket_nonce', add_ticket_nonce );

	try {
		yield put( actions.setTicketIsLoading( blockId, true ) );
		const ticket = yield call( wpREST, {
			path: 'tickets/',
			namespace: 'tribe/tickets/v1',
			initParams: {
				method: 'POST',
				body,
			},
		} );

		yield all( [
			put( actions.setTicketIsEditing( blockId, false ) ),
			put( actions.setTicketId( blockId, ticket.ID ) ),
			put( actions.setTicketHasBeenCreated( blockId, true ) ),
			put( actions.setActiveChildBlockId( '' ) ),
			put( actions.setTicketAvailable( blockId, ticket.capacity ) ),
			put( actions.setTicketProvider( blockId, constants.PROVIDER_CLASS_TO_PROVIDER_MAPPING[ ticket.provider_class ] ) ),
		] );
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield all( [
			put( actions.setTicketIsLoading( blockId, false ) ),
		] );
	}
}

/**
 * @todo missing tests.
 */
export function* updateActiveEditBlock( action ) {
	const { blockId, isEditing } = action.payload;

	if ( ! isEditing ) {
		return;
	}

	const currentId = yield select( selectors.getActiveBlockId );
	if ( currentId && currentId !== blockId ) {
		yield put( actions.setTicketIsEditing( currentId, false ) );
	}

	yield put( actions.setActiveChildBlockId( blockId ) );
}

/**
 * @todo missing tests.
 */
export function* getMedia( id ) {
	yield put( actions.setParentBlockIsLoading( true ) );
	try {
		const media = yield call( wpREST, { path: `media/${ id }` } );
		const header = {
			id: media.id,
			alt: media.alt_text,
			sizes: media.media_details.sizes,
		};
		yield put( actions.setHeader( header ) );
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setParentBlockIsLoading( false ) );
	}
}

export function* setInitialState( action ) {
	const { get } = action.payload;

	const header = parseInt( get( 'header', DEFAULT_UI_STATE.header ), 10 ) || 0;
	const sharedCapacity = get( 'sharedCapacity' );

	// Meta value is '0' however fields use empty string as default
	if ( sharedCapacity !== '0' ) {
		yield put( actions.setTotalSharedCapacity( sharedCapacity ) );
	}

	if ( header > 0 ) {
		yield call( getMedia, header );
	}

	const tickets = config().tickets || {};
	const defaultProvider = tickets.default_provider || '';
	const provider = get( 'provider', DEFAULT_UI_STATE.provider );
	yield put( actions.setProvider( provider || defaultProvider ) );
}

export function* setTicketInitialState( action ) {
	const { clientId, get } = action.payload;
	const values = {
		ticketId: get( 'ticketId', DEFAULT_TICKET_STATE.ticketId ),
		hasBeenCreated: get( 'hasBeenCreated', DEFAULT_TICKET_STATE.hasBeenCreated ),
		dateIsPristine: get( 'dateIsPristine', DEFAULT_TICKET_STATE.dateIsPristine ),
	};

	const publishDate = wpSelect( 'core/editor' ).getEditedPostAttribute( 'date' );
	const eventStart = yield select( blocks.datetime.selectors.getStart );

	const startMoment = yield call( momentUtil.toMoment, publishDate );
	const endMoment = yield call( momentUtil.toMoment, eventStart ); // Ticket purchase window should end when event start

	const startDate = yield call( momentUtil.toDate, startMoment );
	const startTime = yield call( momentUtil.toTime24Hr, startMoment );

	const endDate = yield call( momentUtil.toDate, endMoment );
	const endTime = yield call( momentUtil.toTime24Hr, endMoment );

	const sharedCapacity = yield select( selectors.getSharedCapacityInt );

	if (sharedCapacity) {
		yield put( actions.setCapacity( clientId, sharedCapacity ) );
	}

	yield all( [
		put( actions.setTicketHasBeenCreated( clientId, values.hasBeenCreated ) ),
		put( actions.setTicketId( clientId, values.ticketId ) ),
		put( actions.setTicketDateIsPristine( clientId, values.dateIsPristine ) ),
		put( actions.setStartDate( clientId, startDate ) ),
		put( actions.setStartTime( clientId, startTime ) ),
		put( actions.setEndDate( clientId, endDate ) ),
		put( actions.setEndTime( clientId, endTime ) ),
		put( actions.fetchTicketDetails( clientId, values.ticketId ) ),
	] );
}

export function* fetchTicketDetails( action ) {
	const { ticketId = 0, blockId } = action.payload;

	yield put( actions.setTicketIsLoading( blockId, true ) );

	try {
		if ( ticketId === 0 ) {
			return;
		}

		const ticket = yield call( wpREST, {
			path: `tickets/${ ticketId }`,
			namespace: 'tribe/tickets/v1',
		} );

		const costDetails = ticket.cost_details || {};
		const costValues = costDetails.values || [];
		const { totals = {} } = ticket;

		yield all( [
			put( actions.setTitle( blockId, ticket.title ) ),
			put( actions.setDescription( blockId, ticket.description ) ),
			put( actions.setCapacity( blockId, ticket.capacity ) ),
			put( actions.setPrice( blockId, costValues[ 0 ] ) ),
			put( actions.setSKU( blockId, ticket.sku ) ),
			put( actions.setCapacityType( blockId, ticket.capacity_type ) ),
			put( actions.setTicketSold( blockId, totals.sold ) ),
			put( actions.setTicketAvailable( blockId, totals.stock ) ),
			put( actions.setTicketCurrency( blockId, ticket.cost_details.currency_symbol ) ),
			put( actions.setTicketProvider( blockId, ticket.provider ) ),
		] );
	} catch ( e ) {
		/**
		 * @todo handle error scenario
		 */
	} finally {
		yield put( actions.setTicketIsLoading( blockId, false ) );
	}
}

export function* cancelEditTicket( action ) {
	const { blockId } = action.payload;
	const ticketId = yield select( selectors.getTicketId, { blockId } );

	yield all( [
		put( actions.setTicketIsEditing( blockId, false ) ),
		put( actions.setActiveChildBlockId( '' ) ),
		put( actions.fetchTicketDetails( blockId, ticketId ) ),
	] );
}

export function* setGlobalSharedCapacity() {
	const tmpSharedCapacity = yield select( selectors.getTmpSharedCapacity );
	const sharedValue = parseInt( tmpSharedCapacity, 10 );

	if ( ! isNaN( sharedValue ) && sharedValue > 0 ) {
		yield all( [
			put( actions.setTotalSharedCapacity( sharedValue ) ),
			put( actions.setTempSharedCapacity( '' ) ),
		] );
	}
}

export function* updateTicket( action ) {
	const { blockId } = action.payload;

	const { edit_ticket_nonce = '' } = restNonce();
	const body = yield call( setBodyDetails, blockId );
	body.append( 'edit_ticket_nonce', edit_ticket_nonce );

	yield call( setGlobalSharedCapacity );
	const ticketId = yield select( selectors.getTicketId, { blockId } );

	try {
		const data = [];
		for ( const pair of body.entries() ) {
			data.push( `${ encodeURIComponent( pair[ 0 ] ) }=${ encodeURIComponent( pair[ 1 ] ) }` );
		}

		yield all( [
			put( actions.setTicketIsLoading( blockId, true ) ),
			call( wpREST, {
				path: `tickets/${ ticketId }`,
				namespace: 'tribe/tickets/v1',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
				},
				initParams: {
					method: 'PUT',
					body: data.join( '&' ),
				},
			} ),
			put( actions.setTicketIsEditing( blockId, false ) ),
		] );
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
		yield put( actions.fetchTicketDetails( blockId, ticketId ) );
	} finally {
		yield all( [
			put( actions.setTicketIsLoading( blockId, false ) ),
			put( actions.setActiveChildBlockId( '' ) ),
		] );
	}
}

export default function* watchers() {
	yield takeEvery( types.SET_TICKET_BLOCK_ID, setEditInTicketBlock );
	yield takeEvery( types.REQUEST_REMOVAL_OF_TICKET_BLOCK, removeActiveTicketBlock );
	yield takeEvery( types.SET_CREATE_NEW_TICKET, createNewTicket );
	yield takeEvery( types.SET_TICKET_IS_EDITING, updateActiveEditBlock );
	yield takeEvery( types.SET_INITIAL_STATE, setInitialState );
	yield takeEvery( types.SET_TICKET_INITIAL_STATE, setTicketInitialState );
	yield takeEvery( types.FETCH_TICKET_DETAILS, fetchTicketDetails );
	yield takeEvery( types.CANCEL_EDIT_OF_TICKET, cancelEditTicket );
	yield takeEvery( types.SET_UPDATE_TICKET, updateTicket );
}
