/* eslint-disable max-len */

/**
 * External Dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';
import { put, call, all, select, takeEvery, take, fork, cancel } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import { updateRSVP } from './thunks';
import {
	DEFAULT_STATE as RSVP_HEADER_IMAGE_DEFAULT_STATE
} from './reducers/header-image';
import * as ticketActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import {
	DEFAULT_STATE as TICKET_HEADER_IMAGE_DEFAULT_STATE
} from '@moderntribe/tickets/data/blocks/ticket/reducers/header-image';
import * as utils from '@moderntribe/tickets/data/utils';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';
import { isTribeEventPostType, createWPEditorSavingChannel, createDates } from '@moderntribe/tickets/data/shared/sagas';

import {
	api,
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';

//
// ─── RSVP DETAILS ───────────────────────────────────────────────────────────────
//

/**
 * Set details for current RSVP
 *
 * @export
 * @param {Object} action redux action
 */
export function* setRSVPDetails( action ) {
	const {
		title,
		description,
		capacity,
		notGoingResponses,
		startDate,
		startDateInput,
		startDateMoment,
		startTime,
		endDate,
		endDateInput,
		endDateMoment,
		endTime,
		startTimeInput,
		endTimeInput,
	} = action.payload;
	yield all( [
		put( actions.setRSVPTitle( title ) ),
		put( actions.setRSVPDescription( description ) ),
		put( actions.setRSVPCapacity( capacity ) ),
		put( actions.setRSVPNotGoingResponses( notGoingResponses ) ),
		put( actions.setRSVPStartDate( startDate ) ),
		put( actions.setRSVPStartDateInput( startDateInput ) ),
		put( actions.setRSVPStartDateMoment( startDateMoment ) ),
		put( actions.setRSVPStartTime( startTime ) ),
		put( actions.setRSVPEndDate( endDate ) ),
		put( actions.setRSVPEndDateInput( endDateInput ) ),
		put( actions.setRSVPEndDateMoment( endDateMoment ) ),
		put( actions.setRSVPEndTime( endTime ) ),
		put( actions.setRSVPStartTimeInput( startTimeInput ) ),
		put( actions.setRSVPEndTimeInput( endTimeInput ) ),
	] );
}

/**
 * Set details for current temp RSVP
 *
 * @export
 * @param {Object} action redux action
 */
export function* setRSVPTempDetails( action ) {
	const {
		tempTitle,
		tempDescription,
		tempCapacity,
		tempNotGoingResponses,
		tempStartDate,
		tempStartDateInput,
		tempStartDateMoment,
		tempStartTime,
		tempEndDate,
		tempEndDateInput,
		tempEndDateMoment,
		tempEndTime,
		tempStartTimeInput,
		tempEndTimeInput,
	} = action.payload;
	yield all( [
		put( actions.setRSVPTempTitle( tempTitle ) ),
		put( actions.setRSVPTempDescription( tempDescription ) ),
		put( actions.setRSVPTempCapacity( tempCapacity ) ),
		put( actions.setRSVPTempNotGoingResponses( tempNotGoingResponses ) ),
		put( actions.setRSVPTempStartDate( tempStartDate ) ),
		put( actions.setRSVPTempStartDateInput( tempStartDateInput ) ),
		put( actions.setRSVPTempStartDateMoment( tempStartDateMoment ) ),
		put( actions.setRSVPTempStartTime( tempStartTime ) ),
		put( actions.setRSVPTempEndDate( tempEndDate ) ),
		put( actions.setRSVPTempEndDateInput( tempEndDateInput ) ),
		put( actions.setRSVPTempEndDateMoment( tempEndDateMoment ) ),
		put( actions.setRSVPTempEndTime( tempEndTime ) ),
		put( actions.setRSVPTempStartTimeInput( tempStartTimeInput ) ),
		put( actions.setRSVPTempEndTimeInput( tempEndTimeInput ) ),
	] );
}

//
// ─── INITIALIZE ─────────────────────────────────────────────────────────────────
//

/**
 * Initializes RSVP that has not been created
 * @borrows TEC - Optional functionality requires TEC to be enabled and post type to be event
 * @export
 */
export function* initializeRSVP() {
	const publishDate =  yield call( [ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ], 'date' );
	const {
		moment: startMoment,
		date: startDate,
		dateInput: startDateInput,
		time: startTime,
		timeInput: startTimeInput,
	} = yield call( createDates, publishDate );

	yield all( [
		put( actions.setRSVPStartDate( startDate ) ),
		put( actions.setRSVPStartDateInput( startDateInput ) ),
		put( actions.setRSVPStartDateMoment( startMoment ) ),
		put( actions.setRSVPStartTime( startTime ) ),
		put( actions.setRSVPStartTimeInput( startTimeInput ) ),
		put( actions.setRSVPTempStartDate( startDate ) ),
		put( actions.setRSVPTempStartDateInput( startDateInput ) ),
		put( actions.setRSVPTempStartDateMoment( startMoment ) ),
		put( actions.setRSVPTempStartTime( startTime ) ),
		put( actions.setRSVPTempStartTimeInput( startTimeInput ) ),
	] );

	try {
		if ( yield call( isTribeEventPostType ) ) {
			// NOTE: This requires TEC to be installed, if not installed, do not set an end date
			const eventStart = yield select( window.tribe.events.data.blocks.datetime.selectors.getStart ); // RSVP window should end when event starts... ideally
			const {
				moment: endMoment,
				date: endDate,
				dateInput: endDateInput,
				time: endTime,
				timeInput: endTimeInput,
			} = yield call( createDates, eventStart );

			yield all( [
				put( actions.setRSVPEndDate( endDate ) ),
				put( actions.setRSVPEndDateInput( endDateInput ) ),
				put( actions.setRSVPEndDateMoment( endMoment ) ),
				put( actions.setRSVPEndTime( endTime ) ),
				put( actions.setRSVPEndTimeInput( endTimeInput ) ),
				put( actions.setRSVPTempEndDate( endDate ) ),
				put( actions.setRSVPTempEndDateInput( endDateInput ) ),
				put( actions.setRSVPTempEndDateMoment( endMoment ) ),
				put( actions.setRSVPTempEndTime( endTime ) ),
				put( actions.setRSVPTempEndTimeInput( endTimeInput ) ),
			] );
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		console.error( error );
	}

	yield call( handleRSVPDurationError );
}

/**
 * Will sync RSVP sale end to be the same as event start date and time, if field has not been manually edited
 * @borrows TEC - Functionality requires TEC to be enabled
 * @param {String} prevStartDate Previous start date before latest set date time changes
 * @export
 */
export function* syncRSVPSaleEndWithEventStart( prevStartDate ) {
	try {
		const tempEndMoment = yield select( selectors.getRSVPTempEndDateMoment );
		const endMoment = yield select( selectors.getRSVPEndDateMoment );
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

			yield all( [
				put( actions.setRSVPTempEndDate( endDate ) ),
				put( actions.setRSVPTempEndDateInput( endDateInput ) ),
				put( actions.setRSVPTempEndDateMoment( endDateMoment ) ),
				put( actions.setRSVPTempEndTime( endTime ) ),
				put( actions.setRSVPTempEndTimeInput( endTimeInput ) ),

				// Sync RSVP end items as well so as not to make state 'manually edited'
				put( actions.setRSVPEndDate( endDate ) ),
				put( actions.setRSVPEndDateInput( endDateInput ) ),
				put( actions.setRSVPEndDateMoment( endDateMoment ) ),
				put( actions.setRSVPEndTime( endTime ) ),
				put( actions.setRSVPEndTimeInput( endTimeInput ) ),

				// Trigger UI button
				put( actions.setRSVPHasChanges( true ) ),

				// Handle RSVP duration error
				call( handleRSVPDurationError ),
			] );

			// Sub fork which will wait to sync RSVP when post saves
			yield fork( saveRSVPWithPostSave );
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		console.error( error );
	}
}

/**
 * Allows the RSVP to be saved at the same time a post is being saved.
 * Avoids the user having to open up the RSVP block, and then click update again there, when changing the event start date.
 *
 * @export
 */
export function* saveRSVPWithPostSave() {
	let saveChannel;
	try {
		// Do nothing when not already created
		if ( yield select( selectors.getRSVPCreated ) ) {
			// Create channel for use
			saveChannel = yield call( createWPEditorSavingChannel );

			// Wait for channel to save
			yield take( saveChannel );

			const payload = yield all( {
				id: select( selectors.getRSVPId ),
				title: select( selectors.getRSVPTempTitle ),
				description: select( selectors.getRSVPTempDescription ),
				capacity: select( selectors.getRSVPTempCapacity ),
				notGoingResponses: select( selectors.getRSVPTempNotGoingResponses ),
				startDate: select( selectors.getRSVPTempStartDate ),
				startDateInput: select( selectors.getRSVPTempStartDateInput ),
				startDateMoment: select( selectors.getRSVPTempStartDateMoment ),
				endDate: select( selectors.getRSVPTempEndDate ),
				endDateInput: select( selectors.getRSVPTempEndDateInput ),
				endDateMoment: select( selectors.getRSVPTempEndDateMoment ),
				startTime: select( selectors.getRSVPTempStartTime ),
				endTime: select( selectors.getRSVPTempEndTime ),
			} );

			// Use update thunk to submit
			yield put( updateRSVP( payload ) );
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
 * Listens for event start date and time changes after RSVP block is loaded.
 * @borrows TEC - Functionality requires TEC to be enabled and post type to be event
 * @export
 */
export function* handleEventStartDateChanges() {
	try {
		// Proceed after creating dummy RSVP or after fetching
		yield take( [ types.INITIALIZE_RSVP, types.SET_RSVP_DETAILS ] );
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
				syncTask = yield fork( syncRSVPSaleEndWithEventStart, eventStart );
			}
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		console.error( error );
	}
}

//
// ─── DATE & TIME ────────────────────────────────────────────────────────────────
//

export function* handleRSVPDurationError() {
	let hasDurationError = false;
	const startDateMoment = yield select( selectors.getRSVPTempStartDateMoment );
	const endDateMoment = yield select( selectors.getRSVPTempEndDateMoment );

	if ( ! startDateMoment || ! endDateMoment ) {
		hasDurationError = true;
	} else {
		const startTime = yield select( selectors.getRSVPTempStartTime );
		const endTime = yield select( selectors.getRSVPTempEndTime );
		const startTimeSeconds = yield call( timeUtil.toSeconds, startTime, timeUtil.TIME_FORMAT_HH_MM_SS );
		const endTimeSeconds = yield call( timeUtil.toSeconds, endTime, timeUtil.TIME_FORMAT_HH_MM_SS );
		const startDateTimeMoment = yield call( momentUtil.setTimeInSeconds, startDateMoment.clone(), startTimeSeconds );
		const endDateTimeMoment = yield call( momentUtil.setTimeInSeconds, endDateMoment.clone(), endTimeSeconds );
		const durationHasError = yield call( [ startDateTimeMoment, 'isSameOrAfter' ], endDateTimeMoment );

		if ( durationHasError ) {
			hasDurationError = true;
		}
	}

	yield put( actions.setRSVPHasDurationError( hasDurationError ) );
}

export function* handleRSVPStartDate( action ) {
	const { date, dayPickerInput } = action.payload;
	const startDateMoment = yield date ? call( momentUtil.toMoment, date ) : undefined;
	const startDate = yield date ? call( momentUtil.toDatabaseDate, startDateMoment ) : '';
	yield put( actions.setRSVPTempStartDate( startDate ) );
	yield put( actions.setRSVPTempStartDateInput( dayPickerInput.state.value ) );
	yield put( actions.setRSVPTempStartDateMoment( startDateMoment ) );
}

export function* handleRSVPEndDate( action ) {
	const { date, dayPickerInput } = action.payload;
	const endDateMoment = yield date ? call( momentUtil.toMoment, date ) : undefined;
	const endDate = yield date ? call( momentUtil.toDatabaseDate, endDateMoment ) : '';
	yield put( actions.setRSVPTempEndDate( endDate ) );
	yield put( actions.setRSVPTempEndDateInput( dayPickerInput.state.value ) );
	yield put( actions.setRSVPTempEndDateMoment( endDateMoment ) );
}

export function* handleRSVPStartTime( action ) {
	const startTime = yield call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM );
	yield put( actions.setRSVPTempStartTime( `${ startTime }:00` ) );
}

export function* handleRSVPStartTimeInput( action ) {
	const startTime = yield call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM );
	const startTimeMoment = yield call( momentUtil.toMoment, startTime, momentUtil.TIME_FORMAT, false );
	const startTimeInput = yield call( momentUtil.toTime, startTimeMoment );
	yield put( actions.setRSVPTempStartTimeInput( startTimeInput ) );
}

export function* handleRSVPEndTime( action ) {
	const endTime = yield call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM );
	yield put( actions.setRSVPTempEndTime( `${ endTime }:00` ) );
}

export function* handleRSVPEndTimeInput( action ) {
	const endTime = yield call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM );
	const endTimeMoment = yield call( momentUtil.toMoment, endTime, momentUtil.TIME_FORMAT, false );
	const endTimeInput = yield call( momentUtil.toTime, endTimeMoment );
	yield put( actions.setRSVPTempEndTimeInput( endTimeInput ) );
}

/**
 * Handles proper RSVP deletion and RSVP block removal upon moving RSVP
 *
 * @export
 */
export function* handleRSVPMove() {
	const rsvpId = yield select( selectors.getRSVPId );
	const modalTicketId = yield select( moveSelectors.getModalTicketId );

	if ( rsvpId === modalTicketId ) {
		const clientId = yield select( moveSelectors.getModalClientId );
		yield put( actions.deleteRSVP() );
		yield call( [ wpDispatch( 'core/editor' ), 'removeBlocks' ], [ clientId ] );
	}
}

//
// ─── HEADER IMAGE ───────────────────────────────────────────────────────────────
//

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

		const post_type = wpSelect('core/editor').getCurrentPostType();
		const rest_base = ( 'tribe_events' === post_type ) ? 'tribe_events' : 'posts';

		const { response } = yield call( api.wpREST, {
			path: `${ rest_base }/${ postId }`,
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

		const post_type = wpSelect('core/editor').getCurrentPostType();
		const rest_base = ( 'tribe_events' === post_type ) ? 'tribe_events' : 'posts';

		const { response } = yield call( api.wpREST, {
			path: `${ rest_base }/${ postId }`,
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

//
// ─── HANDLERS ───────────────────────────────────────────────────────────────────
//

export function* handler( action ) {
	switch ( action.type ) {
		case types.SET_RSVP_DETAILS:
			yield call( setRSVPDetails, action );
			break;

		case types.SET_RSVP_TEMP_DETAILS:
			yield call( setRSVPTempDetails, action );
			break;

		case types.INITIALIZE_RSVP:
			yield call( initializeRSVP );
			break;

		case types.HANDLE_RSVP_START_DATE:
			yield call( handleRSVPStartDate, action );
			yield call( handleRSVPDurationError );
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case types.HANDLE_RSVP_END_DATE:
			yield call( handleRSVPEndDate, action );
			yield call( handleRSVPDurationError );
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case types.HANDLE_RSVP_START_TIME:
			yield call( handleRSVPStartTime, action );
			yield call( handleRSVPStartTimeInput, action );
			yield call( handleRSVPDurationError );
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case types.HANDLE_RSVP_END_TIME:
			yield call( handleRSVPEndTime, action );
			yield call( handleRSVPEndTimeInput, action );
			yield call( handleRSVPDurationError );
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case types.FETCH_RSVP_HEADER_IMAGE:
			yield call( fetchRSVPHeaderImage, action );
			break;

		case types.UPDATE_RSVP_HEADER_IMAGE:
			yield call( updateRSVPHeaderImage, action );
			break;

		case types.DELETE_RSVP_HEADER_IMAGE:
			yield call( deleteRSVPHeaderImage );
			break;

		case MOVE_TICKET_SUCCESS:
			yield call( handleRSVPMove );
			break;

		default:
			break;
	}
}

/**
 * Temporary bandaid until datepickers allow blank state
 *
 * @export
 */
export function* setNonEventPostTypeEndDate() {
	yield take( [ types.INITIALIZE_RSVP ] );

	if ( yield call( isTribeEventPostType ) ) {
		return;
	}

	const tempEndMoment = yield select( selectors.getRSVPTempEndDateMoment );
	const endMoment = yield call( [ tempEndMoment, 'clone' ] );
	yield call( [ endMoment, 'add' ], 100, 'years' );
	const { date, dateInput, moment, time } = yield call( createDates, endMoment.toDate() );

	yield all( [
		put( actions.setRSVPTempEndDate( date ) ),
		put( actions.setRSVPTempEndDateInput( dateInput ) ),
		put( actions.setRSVPTempEndDateMoment( moment ) ),
		put( actions.setRSVPTempEndTime( time ) ),
		put( actions.setRSVPEndDate( date ) ),
		put( actions.setRSVPEndDateInput( dateInput ) ),
		put( actions.setRSVPEndDateMoment( moment ) ),
		put( actions.setRSVPEndTime( time ) ),
	] );
}

//
// ─── WATCHERS ───────────────────────────────────────────────────────────────────
//

export default function* watchers() {
	yield takeEvery( [
		types.SET_RSVP_DETAILS,
		types.SET_RSVP_TEMP_DETAILS,
		types.INITIALIZE_RSVP,
		types.HANDLE_RSVP_START_DATE,
		types.HANDLE_RSVP_END_DATE,
		types.HANDLE_RSVP_START_TIME,
		types.HANDLE_RSVP_END_TIME,
		types.FETCH_RSVP_HEADER_IMAGE,
		types.UPDATE_RSVP_HEADER_IMAGE,
		types.DELETE_RSVP_HEADER_IMAGE,
		MOVE_TICKET_SUCCESS,
	], handler );

	yield fork( handleEventStartDateChanges );
	yield fork( setNonEventPostTypeEndDate );
}
