/**
 * External dependencies
 */
import React, { Fragment, PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { Card } from '@moderntribe/tickets/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import NotSupportedMessage from './not-supported-message/container';
import TicketsDashboard from './dashboard/container';
import TicketsContainer from './container/container';
import TicketControls from './controls/container';
import Uneditable from './uneditable/container';
import './style.pcss';

class Tickets extends PureComponent {
	static propTypes = {
		Warning: PropTypes.elementType,
		canCreateTickets: PropTypes.bool,
		clientId: PropTypes.string,
		hasRecurrenceRules: PropTypes.bool,
		isSelected: PropTypes.bool,
		isSettingsOpen: PropTypes.bool,
		noTicketsOnRecurring: PropTypes.bool,
		onBlockUpdate: PropTypes.func,
		showWarning: PropTypes.bool,
	};

	componentDidMount() {
		this.props.onBlockUpdate(this.props.isSelected);
	}

	componentDidUpdate(prevProps) {
		if (prevProps.isSelected !== this.props.isSelected) {
			this.props.onBlockUpdate(this.props.isSelected);
		}
	}

	renderBlock() {
		const {
			isSelected,
			clientId,
			canCreateTickets,
			attributes: { tickets: ticketsJSON = '[]' },
		} = this.props;

		let tickets = [];
		try {
			tickets = JSON.parse(ticketsJSON) || [];
		} catch (e) {
			// Do nothing.
		}

		return (
			<Fragment>
				<TicketsContainer isSelected={isSelected} tickets={tickets} />
				{canCreateTickets && (
					<TicketsDashboard
						isSelected={isSelected}
						clientId={clientId}
					/>
				)}
				<TicketControls />
			</Fragment>
		);
	}

	renderBlockNotSupported() {
		const {
			attributes: { tickets: ticketsJSON = '[]' },
			showUneditableTickets,
			showWarning,
			Warning,
		} = this.props;

		let tickets = [];
		try {
			tickets = JSON.parse(ticketsJSON) || [];
		} catch (e) {
			// Do nothing.
		}

		return (
			<>
				<Card
					className="tribe-editor__card tribe-editor__not-supported-message"
					header={TICKET_LABELS.ticket.plural}
				>
					<div className="tribe-editor__title__help-messages">
						{showWarning && <Warning />}
					</div>
					{showUneditableTickets && (
						<Uneditable
							cardClassName="tribe-editor__uneditable__card"
							tickets={tickets}
						/>
					)}
					{showWarning && (
						<div className="tickets-description">
							<div className="tribe-editor__tickets__container__helper__container">
								<NotSupportedMessage />
							</div>
						</div>
					)}
				</Card>
			</>
		);
	}

	renderContent() {
		if (this.props.hasRecurrenceRules && this.props.noTicketsOnRecurring) {
			return this.renderBlockNotSupported();
		}

		return this.renderBlock();
	}

	render() {
		const { isSelected, isSettingsOpen } = this.props;

		return (
			<div
				className={classNames(
					'tribe-editor__tickets',
					{ 'tribe-editor__tickets--selected': isSelected },
					{ 'tribe-editor__tickets--settings-open': isSettingsOpen }
				)}
			>
				{this.renderContent()}
			</div>
		);
	}
}

export default Tickets;
