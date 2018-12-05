/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Capacity from './capacity/container';
import AdvancedOptions from './advanced-options/container';
import EcommerceOptions from './ecommerce-options/container';
import AttendeesRegistration from './attendees-registration/container';
import './style.pcss';

const TicketContainerContent = ( { blockId, hasTicketsPlus } ) => (
	<Fragment>
		<Capacity blockId={ blockId } />
		<AdvancedOptions blockId={ blockId } />
		<EcommerceOptions blockId={ blockId } />
		{ hasTicketsPlus && <AttendeesRegistration blockId={ blockId } /> }
	</Fragment>
);

TicketContainerContent.propTypes = {
	blockId: PropTypes.string.isRequired,
	hasTicketsPlus: PropTypes.bool,
};

export default TicketContainerContent;
