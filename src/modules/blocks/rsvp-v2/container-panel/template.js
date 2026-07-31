/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { ContainerPanel } from '../../../elements';
import { LAYOUT } from '../../../elements/container-panel';
import RSVPContainerContent from '../container-content/container';
// TODO: Restore RSVPSavedSummary import when ../saved-summary/container lands in PR #4298.
// TODO: Restore RSVPAttendeeInformationSection import when ../attendee-information-section/container lands in PR #4298.
// TODO: Restore RSVPRemoveRsvp import when ../remove-rsvp/container lands in PR #4299.
// TODO: Restore isSavedSummary() import when ../utils/block-state lands in PR #4301.
import '../../rsvp/container/style.pcss';

const RSVPContainer = ( { clientId, isDisabled } ) => {
	// TODO: Restore the saved-summary guard with isSavedSummary() when ../utils/block-state lands in PR #4301.
	if ( false ) {
		return (
			<div
				className={ classNames( 'tribe-editor__rsvp-container', 'tribe-editor__rsvp-container--saved-summary', {
					'tribe-editor__rsvp-container--disabled': isDisabled,
				} ) }
			>
				{ /* TODO: Restore <RSVPSavedSummary /> when ../saved-summary/container lands in PR #4298. */ }
				{ /* TODO: Restore <RSVPAttendeeInformationSection /> when ../attendee-information-section/container lands in PR #4298. */ }
				{ /* TODO: Restore <RSVPRemoveRsvp /> when ../remove-rsvp/container lands in PR #4299. */ }
			</div>
		);
	}

	return (
		<ContainerPanel
			className={ classNames( 'tribe-editor__rsvp-container', {
				'tribe-editor__rsvp-container--disabled': isDisabled,
			} ) }
			content={ <RSVPContainerContent clientId={ clientId } /> }
			header={ null }
			layout={ LAYOUT.rsvp }
		/>
	);
};

RSVPContainer.propTypes = {
	clientId: PropTypes.string.isRequired,
	created: PropTypes.bool.isRequired,
	isAddEditOpen: PropTypes.bool.isRequired,
	isDisabled: PropTypes.bool.isRequired,
	isSelected: PropTypes.bool.isRequired,
};

export default RSVPContainer;
