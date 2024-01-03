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
import './style.pcss';

const confirmLabel = __('Add a Ticket', 'event-tickets');

class TicketsDashboardAction extends PureComponent {
	static propTypes = {
		disableSettings: PropTypes.bool,
		hasCreatedTickets: PropTypes.bool,
		hasOrdersPage: PropTypes.bool,
		onConfirmClick: PropTypes.func,
		showConfirm: PropTypes.bool,
		showNotSupportedMessage: PropTypes.bool,
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

		return actions;
	};

	render() {
		const { onConfirmClick, showConfirm, showNotSupportedMessage } =
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
