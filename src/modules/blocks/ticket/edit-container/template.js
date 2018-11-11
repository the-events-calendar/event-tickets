/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { ContainerPanel } from '@moderntribe/tickets/elements';
import { LAYOUT } from '@moderntribe/tickets/elements/container-panel/element';
import StatusIcons from '@moderntribe/tickets/blocks/ticket/display-container/status-icon/element';
import TicketHeader from './header/container';
import TicketContent from './content/template';

const TicketEditContainer = ( { blockId, isTicketDisabled, expires } ) => (
	<ContainerPanel
		className="tribe-editor__edit-ticket-container"
		layout={ LAYOUT.ticket }
		icon={ <StatusIcons expires={ expires } disabled={ isTicketDisabled } /> }
		header={ <TicketHeader blockId={ blockId } /> }
		content={ <TicketContent blockId={ blockId } /> }
	/>
);

TicketEditContainer.propTypes = {
	blockId: PropTypes.string.isRequired,
	expires: PropTypes.bool,
	isDisabled: PropTypes.bool,
};

export default TicketEditContainer;
