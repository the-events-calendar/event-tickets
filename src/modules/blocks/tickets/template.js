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
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import TicketsDashboard from './dashboard/container';
import TicketsContainer from './container/container';
import TicketControls from './controls/container';
import './style.pcss';

class Tickets extends PureComponent {
	static propTypes = {
		canCreateTickets: PropTypes.bool,
		clientId: PropTypes.string,
		hasProviders: PropTypes.bool,
		hasRecurrenceRules: PropTypes.bool,
		header: PropTypes.string,
		isSelected: PropTypes.bool,
		isSettingsOpen: PropTypes.bool,
		noTicketsOnRecurring: PropTypes.bool,
		onBlockUpdate: PropTypes.func,
	};

	componentDidMount() {
		this.props.onBlockUpdate( this.props.isSelected );
	}

	componentDidUpdate( prevProps ) {
		if ( prevProps.isSelected !== this.props.isSelected ) {
			this.props.onBlockUpdate( this.props.isSelected );
		}
	}

	renderBlock() {
		const {
			isSelected,
			clientId,
			canCreateTickets,
		} = this.props;

		return (
			<Fragment>
				<TicketsContainer isSelected={ isSelected } />
				{ canCreateTickets && <TicketsDashboard isSelected={ isSelected } clientId={ clientId } /> }
				<TicketControls />
			</Fragment>
		);
	}

	renderBlockNotSupported() {
		const { clientId } = this.props;
		return (
			<div className="tribe-editor__not-supported-message">
				<p className="tribe-editor__not-supported-message-text">
					{ __( 'Tickets are not yet supported for on recurring events.', 'event-tickets' ) }
					<br />
					<a
						className="tribe-editor__not-supported-message-link"
						href="https://evnt.is/1b7a"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Read about our plans for future features.', 'event-tickets' ) }
					</a>
					<br />
					<Button variant="secondary" onClick={ () =>
						wp.data.dispatch( 'core/block-editor' ).removeBlock( clientId )
					}>
						{ __( 'Remove block', 'event-tickets' ) }
					</Button>
				</p>
			</div>
		);
	}

	renderContent() {
		if ( this.props.hasRecurrenceRules && this.props.noTicketsOnRecurring ) {
			return this.renderBlockNotSupported();
		}

		return this.renderBlock();
	}

	render() {
		const { isSelected, isSettingsOpen } = this.props;

		return (
			<div
				className={ classNames(
					'tribe-editor__tickets',
					{ 'tribe-editor__tickets--selected': isSelected },
					{ 'tribe-editor__tickets--settings-open': isSettingsOpen },
				) }
			>
				{this.renderContent()}
			</div>
		);
	}
}

export default Tickets;
