/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import RSVPCreateForm from '../create-form/template';
import '../../rsvp/container-content/style.pcss';

const RSVPContainerContent = ( { clientId, isAddEditOpen } ) => {
	if ( ! isAddEditOpen ) {
		return null;
	}

	return <RSVPCreateForm clientId={ clientId } />;
};

RSVPContainerContent.propTypes = {
	clientId: PropTypes.string,
	isAddEditOpen: PropTypes.bool,
};

export default RSVPContainerContent;
