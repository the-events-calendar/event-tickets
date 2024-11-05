/**
 * External dependencies
 */
import React, { Fragment, PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	SettingsActionButton,
	AttendeesActionButton,
	OrdersActionButton,
} from '../action-buttons';
import NotSupportedMessage from '../not-supported-message/container';
import { ActionDashboard } from '@moderntribe/tickets/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import './style.pcss';
import { applyFilters } from '@wordpress/hooks';

// eslint-disable-next-line no-undef
const confirmLabel = sprintf(
	/* Translators: %s - the singular label for a ticket. */
	__('Add a %s', 'event-tickets'),
	TICKET_LABELS.ticket.singular
);

class TicketsDashboardAction extends PureComponent {
	static propTypes = {
		disableSettings: PropTypes.bool,
		hasCreatedTickets: PropTypes.bool,
		hasOrdersPage: PropTypes.bool,
		onConfirmClick: PropTypes.func,
		showConfirm: PropTypes.bool,
		showNotSupportedMessage: PropTypes.bool,
		clientId: PropTypes.string,
		isConfirmDisabled: PropTypes.bool,
	};

	constructor(props) {
		super(props);
		this.state = {
			isWarningOpen: false,
		};
	}

	onWarningClick = () => {
		this.setState({ isWarningOpen: !this.state.isWarningOpen });
	};

	getActions = () => {
		const { hasCreatedTickets, hasOrdersPage, disableSettings } =
			this.props;

		// Start with an empty set of actions.
		const actions = [];

		if (!disableSettings) {
			// eslint-disable-next-line react/jsx-key
			actions.push(<SettingsActionButton />);
		}

		if (hasCreatedTickets) {
			actions.push(<AttendeesActionButton />);

			if (hasOrdersPage) {
				actions.push(<OrdersActionButton />);
			}
		}

		/**
		 * Filters the actions that will be shown in the dashboard.
		 *
		 * @since 5.16.0
		 *
		 * @param {Array} actions The actions list that will be shown in the dashboard.
		 * @param {Object} props The component props.
		 */
		return applyFilters( 'tec.tickets.blocks.Tickets.TicketsDashboardAction.actions', actions, this.props );
	};

	render() {
		const { onConfirmClick, showConfirm, showNotSupportedMessage, isConfirmDisabled } =
			this.props;

		const actionDashboardClassName = classNames(
			'tribe-common',
			'tribe-editor__tickets__action-dashboard',
			{
				'tribe-editor__tickets__action-dashboard__no-border-bottom':
					showNotSupportedMessage,
			}
		);

		return (
			<Fragment>
				<ActionDashboard
					className={actionDashboardClassName}
					actions={this.getActions()}
					confirmLabel={confirmLabel}
					onConfirmClick={onConfirmClick}
					showCancel={false}
					showConfirm={showConfirm}
					isConfirmDisabled={isConfirmDisabled}
				/>
				{showNotSupportedMessage ? (
					<div className="tribe-editor__tickets__action-dashboard__not-supported-message">
						<div className="tickets-description">
							<div className="tribe-editor__tickets__container__helper__container">
								<NotSupportedMessage />
							</div>
						</div>
					</div>
				) : null}
			</Fragment>
		);
	}
}

export default TicketsDashboardAction;
