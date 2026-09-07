/* eslint-disable max-len */

/**
 * External Dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';
import { put, call, takeEvery } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as types from '../rsvp-shared/types';
import * as actions from '../rsvp-shared/actions';
import { DEFAULT_STATE as RSVP_HEADER_IMAGE_DEFAULT_STATE } from './reducers/header-image';
import * as ticketActions from '../ticket/actions';
import { DEFAULT_STATE as TICKET_HEADER_IMAGE_DEFAULT_STATE } from '../ticket/reducers/header-image';
import * as utils from '../../utils';
import { api } from '@moderntribe/common/utils';

export function* fetchRSVPHeaderImage( action ) {
	const { id } = action.payload;
	yield put( actions.setRSVPIsSettingsLoading( true ) );

	try {
		const { response, data: media } = yield call( api.wpREST, { path: `media/${ id }` } );

		if ( response.ok ) {
			const headerImage = {
				id: media.id,
				alt: media.alt_text,
				src: media.media_details.sizes.medium.source_url,
			};
			yield put( actions.setRSVPHeaderImage( headerImage ) );
		}
	} catch ( e ) {
		console.error( e );
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setRSVPIsSettingsLoading( false ) );
	}
}

export function* updateRSVPHeaderImage( action ) {
	const { image } = action.payload;
	const postId = yield call( [ wpSelect( 'core/editor' ), 'getCurrentPostId' ] );
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: `${ image.id }`,
		},
	};

	try {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setRSVPIsSettingsLoading( true ) );
		yield put( ticketActions.setTicketsIsSettingsLoading( true ) );

		const slug = wpSelect( 'core/editor' ).getCurrentPostType();
		const postType = wpSelect( 'core' ).getPostType( slug );
		const restBase = postType.rest_base;

		const { response } = yield call( api.wpREST, {
			path: `${ restBase }/${ postId }`,
			headers: {
				'Content-Type': 'application/json',
			},
			initParams: {
				method: 'PUT',
				body: JSON.stringify( body ),
			},
		} );

		if ( response.ok ) {
			const headerImage = {
				id: image.id,
				alt: image.alt,
				src: image.sizes.medium.url,
			};
			/**
			 * @todo: until rsvp and tickets header image can be separated, they need to be linked
			 */
			yield put( actions.setRSVPHeaderImage( headerImage ) );
			yield put( ticketActions.setTicketsHeaderImage( headerImage ) );
		}
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setRSVPIsSettingsLoading( false ) );
		yield put( ticketActions.setTicketsIsSettingsLoading( false ) );
	}
}

export function* deleteRSVPHeaderImage() {
	const postId = yield call( [ wpSelect( 'core/editor' ), 'getCurrentPostId' ] );
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: null,
		},
	};

	try {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setRSVPIsSettingsLoading( true ) );
		yield put( ticketActions.setTicketsIsSettingsLoading( true ) );

		const slug = wpSelect( 'core/editor' ).getCurrentPostType();
		const postType = wpSelect( 'core' ).getPostType( slug );
		const restBase = postType.rest_base;

		const { response } = yield call( api.wpREST, {
			path: `${ restBase }/${ postId }`,
			headers: {
				'Content-Type': 'application/json',
			},
			initParams: {
				method: 'PUT',
				body: JSON.stringify( body ),
			},
		} );

		if ( response.ok ) {
			/**
			 * @todo: until rsvp and tickets header image can be separated, they need to be linked
			 */
			yield put( actions.setRSVPHeaderImage( RSVP_HEADER_IMAGE_DEFAULT_STATE ) );
			yield put( ticketActions.setTicketsHeaderImage( TICKET_HEADER_IMAGE_DEFAULT_STATE ) );
		}
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setRSVPIsSettingsLoading( false ) );
		yield put( ticketActions.setTicketsIsSettingsLoading( false ) );
	}
}

export function* handler( action ) {
	switch ( action.type ) {
		case types.FETCH_RSVP_HEADER_IMAGE:
			yield call( fetchRSVPHeaderImage, action );
			break;

		case types.UPDATE_RSVP_HEADER_IMAGE:
			yield call( updateRSVPHeaderImage, action );
			break;

		case types.DELETE_RSVP_HEADER_IMAGE:
			yield call( deleteRSVPHeaderImage );
			break;

		default:
			break;
	}
}

export default function* watchers() {
	yield takeEvery(
		[ types.FETCH_RSVP_HEADER_IMAGE, types.UPDATE_RSVP_HEADER_IMAGE, types.DELETE_RSVP_HEADER_IMAGE ],
		handler
	);
}
