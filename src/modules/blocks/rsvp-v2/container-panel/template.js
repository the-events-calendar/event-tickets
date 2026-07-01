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
import RSVPSavedSummary from '../saved-summary/container';
import RSVPAttendeeInformationSection from '../attendee-information-section/container';
import RSVPRemoveRsvp from '../remove-rsvp/container';
import { isSavedSummary } from '../utils/block-state';
import '../../rsvp/container/style.pcss';

const RSVPContainer = ( { clientId, created, isAddEditOpen, isDisabled, isSelected } ) => {
	if ( isSavedSummary( { created, isAddEditOpen } ) ) {
		return (
			<div
				className={ classNames( 'tribe-editor__rsvp-container', 'tribe-editor__rsvp-container--saved-summary', {
					'tribe-editor__rsvp-container--disabled': isDisabled,
				} ) }
			>
				<RSVPSavedSummary isSelected={ isSelected } />
				<RSVPAttendeeInformationSection clientId={ clientId } isSelected={ isSelected } />
				<RSVPRemoveRsvp clientId={ clientId } created={ created } isSelected={ isSelected } />
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
