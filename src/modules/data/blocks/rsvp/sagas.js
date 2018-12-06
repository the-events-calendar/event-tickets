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
import {
	globals,
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';

//
// ─── RSVP DETAILS ───────────────────────────────────────────────────────────────
//

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

export function* initializeRSVP() {
	const datePickerFormat = globals.tecDateSettings().datepickerFormat;
	const publishDate = yield call( [ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ], 'date' );
	const startMoment = yield call( momentUtil.toMoment, publishDate );
	const startDate = yield call( momentUtil.toDate, startMoment );
	const startDateInput = yield datePickerFormat
		? call( momentUtil.toDate, startMoment, datePickerFormat )
		: call( momentUtil.toDate, startMoment );
	const startTime = yield call( momentUtil.toDatabaseTime, startMoment );
	const startTimeInput = yield call( momentUtil.toTime, startMoment );

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
		// NOTE: This requires TEC to be installed, if not installed, do not set an end date
		const eventStart = yield select( window.tribe.events.data.blocks.datetime.selectors.getStart ); // RSVP window should end when event starts... ideally
		const endMoment = yield call( momentUtil.toMoment, eventStart );
		const endDate = yield call( momentUtil.toDate, endMoment );
		const endDateInput = yield datePickerFormat
			? call( momentUtil.toDate, endMoment, datePickerFormat )
			: call( momentUtil.toDate, endMoment );
		const endTime = yield call( momentUtil.toDatabaseTime, endMoment );
		const endTimeInput = yield call( momentUtil.toTime, endMoment );

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
	} catch ( err ) {
		// ¯\_(ツ)_/¯
	}
}

//
// ─── DATE & TIME ────────────────────────────────────────────────────────────────
//

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

//
// ─── MOVE ───────────────────────────────────────────────────────────────────────
//

export function* handleRSVPMove() {
	const rsvpId = yield select( selectors.getRSVPId );
	const modalTicketId = yield select( moveSelectors.getModalTicketId );

	if ( rsvpId === modalTicketId ) {
		const blockId = yield select( moveSelectors.getModalBlockId );
		yield put( actions.deleteRSVP() );
		yield call( [ wpDispatch( 'core/editor' ), 'removeBlocks' ], [ blockId ] );
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
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case types.HANDLE_RSVP_END_DATE:
			yield call( handleRSVPEndDate, action );
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case types.HANDLE_RSVP_START_TIME:
			yield call( handleRSVPStartTime, action );
			yield call( handleRSVPStartTimeInput, action );
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case types.HANDLE_RSVP_END_TIME:
			yield call( handleRSVPEndTime, action );
			yield call( handleRSVPEndTimeInput, action );
			yield put( actions.setRSVPHasChanges( true ) );
			break;

		case MOVE_TICKET_SUCCESS:
			yield call( handleRSVPMove );
			break;

		default:
			break;
	}
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
		MOVE_TICKET_SUCCESS,
	], handler );
}
