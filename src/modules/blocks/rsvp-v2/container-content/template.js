/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import '../../rsvp/container-content/style.pcss';

const RSVPContainerContent = ( { isAddEditOpen } ) => {
	if ( ! isAddEditOpen ) {
		return null;
	}

	// TODO: Restore <RSVPCreateForm /> when ../create-form/template lands in PR #4298.
	return null;
};

RSVPContainerContent.propTypes = {
	isAddEditOpen: PropTypes.bool,
};

export default RSVPContainerContent;
