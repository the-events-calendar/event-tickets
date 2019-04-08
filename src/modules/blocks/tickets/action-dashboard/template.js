/**
 * External dependencies
 */
import React, { Fragment, PureComponent } from 'react';
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
import { ActionDashboard } from '@moderntribe/tickets/elements';
import { Button } from '@moderntribe/common/elements';
import './style.pcss';

const confirmLabel = __( 'Add Tickets', 'event-tickets' );

const TicketsWarningButton = ( { onClick } ) => (
	<Button
		className="tribe-editor__tickets__warning-button"
		onClick={ onClick }
	>
		<Dashicon
			className="tribe-editor__tickets__warning-button-icon"
			icon="info-outline"
		/>
		<span className="tribe-editor__tickets__warning-button-text">
			{ __( 'Warning', 'event-tickets' ) }
		</span>
	</Button>
);

class TicketsDashboardAction extends PureComponent {
	static propTypes = {
		hasCreatedTickets: PropTypes.bool,
		hasOrdersPage: PropTypes.bool,
		hasRecurrenceRules: PropTypes.bool,
		hasTicketsPlus: PropTypes.bool,
		onConfirmClick: PropTypes.func,
	};

	constructor( props ) {
		super( props );
		this.state = {
			isWarningOpen: false,
		};
	}

	onWarningClick = () => {
		this.setState( { isWarningOpen: ! this.state.isWarningOpen } );
	};

	getActions = () => {
		const {
			hasCreatedTickets,
			hasOrdersPage,
			hasRecurrenceRules,
			hasTicketsPlus,
		} = this.props;

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
			actions.push( <TicketsWarningButton onClick={ this.onWarningClick } /> );
		}
		return actions;
	};

	render() {
		const { onConfirmClick } = this.props;

		return (
			<Fragment>
				<ActionDashboard
					className="tribe-editor__tickets__action-dashboard"
					actions={ this.getActions() }
					confirmLabel={ confirmLabel }
					onConfirmClick={ onConfirmClick }
					showCancel={ false }
				/>
				{ this.state.isWarningOpen && (
					<div className="tribe-editor__tickets__warning">
						{ __( 'This is a recurring event. If you add tickets they will only show up on the next upcoming event in the recurrence pattern. The same ticket form will appear across all events in the series. Please configure your events accordingly.', 'event-tickets' ) }
					</div>
				) }
			</Fragment>
		)
	}
};

export default TicketsDashboardAction;
