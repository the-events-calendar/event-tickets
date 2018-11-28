/**
 * External Dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';
import { put, call, all, select, takeEvery } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import { moment as momentUtil } from '@moderntribe/common/utils';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';

export function* setRSVPDetails( action ) {
	const {
		title,
		description,
		capacity,
		notGoingResponses,
		startDate,
		startDateObj,
		startTime,
		endDate,
		endDateObj,
		endTime,
	} = action.payload;
	yield all( [
		put( actions.setRSVPTitle( title ) ),
		put( actions.setRSVPDescription( description ) ),
		put( actions.setRSVPCapacity( capacity ) ),
		put( actions.setRSVPNotGoingResponses( notGoingResponses ) ),
		put( actions.setRSVPStartDate( startDate ) ),
		put( actions.setRSVPStartDateObj( startDateObj ) ),
		put( actions.setRSVPStartTime( startTime ) ),
		put( actions.setRSVPEndDate( endDate ) ),
		put( actions.setRSVPEndDateObj( endDateObj ) ),
		put( actions.setRSVPEndTime( endTime ) ),
	] );
}

export function* setRSVPTempDetails( action ) {
	const {
		tempTitle,
		tempDescription,
		tempCapacity,
		tempNotGoingResponses,
		tempStartDate,
		tempStartDateObj,
		tempStartTime,
		tempEndDate,
		tempEndDateObj,
		tempEndTime,
	} = action.payload;
	yield all( [
		put( actions.setRSVPTempTitle( tempTitle ) ),
		put( actions.setRSVPTempDescription( tempDescription ) ),
		put( actions.setRSVPTempCapacity( tempCapacity ) ),
		put( actions.setRSVPTempNotGoingResponses( tempNotGoingResponses ) ),
		put( actions.setRSVPTempStartDate( tempStartDate ) ),
		put( actions.setRSVPTempStartDateObj( tempStartDateObj ) ),
		put( actions.setRSVPTempStartTime( tempStartTime ) ),
		put( actions.setRSVPTempEndDate( tempEndDate ) ),
		put( actions.setRSVPTempEndDateObj( tempEndDateObj ) ),
		put( actions.setRSVPTempEndTime( tempEndTime ) ),
	] );
}

export function* initializeRSVP() {
	const publishDate = wpSelect( 'core/editor' ).getEditedPostAttribute( 'date' );
	const startMoment = yield call( momentUtil.toMoment, publishDate );
	const startDate = yield call( momentUtil.toDate, startMoment );
	const startTime = yield call( momentUtil.toTime24Hr, startMoment );
	const startDateObj = new Date( startDate );

	yield all( [
		put( actions.setRSVPTempStartDate( startDate ) ),
		put( actions.setRSVPTempStartDateObj( startDateObj ) ),
		put( actions.setRSVPTempStartTime( startTime ) ),
	] );

	try {
		// NOTE: This requires TEC to be installed, if not installed, do not set an end date
		const eventStart = yield select( window.tribe.events.data.blocks.datetime.selectors.getStart ); // RSVP window should end when event starts... ideally
		const endMoment = yield call( momentUtil.toMoment, eventStart );
		const endDate = yield call( momentUtil.toDate, endMoment );
		const endTime = yield call( momentUtil.toTime24Hr, endMoment );
		const endDateObj = new Date( endDate );

		yield all( [
			put( actions.setRSVPTempEndDate( endDate ) ),
			put( actions.setRSVPTempEndDateObj( endDateObj ) ),
			put( actions.setRSVPTempEndTime( endTime ) ),
		] );
	} catch ( err ) {
		// ¯\_(ツ)_/¯
	}
}

export function* handleRSVPMove() {
	const rsvpId = yield select( selectors.getRSVPId );
	const modalTicketId = yield select( moveSelectors.getModalTicketId );

	if ( rsvpId === modalTicketId ) {
		const blockId = yield select( moveSelectors.getModalBlockId );
		yield put( actions.deleteRSVP() );
		yield call( [ wpDispatch( 'core/editor' ), 'removeBlocks' ], [ blockId ] );
	}
}

export default function* watchers() {
	yield takeEvery( types.SET_RSVP_DETAILS, setRSVPDetails );
	yield takeEvery( types.SET_RSVP_TEMP_DETAILS, setRSVPTempDetails );
	yield takeEvery( types.INITIALIZE_RSVP, initializeRSVP );
	yield takeEvery( MOVE_TICKET_SUCCESS, handleRSVPMove );
}
