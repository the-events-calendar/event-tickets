/**
 * External Dependencies
 */
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';
import { put, call, all, select, takeEvery, take } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import { globals, moment as momentUtil } from '@moderntribe/common/utils';
import { editor } from '@moderntribe/common/data';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';

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
	] );
}

export function* createDates( date ) {
	const datePickerFormat = globals.tecDateSettings().datepickerFormat;
	const moment = yield call( momentUtil.toMoment, date );
	const currentDate = yield call( momentUtil.toDate, moment );
	const dateInput = yield datePickerFormat
		? call( momentUtil.toDate, moment, datePickerFormat )
		: call( momentUtil.toDate, moment );
	const time = yield call( momentUtil.toDatabaseTime, moment );

	return {
		moment,
		date: currentDate,
		dateInput,
		time,
	};
}

export function* initializeRSVP() {
	const publishDate = wpSelect( 'core/editor' ).getEditedPostAttribute( 'date' );
	const {
		moment: startMoment,
		date: startDate,
		dateInput: startDateInput,
		time: startTime,
	} = yield call( createDates, publishDate );

	yield all( [
		put( actions.setRSVPTempStartDate( startDate ) ),
		put( actions.setRSVPTempStartDateInput( startDateInput ) ),
		put( actions.setRSVPTempStartDateMoment( startMoment ) ),
		put( actions.setRSVPTempStartTime( startTime ) ),
	] );

	try {
		// NOTE: This requires TEC to be installed, if not installed, do not set an end date
		const eventStart = yield select( window.tribe.events.data.blocks.datetime.selectors.getStart ); // RSVP window should end when event starts... ideally
		const {
			moment: endMoment,
			date: endDate,
			dateInput: endDateInput,
			time: endTime,
		} = yield call( createDates, eventStart );

		yield all( [
			put( actions.setRSVPTempEndDate( endDate ) ),
			put( actions.setRSVPTempEndDateInput( endDateInput ) ),
			put( actions.setRSVPTempEndDateMoment( endMoment ) ),
			put( actions.setRSVPTempEndTime( endTime ) ),
			put( actions.setRSVPEndDate( endDate ) ),
			put( actions.setRSVPEndDateInput( endDateInput ) ),
			put( actions.setRSVPEndDateMoment( endMoment ) ),
			put( actions.setRSVPEndTime( endTime ) ),
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

export function* syncRSVPSaleEndWithEventStart() {
	try {
		const postType = yield call( [ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ], 'type' );
		const tempEndMoment = yield select( selectors.getRSVPTempEndDateMoment );
		const endMoment = yield select( selectors.getRSVPEndDateMoment );

		// NOTE: Mutation
		// Convert to use local timezone
		yield all( [
			call( [ tempEndMoment, 'local' ] ),
			call( [ endMoment, 'local' ] ),
		] );

		// If initial end and current end are the same, the RSVP has not been modified
		const isNotEdited = yield call( [ tempEndMoment, 'isSame' ], endMoment, 'minute' );

		console.warn( isNotEdited, tempEndMoment.format(), endMoment.format() );

		if ( postType === editor.EVENT && isNotEdited ) {
			const eventStart = yield select( window.tribe.events.data.blocks.datetime.selectors.getStart );
			const {
				moment: endDateMoment,
				date: endDate,
				dateInput: endDateInput,
				time: endTime,
			} = yield call( createDates, eventStart );

			yield all( [
				put( actions.setRSVPTempEndDate( endDate ) ),
				put( actions.setRSVPTempEndDateInput( endDateInput ) ),
				put( actions.setRSVPTempEndDateMoment( endDateMoment ) ),
				put( actions.setRSVPTempEndTime( endTime ) ),
				put( actions.setRSVPEndDate( endDate ) ),
				put( actions.setRSVPEndDateInput( endDateInput ) ),
				put( actions.setRSVPEndDateMoment( endDateMoment ) ),
				put( actions.setRSVPEndTime( endTime ) ),
			] );
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		console.warn( error );
	}
}

export default function* watchers() {
	yield takeEvery( types.SET_RSVP_DETAILS, setRSVPDetails );
	yield takeEvery( types.SET_RSVP_TEMP_DETAILS, setRSVPTempDetails );
	yield takeEvery( types.INITIALIZE_RSVP, initializeRSVP );
	yield takeEvery( MOVE_TICKET_SUCCESS, handleRSVPMove );

	try {
		if ( window.tribe.events ) {
			yield take( [ types.INITIALIZE_RSVP, types.SET_RSVP_DETAILS ] );
			const { SET_START_DATE_TIME, SET_START_TIME } = window.tribe.events.data.blocks.datetime.types;
			yield takeEvery( [ SET_START_DATE_TIME, SET_START_TIME ], syncRSVPSaleEndWithEventStart );
		}
	} catch ( error ) {

	}
}
