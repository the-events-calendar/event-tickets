/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	SettingsActionButton,
	AttendeesActionButton,
	OrdersActionButton,
} from '@moderntribe/tickets/blocks/tickets/action-buttons';
import { ActionDashboard, LabelWithTooltip } from '@moderntribe/tickets/elements';
import './style.pcss';

const confirmLabel = __( 'Add Tickets', 'event-tickets' );

const TicketsWarningTooltipLabel = () => (
	<Dashicon
		className="tribe-editor__tickets__warning-tooltip-label"
		icon="info-outline"
	/>
);

const TicketsWarning = () => (
	<LabelWithTooltip
		className="tribe-editor__tickets__warning"
		label={ __( 'Warning', 'event-tickets' ) }
		tooltipLabel={ <TicketsWarningTooltipLabel /> }
		tooltipText={ __( 'This is a recurring event. If you add tickets they will only show up on the next upcoming event in the recurrence pattern. The same ticket form will appear across all events in the series. Please configure your events accordingly.', 'event-tickets' ) }
	/>
);

const TicketsDashboardAction = ( {
	hasCreatedTickets,
	hasOrdersPage,
	hasRecurrenceRules,
	hasTicketsPlus,
	onConfirmClick,
} ) => {
	const getActions = () => {
		const actions = [ <SettingsActionButton /> ];
		if ( hasCreatedTickets ) {
			if ( hasTicketsPlus ) {
				actions.push( <AttendeesActionButton /> );
			}
			if ( hasOrdersPage ) {
				actions.push( <OrdersActionButton /> );
			}
		}
		if ( hasRecurrenceRules ) {
			actions.push( <TicketsWarning /> );
		}
		return actions;
	};

	return (
		<ActionDashboard
			className="tribe-editor__tickets__action-dashboard"
			actions={ getActions() }
			confirmLabel={ confirmLabel }
			onConfirmClick={ onConfirmClick }
			showCancel={ false }
		/>
	);
};

TicketsDashboardAction.propTypes = {
	hasCreatedTickets: PropTypes.bool,
	hasOrdersPage: PropTypes.bool,
	hasRecurrenceRules: PropTypes.bool,
	hasTicketsPlus: PropTypes.bool,
	onConfirmClick: PropTypes.func,
};

export default TicketsDashboardAction;
