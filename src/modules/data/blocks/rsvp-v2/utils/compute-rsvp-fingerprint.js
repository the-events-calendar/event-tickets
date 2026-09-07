/**
 * Internal dependencies
 */
import * as selectors from '../../rsvp-shared/selectors';

/**
 * Builds a stable fingerprint of editable RSVP state for block attribute sync.
 *
 * @param {Object} state Redux state.
 * @return {string} Fingerprint string.
 */
export const computeRsvpFingerprint = ( state ) => {
	const parts = [
		selectors.getRSVPId( state ),
		selectors.getRSVPCreated( state ) ? '1' : '0',
		selectors.getRSVPHasChanges( state ) ? '1' : '0',
		selectors.getRSVPTempCapacity( state ),
		selectors.getRSVPTempNotGoingResponses( state ) ? '1' : '0',
		selectors.getRSVPTempStartDate( state ),
		selectors.getRSVPTempEndDate( state ),
		selectors.getRSVPTempStartTime( state ),
		selectors.getRSVPTempEndTime( state ),
		String( selectors.getRSVPGoingCount( state ) ?? '' ),
		String( selectors.getRSVPNotGoingCount( state ) ?? '' ),
	];

	return parts.join( '|' );
};
