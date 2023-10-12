/**
 * External dependencies
 */
import React, { Fragment, PureComponent } from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MoveDelete from '@moderntribe/tickets/blocks/rsvp/move-delete/container';
import { ActionDashboard } from '@moderntribe/tickets/elements';
import './style.pcss';

const confirmLabel = ( created ) => (
	created
		? __( 'Update RSVP', 'event-tickets' )
		: __( 'Create RSVP', 'event-tickets' )
);

const cancelLabel = __( 'Cancel', 'event-tickets' );

class RSVPActionDashboard extends PureComponent {
	static propTypes = {
		clientId: PropTypes.string.isRequired,
		created: PropTypes.bool.isRequired,
		hasRecurrenceRules: PropTypes.bool.isRequired,
		isCancelDisabled: PropTypes.bool.isRequired,
		isConfirmDisabled: PropTypes.bool.isRequired,
		isLoading: PropTypes.bool.isRequired,
		onCancelClick: PropTypes.func.isRequired,
		onConfirmClick: PropTypes.func.isRequired,
		showCancel: PropTypes.bool.isRequired,
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
		const actions = [];
		if ( this.props.created ) {
			actions.push( <MoveDelete clientId={ this.props.clientId } /> );
		}

		return actions;
	}

	render() {
		const {
			created,
			hasRecurrenceRules,
			isConfirmDisabled,
			onCancelClick,
			onConfirmClick,
		} = this.props;

		/* eslint-disable max-len */
		return (
			<Fragment>
				<ActionDashboard
					className="tribe-editor__rsvp__action-dashboard tribe-common"
					actions={ this.getActions() }
					cancelLabel={ cancelLabel }
					confirmLabel={ confirmLabel( created ) }
					isConfirmDisabled={ isConfirmDisabled }
					onCancelClick={ onCancelClick }
					onConfirmClick={ onConfirmClick }
					showCancel={ true }
				/>
				{ hasRecurrenceRules && (
					<div className="tribe-editor__rsvp__warning">
						{ __( 'This is a recurring event. If you add tickets they will only show up on the next upcoming event in the recurrence pattern. The same ticket form will appear across all events in the series. Please configure your events accordingly.', 'event-tickets' ) }
					</div>
				) }
			</Fragment>
		);
		/* eslint-enable max-len */
	}
}

export default RSVPActionDashboard;
