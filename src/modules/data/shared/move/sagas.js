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
 * @export
 * @yield
 * @param {*} {
 *              ignore,
 *              post_type,
 *              search_terms = '',
 *              }
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
 * @export
 * @yield
 * @param {*} {
 *              src_post_id,
 *              ticket_type_id,
 *              target_post_id,
 *              }
 */
export function* moveTicket( { src_post_id, ticket_type_id, target_post_id } ) {
	try {
		yield put( {
			type: types.MOVE_TICKET,
		} );
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
