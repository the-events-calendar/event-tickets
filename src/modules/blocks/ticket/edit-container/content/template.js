/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Capacity from './capacity/container';
import AdvancedOptions from './advanced-options/template';
import EcommerceOptions from './ecommerce-options/container';
import AttendeesRegistration from './attendees-registration/container';

import './style.pcss';

const TicketEditContent = ( { blockId } ) => (
	<div className="tribe-editor__ticket-container__content">
		<Capacity blockId={ blockId } />
		<AdvancedOptions blockId={ blockId } />
		<EcommerceOptions blockId={ blockId } />
		<AttendeesRegistration blockId={ blockId } />
	</div>
);

TicketEditContent.propTypes = {
	blockId: PropTypes.string.isRequired,
};

export default TicketEditContent;
