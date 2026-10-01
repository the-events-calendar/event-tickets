/**
 * Internal dependencies
 */
import { actions, selectors } from '../../../data/blocks/rsvp';
import { createDurationPickerContainer } from '../../rsvp-shared/utils/create-duration-picker-container';

export default createDurationPickerContainer( {
	actions,
	selectors,
	autosave: false,
} );
