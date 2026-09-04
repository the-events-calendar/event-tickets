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

const RSVPContainerContent = ( { clientId, hasTicketsPlus, isAddEditOpen } ) => {
	if ( ! isAddEditOpen ) {
		return null;
	}

	return <RSVPCreateForm clientId={ clientId } hasTicketsPlus={ hasTicketsPlus } />;
};

RSVPContainerContent.propTypes = {
	clientId: PropTypes.string,
	hasTicketsPlus: PropTypes.bool,
	isAddEditOpen: PropTypes.bool,
};

export default RSVPContainerContent;
