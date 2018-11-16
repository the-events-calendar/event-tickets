/**
 * External Dependencies
 */
import { put, all, select, takeEvery, call } from 'redux-saga/effects';
import trim from 'lodash/trim';

/**
 * Wordpress dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as constants from './constants';
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
<<<<<<< HEAD
import { DEFAULT_STATE } from './reducer';
=======
import * as utils from './utils';
>>>>>>> release/F18.3
import {
	DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE
} from './reducers/header-image';
import {
	DEFAULT_STATE as TICKET_DEFAULT_STATE,
} from './reducers/tickets/ticket';
import * as utils from '@moderntribe/tickets/data/utils';
import { wpREST } from '@moderntribe/common/utils/api';
<<<<<<< HEAD
import {
	tickets as ticketsConfig,
	restNonce,
} from '@moderntribe/common/src/modules/utils/globals';
import { getStart } from '@moderntribe/events/data/blocks/datetime/selectors';
import { moment as momentUtil } from '@moderntribe/common/utils';

const {
	UNLIMITED,
	SHARED,
	TICKET_TYPES,
	PROVIDER_CLASS_TO_PROVIDER_MAPPING,
} = constants;

export function* setTicketsInitialState( action ) {
	const { get } = action.payload;

	const header = parseInt( get( 'header', HEADER_IMAGE_DEFAULT_STATE.id ), 10 );
	const sharedCapacity = get( 'sharedCapacity' );
=======
import { TICKET_TYPES } from '@moderntribe/tickets/data/utils';
import { moment as momentUtil, globals } from '@moderntribe/common/utils';

const { config, restNonce } = globals;
/**
 * @todo missing tests.
 */
export function* setEditInTicketBlock( action ) {
	const { blockId } = action.payload;
	const currentId = yield select( selectors.getActiveBlockId );
	const hasBeenCreated = yield select( selectors.getTicketHasBeenCreated, { blockId } );
>>>>>>> release/F18.3

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

	const publishDate = wpSelect( 'core/editor' ).getEditedPostAttribute( 'date' );
	const startMoment = yield call( momentUtil.toMoment, publishDate );
	const startDate = yield call( momentUtil.toDatabaseDate, startMoment );
	const startDateInput = yield call( momentUtil.toDate, startMoment );
	const startTime = yield call( momentUtil.toDatabaseTime, startMoment );

	yield all( [
		put( actions.setTicketStartDate( clientId, startDate ) ),
		put( actions.setTicketStartDateInput( clientId, startDateInput ) ),
		put( actions.setTicketStartDateMoment( clientId, startMoment ) ),
		put( actions.setTicketStartTime( clientId, startTime ) ),
		put( actions.setTicketTempStartDate( clientId, startDate ) ),
		put( actions.setTicketTempStartDateInput( clientId, startDateInput ) ),
		put( actions.setTicketTempStartDateMoment( clientId, startMoment ) ),
		put( actions.setTicketTempStartTime( clientId, startTime ) ),
	] );

	try {
		// NOTE: This requires TEC to be installed, if not installed, do not set an end date
		const eventStart = yield select( getStart ); // Ticket purchase window should end when event starts
		const endMoment = yield call( momentUtil.toMoment, eventStart );
		const endDate = yield call( momentUtil.toDatabaseDate, endMoment );
		const endDateInput = yield call( momentUtil.toDate, endMoment );
		const endTime = yield call( momentUtil.toDatabaseTime, endMoment );

<<<<<<< HEAD
		yield all( [
			put( actions.setTicketEndDate( clientId, endDate ) ),
			put( actions.setTicketEndDateInput( clientId, endDateInput ) ),
			put( actions.setTicketEndDateMoment( clientId, endMoment ) ),
			put( actions.setTicketEndTime( clientId, endTime ) ),
			put( actions.setTicketTempEndDate( clientId, endDate ) ),
			put( actions.setTicketTempEndDateInput( clientId, endDateInput ) ),
			put( actions.setTicketTempEndDateMoment( clientId, endMoment ) ),
			put( actions.setTicketTempEndTime( clientId, endTime ) ),
		] );
	} catch ( err ) {
		// ¯\_(ツ)_/¯
	}
=======
	const ticketId = yield select( selectors.getTicketId, { blockId } );
	const { remove_ticket_nonce = '' } = globals.restNonce();
>>>>>>> release/F18.3

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
<<<<<<< HEAD
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
=======
	body.append( 'provider', yield select( selectors.getSelectedProvider ) );
	body.append( 'name', yield select( selectors.getTicketTitle, props ) );
	body.append( 'description', yield select( selectors.getTicketDescription, props ) );
	body.append( 'price', yield select( selectors.getTicketPrice, props ) );
	body.append( 'start_date', yield select( selectors.getNormalizedStartDate, props ) );
	body.append( 'start_time', yield select( selectors.getNormalizedStartTime, props ) );
	body.append( 'sku', yield select( selectors.getTicketSKU, props ) );

	const expires = yield select( selectors.getTicketExpires, props );
	if ( expires ) {
		body.append( 'end_date', yield select( selectors.getNormalizedEndDate, props ) );
		body.append( 'end_time', yield select( selectors.getNormalizedEndTime, props ) );
>>>>>>> release/F18.3
	}

	return body;
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

		if ( response.ok ) {
			const {
				totals = {},
				available_from,
				available_until,
				cost_details,
				provider,
				title,
				description,
				sku,
				capacity_type,
				capacity,
			} = ticket;

			const startMoment = yield call( momentUtil.toMoment, available_from );
			const startDate = yield call( momentUtil.toDatabaseDate, startMoment );
			const startDateInput = yield call( momentUtil.toDate, startMoment );
			const startTime = yield call( momentUtil.toDatabaseTime, startMoment );

			let endMoment = yield call( momentUtil.toMoment, '' );
			let endDate = '';
			let endDateInput = '';
			let endTime = '';

			if ( available_until ) {
				endMoment = yield call( momentUtil.toMoment, available_until );
				endDate = yield call( momentUtil.toDatabaseDate, endMoment );
				endDateInput = yield call( momentUtil.toDate, endMoment );
				endTime = yield call( momentUtil.toDatabaseTime, endMoment );
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
		/**
		 * @todo handle error scenario
		 */
	} finally {
		yield put( actions.setTicketIsLoading( blockId, false ) );
	}
}

export function* createNewTicket( action ) {
	const { blockId } = action.payload;
	const props = { blockId };

<<<<<<< HEAD
	const { add_ticket_nonce = '' } = restNonce();
=======
	yield call( setGlobalSharedCapacity );
	const { add_ticket_nonce = '' } = globals.restNonce();
>>>>>>> release/F18.3
	const body = yield call( setBodyDetails, blockId );
	body.append( 'add_ticket_nonce', add_ticket_nonce );

	try {
		yield put( actions.setTicketIsLoading( blockId, true ) );
<<<<<<< HEAD
		const { response, data: ticket } = yield call( wpREST, {
=======
		const { data = {} } = yield call( wpREST, {
>>>>>>> release/F18.3
			path: 'tickets/',
			namespace: 'tribe/tickets/v1',
			initParams: {
				method: 'POST',
				body,
			},
		} );
		const ticket = data;

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
<<<<<<< HEAD
		const data = [];
		for ( const pair of body.entries() ) {
			data.push( `${ encodeURIComponent( pair[ 0 ] ) }=${ encodeURIComponent( pair[ 1 ] ) }` );
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
					capacityType,
					capacity,
				} ) ),
				put( actions.setTicketHasChanges( blockId, false ) ),
			] );
		}
=======
		const { data = {} } = yield call( wpREST, { path: `media/${ id }` } );
		const media = data;
		const header = {
			id: media.id,
			alt: media.alt_text,
			sizes: media.media_details.sizes,
		};
		yield put( actions.setHeader( header ) );
>>>>>>> release/F18.3
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketIsLoading( blockId, false ) );
	}
}

<<<<<<< HEAD
export function* deleteTicket( action ) {
	const { blockId } = action.payload;
	const props = { blockId };
=======
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

	const tickets = globals.config().tickets || {};
	const defaultProvider = tickets.default_provider || '';
	const provider = get( 'provider', DEFAULT_UI_STATE.provider );
	yield put( actions.setProvider( provider || defaultProvider ) );
}
>>>>>>> release/F18.3

	const ticketId = yield select( selectors.getTicketId, props );
	const hasBeenCreated = yield select( selectors.getTicketHasBeenCreated, props );

<<<<<<< HEAD
	yield put( actions.setTicketIsSelected( blockId, false ) );
	yield put( actions.removeTicketBlock( blockId ) );

	if ( hasBeenCreated ) {
		const { remove_ticket_nonce = '' } = restNonce();
		const postId = wpSelect( 'core/editor' ).getCurrentPostId();
=======
	const publishDate = wpSelect( 'core/editor' ).getEditedPostAttribute( 'date' );
	const startMoment = yield call( momentUtil.toMoment, publishDate );
	const startDate = yield call( momentUtil.toDate, startMoment );
	const startTime = yield call( momentUtil.toTime24Hr, startMoment );

	yield all( [
		put( actions.setStartDate( clientId, startDate ) ),
		put( actions.setStartTime( clientId, startTime ) ),
	] );

	try {
		// NOTE: This requires TEC to be installed, if not installed, do not set an end date
		const eventStart = yield select( window.tribe.events.blocks.datetime.selectors.getStart ); // Ticket purchase window should end when event start... ideally
		const endMoment = yield call( momentUtil.toMoment, eventStart );
		const endDate = yield call( momentUtil.toDate, endMoment );
		const endTime = yield call( momentUtil.toTime24Hr, endMoment );

		yield all( [
			put( actions.setEndDate( clientId, endDate ) ),
			put( actions.setEndTime( clientId, endTime ) ),
		] );
	} catch ( err ) {
		// ¯\_(ツ)_/¯
	}
>>>>>>> release/F18.3

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
<<<<<<< HEAD
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
=======

	yield all( [
		put( actions.setTicketHasBeenCreated( clientId, values.hasBeenCreated ) ),
		put( actions.setTicketId( clientId, values.ticketId ) ),
		put( actions.setTicketDateIsPristine( clientId, values.dateIsPristine ) ),
		put( actions.fetchTicketDetails( clientId, values.ticketId ) ),
	] );
}

export function* fetchTicketDetails( action ) {
	const { ticketId = 0, blockId } = action.payload;

	yield put( actions.setTicketIsLoading( blockId, true ) );
	try {
		// Prevent to fetch details of a non created ticket
		if ( ticketId === 0 ) {
			yield call( removeBlock,  blockId );
			return;
		}

		const { data = {}, response = {} } = yield call( wpREST, {
			path: `tickets/${ ticketId }`,
			namespace: 'tribe/tickets/v1',
		} );
		const ticket = data;

		if ( response.status === 404 ) {
			yield call( removeBlock, blockId );
			return;
		}


		const costDetails = ticket.cost_details || {};
		const costValues = costDetails.values || [];
		const {
			totals = {},
			available_from,
			available_from_start_time,
			available_until,
			available_from_end_time,
		} = ticket;

		const start = `${available_from} ${available_from_start_time}`;
		const format = `${utils.toMomentDateFormat} ${utils.toMomentTimeFormat}`;
		const startMoment = yield call( momentUtil.toMoment, start, format, false );
		const startDate = yield call( momentUtil.toDate, startMoment );
		const startTime = yield call( momentUtil.toTime24Hr, startMoment );

		yield all([
			put( actions.setTicketStartDateMoment( blockId, startMoment ) ),
			put( actions.setStartDate( blockId, startDate ) ),
			put( actions.setStartTime( blockId, startTime ) ),
		]);

		if ( available_until && available_from_end_time ) {
			const end = `${available_until} ${available_from_end_time}`;
			const endMoment = yield call( momentUtil.toMoment, end, format, false );
			const endDate = yield call( momentUtil.toDate, endMoment );
			const endTime = yield call( momentUtil.toTime24Hr, endMoment );
			yield all([
				put( actions.setTicketEndDateMoment( blockId, endMoment ) ),
				put( actions.setEndDate( blockId, endDate ) ),
				put( actions.setEndTime( blockId, endTime) ),
			]);
		}

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
>>>>>>> release/F18.3
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketsIsSettingsLoading( false ) );
	}
}

<<<<<<< HEAD
export function* updateTicketsHeaderImage( action ) {
	const { image } = action.payload;
	const postId = wpSelect( 'core/editor' ).getCurrentPostId();
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: `${ image.id }`,
		},
	};
=======
export function* removeBlock( blockId ) {
	const activeBlockId = yield select( selectors.getActiveBlockId );
	const hasBeenCreated = yield select( selectors.getTicketHasBeenCreated, { blockId } );
	if ( ! hasBeenCreated ) {
		return;
	}

	if ( activeBlockId === blockId ) {
		yield put( actions.setActiveChildBlockId( '' ) );
	}

	yield all( [
		call( [ wpDispatch( 'core/editor' ), 'removeBlock' ], blockId ),
		put( actions.removeTicketBlock( blockId ) ),
		put( actions.setTicketIsEditing( blockId, false ) ),
	] );
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
>>>>>>> release/F18.3

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

<<<<<<< HEAD
export function* deleteTicketsHeaderImage() {
	const postId = wpSelect( 'core/editor' ).getCurrentPostId();
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: null,
		},
	};
=======
export function* updateTicket( action ) {
	const { blockId } = action.payload;

	const { edit_ticket_nonce = '' } = globals.restNonce();
	const body = yield call( setBodyDetails, blockId );
	body.append( 'edit_ticket_nonce', edit_ticket_nonce );

	yield call( setGlobalSharedCapacity );
	const ticketId = yield select( selectors.getTicketId, { blockId } );
>>>>>>> release/F18.3

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

<<<<<<< HEAD
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
		put( actions.setTicketTempCapacityType( blockId, capacityType ) ),
		put( actions.setTicketTempCapacity( blockId, capacity ) ),
	] );
=======
export function* setRegularCapacity( action ) {
	const { payload = {} } = action;
	const { blockId, capacity } = payload;
	const isEmpty = trim( capacity ) === '';
	const capacityType = isEmpty ? TICKET_TYPES.unlimited : TICKET_TYPES.independent;
	yield all([
		put( actions.setCapacityType( blockId, capacityType ) ),
		put( actions.setCapacity( blockId, capacity ) ),
	])
>>>>>>> release/F18.3
}

export default function* watchers() {
	yield takeEvery( types.SET_TICKETS_INITIAL_STATE, setTicketsInitialState );
	yield takeEvery( types.SET_TICKET_INITIAL_STATE, setTicketInitialState );
<<<<<<< HEAD
	yield takeEvery( types.FETCH_TICKET, fetchTicket );
	yield takeEvery( types.CREATE_NEW_TICKET, createNewTicket );
	yield takeEvery( types.UPDATE_TICKET, updateTicket );
	yield takeEvery( types.DELETE_TICKET, deleteTicket );
	yield takeEvery( types.FETCH_TICKETS_HEADER_IMAGE, fetchTicketsHeaderImage );
	yield takeEvery( types.UPDATE_TICKETS_HEADER_IMAGE, updateTicketsHeaderImage );
	yield takeEvery( types.DELETE_TICKETS_HEADER_IMAGE, deleteTicketsHeaderImage );
	yield takeEvery( types.SET_TICKET_DETAILS, setTicketDetails );
	yield takeEvery( types.SET_TICKET_TEMP_DETAILS, setTicketTempDetails );
=======
	yield takeEvery( types.FETCH_TICKET_DETAILS, fetchTicketDetails );
	yield takeEvery( types.CANCEL_EDIT_OF_TICKET, cancelEditTicket );
	yield takeEvery( types.SET_UPDATE_TICKET, updateTicket );
	yield takeEvery( types.SET_TICKET_REGULAR_CAPACITY, setRegularCapacity );
>>>>>>> release/F18.3
}
