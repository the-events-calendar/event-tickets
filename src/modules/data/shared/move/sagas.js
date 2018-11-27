/* eslint-disable camelcase */
/**
 * External Dependencies
 */
import { put, all, select, takeLatest, call } from 'redux-saga/effects';
import { delay } from 'redux-saga';

/**
 * Wordpress dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as types from './types';
import { globals } from '@moderntribe/common/utils';
import * as selectors from '@moderntribe/tickets/data/shared/move/selectors';

export function createBody( params ) {
	return Object.entries( params )
		.map( ( [ key, value ] ) => `${ key }=${ encodeURIComponent( value ) }` )
		.join( '&' );
}

export function* _fetch( params ) {
	try {
		const body = yield call( createBody, {
			...params,
			check: globals.restNonce().wp_rest,
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

	}
}

/**
 * Fetches usable oost types
 * @returns {Object} JSON response
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
 * @param {*} {
 * 	ignore,
 * 	post_type,
 * 	search_terms = '',
 * }
 * @returns {Object} JSON response
 */
export function* fetchPostChoices( {
	ignore,
	post_type,
	search_terms = '',
} ) {
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
 * @param {*} {
 * 	src_post_id,
 * 	ticket_type_id,
 * 	target_post_id,
 * }
 * @returns {Object} JSON response
 */
export function* moveTicket( {
	src_post_id,
	ticket_type_id,
	target_post_id,
} ) {
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
	if ( ! action.payload.hasOwnProperty( 'target_post_id' ) ) {
		yield call( delay, 700 );
		yield call( getPostChoices );
	}
}

export function* initalize() {
	yield all( [
		call( fetchPostTypes ),
		call( getPostChoices ),
	] );
}

export default function* watchers() {
	yield takeLatest( [ types.INITIALIZE_MODAL ], initalize );
	yield takeLatest( [ types.SET_MODAL_DATA ], onModalChange );
}
