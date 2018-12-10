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
} from '@moderntribe/tickets/blocks/rsvp/action-buttons';
import { ActionDashboard, LabelWithTooltip } from '@moderntribe/tickets/elements';
import './style.pcss';

const confirmLabel = ( created ) => (
	created
		? __( 'Update RSVP', 'event-tickets' )
		: __( 'Create RSVP', 'event-tickets' )
);

const cancelLabel = __( 'Cancel', 'event-tickets' );

const RSVPWarningTooltipLabel = () => (
	<Dashicon
		className="tribe-editor__rsvp__warning-tooltip-label"
		icon="info-outline"
	/>
);

const RSVPWarning = ( { isDisabled } ) => (
	<LabelWithTooltip
		className="tribe-editor__rsvp__warning"
		label={ __( 'Warning', 'event-tickets' ) }
		tooltipDisabled={ isDisabled }
		tooltipLabel={ <RSVPWarningTooltipLabel /> }
		tooltipText={ __( 'This is a recurring event. If you add tickets they will only show up on the next upcoming event in the recurrence pattern. The same ticket form will appear across all events in the series. Please configure your events accordingly.', 'event-tickets' ) }
	/>
);

RSVPWarning.propTypes = {
	isDisabled: PropTypes.bool.isRequired,
};

const RSVPActionDashboard = ( {
	created,
	hasTicketsPlus,
	hasRecurrenceRules,
	isCancelDisabled,
	isConfirmDisabled,
	isLoading,
	onCancelClick,
	onConfirmClick,
	showCancel,
} ) => {
	const getActions = () => {
		const actions = [ <SettingsActionButton /> ];
		if ( hasTicketsPlus ) {
			actions.push( <AttendeesActionButton /> );
		}
		if ( hasRecurrenceRules ) {
			actions.push( <RSVPWarning isDisabled={ isLoading } /> );
		}
		return actions;
	}

	return (
		<ActionDashboard
			className="tribe-editor__rsvp__action-dashboard"
			actions={ getActions() }
			cancelLabel={ cancelLabel }
			confirmLabel={ confirmLabel( created ) }
			isCancelDisabled={ isCancelDisabled }
			isConfirmDisabled={ isConfirmDisabled }
			onCancelClick={ onCancelClick }
			onConfirmClick={ onConfirmClick }
			showCancel={ showCancel }
		/>
	);
};

RSVPActionDashboard.propTypes = {
	created: PropTypes.bool.isRequired,
	hasTicketsPlus: PropTypes.bool.isRequired,
	hasRecurrenceRules: PropTypes.bool.isRequired,
	isCancelDisabled: PropTypes.bool.isRequired,
	isConfirmDisabled: PropTypes.bool.isRequired,
	isLoading: PropTypes.bool.isRequired,
	onCancelClick: PropTypes.func.isRequired,
	onConfirmClick: PropTypes.func.isRequired,
	showCancel: PropTypes.bool.isRequired,
};

export default RSVPActionDashboard;
