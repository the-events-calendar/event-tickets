/* eslint-disable camelcase */
/**
 * External Dependencies
 */
import { put, all, select, takeLatest, call } from 'redux-saga/effects';

/**
 * Wordpress dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as types from './types';
import { globals } from '@moderntribe/common/utils';

export function* createBody( params ) {
	const payload = new FormData();
	const keys = Object.keys( params );

	for ( let i = 0; i < keys.length; i++ ) {
		const key = keys[ i ];
		const value = params[ key ];
		yield call( [ payload, 'append' ], key, encodeURIComponent( value ) );
	}

	return payload;
}

export function* _fetch( params ) {
	try {
		console.warn( globals.restNonce() );
		const body = yield call( createBody, {
			...params,
			check: globals.restNonce().wp_restn,
		} );

		const response = yield call( fetch, window.ajaxurl, {
			method: 'POST',
			body,
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

export function* initalize() {
	yield all( [
		call( fetchPostTypes ),
		call( fetchPostChoices ),
	] );
}

export default function* watchers() {
	yield takeLatest( [ types.INITIALIZE_MODAL ], initalize );
}
