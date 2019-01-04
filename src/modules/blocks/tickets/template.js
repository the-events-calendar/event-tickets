/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import TicketsDashboard from './dashboard/container';
import TicketsContainer from './container/container';
import TicketControls from './controls/container';
import './style.pcss';

class Tickets extends PureComponent {
	static propTypes = {
		isSelected: PropTypes.bool,
		canCreateTickets: PropTypes.bool,
		isSettingsOpen: PropTypes.bool,
		clientId: PropTypes.string,
		header: PropTypes.string,
	};

	render() {
		const {
			isSelected,
			canCreateTickets,
			isSettingsOpen,
			clientId,
		} = this.props;

		return (
			<div
				className={ classNames(
					'tribe-editor__tickets',
					{ 'tribe-editor__tickets--selected': isSelected },
					{ 'tribe-editor__tickets--settings-open': isSettingsOpen },
				) }
			>
				<TicketsContainer isSelected={ isSelected } />
				{ canCreateTickets && <TicketsDashboard isSelected={ isSelected } clientId={ clientId } /> }
				<TicketControls />
			</div>
		);
	}
}

export default Tickets;
