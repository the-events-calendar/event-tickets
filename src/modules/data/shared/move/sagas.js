/* eslint-disable camelcase */
/**
 * External Dependencies
 */
import { put, all, select, takeLatest, call, fork, take } from 'redux-saga/effects';
import { delay } from 'redux-saga';

/**
 * Wordpress dependencies
 */
import { select as wpSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as types from './types';
import { globals } from '@moderntribe/common/utils';
import * as selectors from './selectors';
import * as actions from './actions';
import { usesDeferredSave } from '../../blocks/ticket/deferred';
import { stageMove } from '../../blocks/ticket/deferred-sagas';
import * as ticketSelectors from '../../blocks/ticket/selectors';

export function createBody( params ) {
	return Object.entries( params )
		.map( ( [ key, value ] ) => `${ key }=${ encodeURIComponent( value ) }` )
		.join( '&' );
}

export function* _fetch( params ) {
	try {
		const body = yield call( createBody, {
			...params,
			check: globals.restNonce().move_tickets,
		} );

		const response = yield call( fetch, window.ajaxurl, {
			method: 'POST',
			body,
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
			},
			credentials: 'include',
		} );

		return yield call( [ response, 'json' ] );
	} catch ( error ) {
		console.error( error );
	}
}

/**
 * Fetches usable oost types
 *
 * @yield
 */
export function* fetchPostTypes() {
	try {
		yield put( {
			type: types.FETCH_POST_TYPES,
		} );
		const { data } = yield call( _fetch, {
			action: 'move_tickets_get_post_types',
		} );
		yield put( {
			type: types.FETCH_POST_TYPES_SUCCESS,
			data,
		} );
		return data;
	} catch ( error ) {
		yield put( {
			type: types.FETCH_POST_TYPES_ERROR,
			error,
		} );
	}
}

/**
 * Fetches filtered posts based on criteria
 *
 * @yield
 * @param {Object} args              The search criteria.
 * @param {Array}  args.ignore       Post IDs to ignore.
 * @param {string} args.post_type    The post type to search.
 * @param {string} args.search_terms The search terms.
 */
export function* fetchPostChoices( { ignore, post_type, search_terms = '' } ) {
	try {
		yield put( {
			type: types.FETCH_POST_CHOICES,
		} );
		const { data } = yield call( _fetch, {
			action: 'move_tickets_get_post_choices',
			ignore,
			post_type,
			search_terms,
		} );
		yield put( {
			type: types.FETCH_POST_CHOICES_SUCCESS,
			data,
		} );
		return data;
	} catch ( error ) {
		yield put( {
			type: types.FETCH_POST_CHOICES_ERROR,
			error,
		} );
	}
}

/**
 * Moves ticket/RSVP from one post to another
 *
 * @yield
 * @param {Object} args                The move arguments.
 * @param {number} args.src_post_id    The source post ID.
 * @param {number} args.ticket_type_id The ticket type ID.
 * @param {number} args.target_post_id The target post ID.
 */
/**
 * Whether the block being moved is a Ticket block, rather than an RSVP.
 *
 * @since TBD
 *
 * @return {boolean} Whether the tickets store holds the block the move dialog was opened for.
 */
export function* isTicketBlockMove() {
	const clientId = yield select( selectors.getModalClientId );
	const ticketClientIds = yield select( ticketSelectors.getTicketsAllClientIds );

	return ticketClientIds.includes( clientId );
}

export function* moveTicket( { src_post_id, ticket_type_id, target_post_id } ) {
	try {
		yield put( {
			type: types.MOVE_TICKET,
		} );

		// On a post that defers ticket saves a Ticket block's move is staged and happens with the post save. An RSVP,
		// whose other changes are saved at once, is moved at once too: a staged move would read as done to it.
		if ( usesDeferredSave() && ( yield call( isTicketBlockMove ) ) ) {
			const staged = yield call( stageMove, parseInt( ticket_type_id, 10 ), parseInt( target_post_id, 10 ) );

			if ( ! staged ) {
				yield put( {
					type: types.MOVE_TICKET_ERROR,
					error: 'staged-edit',
				} );
				return;
			}

			const data = { remove_ticket_type: parseInt( ticket_type_id, 10 ), staged: true };
			yield put( {
				type: types.MOVE_TICKET_SUCCESS,
				data,
			} );
			return data;
		}
		const { data } = yield call( _fetch, {
			action: 'move_ticket_type',
			src_post_id,
			ticket_type_id,
			target_post_id,
		} );
		yield put( {
			type: types.MOVE_TICKET_SUCCESS,
			data,
		} );
		return data;
	} catch ( error ) {
		yield put( {
			type: types.MOVE_TICKET_ERROR,
			error,
		} );
	}
}

export function* getCurrentPostId() {
	return yield call( [ wpSelect( 'core/editor' ), 'getCurrentPostId' ] );
}

export function* getPostChoices() {
	const params = yield all( {
		post_type: select( selectors.getModalPostType ),
		search_terms: select( selectors.getModalSearch ),
		ignore: call( getCurrentPostId ),
	} );
	yield call( fetchPostChoices, params );
}

export function* onModalChange( action ) {
	if ( ! action.payload.hasOwnProperty( 'target_post_id' ) && ! action.payload.hasOwnProperty( 'ticketId' ) ) {
		yield call( delay, 500 );
		yield call( getPostChoices );
	}
}

export function* onModalSubmit() {
	const params = yield all( {
		src_post_id: call( getCurrentPostId ),
		target_post_id: select( selectors.getModalTarget ),
		ticket_type_id: select( selectors.getModalTicketId ),
	} );
	yield fork( moveTicket, params );

	const action = yield take( [ types.MOVE_TICKET_SUCCESS, types.MOVE_TICKET_ERROR ] );

	if ( action.type === types.MOVE_TICKET_SUCCESS ) {
		yield put( actions.hideModal() );
	}
}

export function* onModalShow( action ) {
	yield put( { type: types.SET_MODAL_DATA, payload: action.payload } );
}

export function* onModalHide() {
	yield put( { type: types.RESET_MODAL_DATA } );
}

export function* initialize() {
	yield all( [ call( fetchPostTypes ), call( getPostChoices ) ] );
}

export default function* watchers() {
	yield takeLatest( [ types.INITIALIZE_MODAL ], initialize );
	yield takeLatest( [ types.SET_MODAL_DATA ], onModalChange );
	yield takeLatest( [ types.SUBMIT_MODAL ], onModalSubmit );
	yield takeLatest( [ types.SHOW_MODAL ], onModalShow );
	yield takeLatest( [ types.HIDE_MODAL ], onModalHide );
}
