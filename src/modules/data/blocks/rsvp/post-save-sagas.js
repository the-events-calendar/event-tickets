/* eslint-disable max-len */

/**
 * External Dependencies
 */
import { put, call, select, take, fork, takeEvery, all } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as types from '../rsvp-shared/types';
import * as selectors from '../rsvp-shared/selectors';
import { updateRSVP } from './thunks';
import { createWPEditorSavingChannel } from '../../shared/sagas';

/**
 * Allows the RSVP to be saved at the same time a post is being saved.
 * Avoids the user having to open up the RSVP block, and then click update again there, when changing the event start date.
 *
 * @export
 * @yield
 */
export function* saveRSVPWithPostSave() {
	let saveChannel;
	try {
		if ( yield select( selectors.getRSVPCreated ) ) {
			saveChannel = yield call( createWPEditorSavingChannel );
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

			yield put( updateRSVP( payload ) );
		}
	} catch ( error ) {
		console.error( error );
	} finally {
		if ( saveChannel ) {
			yield call( [ saveChannel, 'close' ] );
		}
	}
}

export function* onRSVPSaleEndSyncedToEventStart() {
	yield fork( saveRSVPWithPostSave );
}

export default function* watchers() {
	yield takeEvery( types.RSVP_SALE_END_SYNCED_TO_EVENT_START, onRSVPSaleEndSyncedToEventStart );
}
