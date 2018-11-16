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

<<<<<<< HEAD:src/modules/blocks/ticket/container-content/template.js
const TicketContainerContent = ( { blockId } ) => (
	<Fragment>
		<Capacity blockId={ blockId } />
		<AdvancedOptions blockId={ blockId } />
		<EcommerceOptions blockId={ blockId } />
		<AttendeesRegistration blockId={ blockId } />
	</Fragment>
=======
const TicketEditContent = ( { blockId, hasTicketsPlus } ) => (
	<div className="tribe-editor__ticket-container__content">
		<Capacity blockId={ blockId } />
		<AdvancedOptions blockId={ blockId } />
		<EcommerceOptions blockId={ blockId } />
		{ hasTicketsPlus && <AttendeesRegistration blockId={ blockId } /> }
	</div>
>>>>>>> release/F18.3:src/modules/blocks/ticket/edit-container/content/template.js
);

TicketContainerContent.propTypes = {
	blockId: PropTypes.string.isRequired,
	hasTicketsPlus: PropTypes.bool,
};

export default TicketContainerContent;
