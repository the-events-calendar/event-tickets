/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { ContainerPanel } from '@moderntribe/tickets/elements';
import TicketContainerHeader from '@moderntribe/tickets/blocks/ticket/container-header/template';
import TicketContainerContent from '@moderntribe/tickets/blocks/ticket/container-content/template';
import { LAYOUT } from '@moderntribe/tickets/elements/container-panel/element';
import {
	ClockActive,
	ClockInactive,
} from '@moderntribe/tickets/icons';

const TicketContainerIcon = ( { isDisabled } ) => (
	isDisabled ? <ClockInactive /> : <ClockActive />
);

TicketContainerIcon.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
};

const TicketContainer = ( { blockId, isDisabled, isSelected } ) => (
	<ContainerPanel
		className="tribe-editor__ticket__container"
		layout={ LAYOUT.ticket }
		icon={ <TicketContainerIcon isDisabled={ isDisabled } /> }
		header={ <TicketContainerHeader blockId={ blockId } isSelected={ isSelected } /> }
		content={ <TicketContainerContent blockId={ blockId } /> }
	/>
);

TicketContainer.propTypes = {
	blockId: PropTypes.string.isRequired,
	isDisabled: PropTypes.bool,
	isSelected: PropTypes.bool,
};

export default TicketContainer;
