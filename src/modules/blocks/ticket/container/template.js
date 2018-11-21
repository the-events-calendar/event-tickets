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
	TicketActive,
	TicketInactive,
} from '@moderntribe/tickets/icons';

const ClockIcon = ( { isDisabled } ) => (
	isDisabled ? <ClockInactive /> : <ClockActive />
);

const TicketIcon = ( { isDisabled } ) => (
	isDisabled ? <TicketInactive /> : <TicketActive />
);

const TicketContainerIcon = ( { isDisabled, isFuture } ) => (
	isFuture ? <ClockIcon isDisabled={ isDisabled } /> : <TicketIcon isDisabled={ isDisabled } />
);

TicketContainerIcon.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
};

const TicketContainer = ( { blockId, isDisabled, isFuture, isSelected } ) => (
	<ContainerPanel
		className="tribe-editor__ticket__container"
		layout={ LAYOUT.ticket }
		icon={ <TicketContainerIcon isDisabled={ isDisabled } isFuture={ isFuture } /> }
		header={ <TicketContainerHeader blockId={ blockId } isSelected={ isSelected } /> }
		content={ <TicketContainerContent blockId={ blockId } /> }
	/>
);

TicketContainer.propTypes = {
	blockId: PropTypes.string.isRequired,
	isDisabled: PropTypes.bool,
	isFuture: PropTypes.bool,
	isTicketFuture: PropTypes.bool,
	isSelected: PropTypes.bool,
};

export default TicketContainer;
