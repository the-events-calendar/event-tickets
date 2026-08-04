/**
 * V2 RSVP Duration Picker Container
 *
 * Wraps the shared duration picker and autosaves changes via REST.
 */

/**
 * Internal dependencies
 */
import { actions, selectors } from '../../../data/blocks/rsvp-v2';
import { createDurationPickerContainer } from '../../rsvp-shared/utils/create-duration-picker-container';
import { schedulePersistRSVP } from '../utils/schedule-persist-rsvp';

export default createDurationPickerContainer( {
	actions,
	selectors,
	autosave: true,
	onAutosave: schedulePersistRSVP,
} );
