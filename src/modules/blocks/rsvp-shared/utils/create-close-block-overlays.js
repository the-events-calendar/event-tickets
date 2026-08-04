/**
 * Internal dependencies
 */
import { closeAllDatePickers } from '@moderntribe/common/utils/date-picker-popover-state';
import { hideModal } from '../../../data/shared/move/actions';
import { dispatchCloseRsvpOverlays } from './close-overlays';

/**
 * Creates a handler that closes every RSVP block overlay backed by Redux or portals.
 *
 * @param {Object}   options            Factory options.
 * @param {Function} options.dispatch   Redux dispatch function.
 * @param {Object}   options.actions    RSVP block actions module.
 * @param {boolean}  options.closeSettings       Whether to close the V1 settings dashboard.
 * @param {boolean}  options.closeAttendeeModal  Whether to close the attendee information modal.
 * @return {Function} Redux action dispatcher.
 */
export const createCloseBlockOverlays = ( {
	dispatch,
	actions,
	closeSettings = false,
	closeAttendeeModal = true,
} ) => () => {
	dispatch( actions.setRSVPIsAddEditOpen( false ) );

	if ( closeSettings ) {
		dispatch( actions.setRSVPSettingsOpen( false ) );
	}

	if ( closeAttendeeModal ) {
		dispatch( actions.setRSVPIsModalOpen( false ) );
	}

	dispatch( hideModal() );
	closeAllDatePickers();
	dispatchCloseRsvpOverlays();
};
