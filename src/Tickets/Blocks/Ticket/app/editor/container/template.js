/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { ContainerPanel } from '@moderntribe/tickets/elements';
import TicketContainerHeader from '../container-header/template';
import TicketContainerContent from '../container-content/container';
import { LAYOUT } from '@moderntribe/tickets/elements/container-panel';
import {
	ClockActive,
	ClockInactive,
	TicketActive,
	TicketInactive,
} from '@moderntribe/tickets/icons';

const ClockIcon = ( { isDisabled } ) => (
	isDisabled ? <ClockInactive /> : <ClockActive />
);

ClockIcon.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
};

const TicketIcon = ( { isDisabled } ) => (
	isDisabled ? <TicketInactive /> : <TicketActive />
);

TicketIcon.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
};

const TicketContainerIcon = ( { isDisabled, isFuture, isPast } ) => (
	isFuture || isPast
		? <ClockIcon isDisabled={ isDisabled } />
		: <TicketIcon isDisabled={ isDisabled } />
);

TicketContainerIcon.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
	isFuture: PropTypes.bool,
	isPast: PropTypes.bool,
};

const TicketContainer = ( { clientId, isDisabled, isFuture, isPast, isSelected, isOnSale } ) => (
	<ContainerPanel
		className={ classNames(
			'tribe-editor__ticket__container',
			{ 'tribe-editor__ticket-on-sale': isOnSale },
		) }
		layout={ LAYOUT.ticket }
		icon={
			<TicketContainerIcon
				isDisabled={ isDisabled }
				isFuture={ isFuture }
				isPast={ isPast }
			/>
		}
		header={ <TicketContainerHeader clientId={ clientId } isSelected={ isSelected } isOnSale={ isOnSale }/> }
		content={ <TicketContainerContent clientId={ clientId } /> }
	/>
);

TicketContainer.propTypes = {
	clientId: PropTypes.string.isRequired,
	isDisabled: PropTypes.bool,
	isFuture: PropTypes.bool,
	isPast: PropTypes.bool,
	isSelected: PropTypes.bool,
	isOnSale: PropTypes.bool,
};

export default TicketContainer;
