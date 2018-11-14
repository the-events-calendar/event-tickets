/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { ContainerPanel } from '@moderntribe/tickets/elements';
import { LAYOUT } from '@moderntribe/tickets/elements/container-panel/element';
import StatusIcons from '@moderntribe/tickets/blocks/ticket/display-container/status-icon/element';
import TicketDisplay from './display/container';

const TicketDisplayContainer = ( { isTicketDisabled, expires, isSelected, blockId } ) => {
	return (
		<ContainerPanel
			className="tribe-editor__display-ticket-container"
			layout={ LAYOUT.ticket }
			icon={ <StatusIcons expires={ expires } disabled={ isTicketDisabled } /> }
			header={ <TicketDisplay blockId={ blockId } isSelected={ isSelected } /> }
		/>
	);
};

TicketDisplayContainer.propTypes = {
	expires: PropTypes.bool,
	isSelected: PropTypes.bool,
};

TicketDisplayContainer.defaultProps = {
	expires: true,
	isSelected: false,
};

export default TicketDisplayContainer;
