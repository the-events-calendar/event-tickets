/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPCounters from '@moderntribe/tickets/blocks/rsvp/counters/container';
import { NumericLabel, SplitContainer } from '@moderntribe/tickets/elements';
import {
	SettingsActionButton,
	AttendeesActionButton,
} from '@moderntribe/tickets/blocks/rsvp/action-buttons';

import './style.pcss';

const getCapacityLabel = ( capacity ) => {
	// todo: should use _n to be translator friendly
	const singular = __( '%d Remaining', 'event-tickets' );
	const plural = singular;
	const fallback = (
		<span className="tribe-editor__rsvp-container-header__capacity-label-fallback">
			{ __( 'Unlimited', 'event-tickets' ) }
		</span>
	);

	return (
		<NumericLabel
			className="tribe-editor__rsvp-container-header__capacity-label"
			count={ capacity }
			includeZero={ true }
			singular={ singular }
			plural={ plural }
			fallback={ fallback }
		/>
	);
};

const RSVPContainerHeader = ( {
	description,
	isAddEditOpen,
	isCreated,
	title,
	available,
	setAddEditOpen,
} ) => {
	if ( isAddEditOpen ) {
		return null;
	}

	/* eslint-disable max-len */
	const leftColumn = (
		<>
			<h3 className="tribe-editor__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">
				{ title }
			</h3>

			<div className="tribe-editor__rsvp-description tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
				{ description }
				<RSVPCounters />
			</div>

			{ isCreated && getCapacityLabel( available ) }
		</>
	);

	const rightColumn = (
		<>
			<button id="edit-rsvp" className="tribe-common-c-btn tribe-common-b1 tribe-common-b2--min-medium" onClick={ setAddEditOpen }>
				{ __( 'Edit RSVP', 'event-tickets' )}
			</button>
			<SettingsActionButton />
			<AttendeesActionButton />
		</>
	);

	return (
		<>
			<div className="tribe-common tribe-editor__inactive-block--rsvp tribe-editor__rsvp-container-header">
				<SplitContainer
					leftColumn={ leftColumn }
					rightColumn={ rightColumn }
				/>
			</div>
		</>
	);
	/* eslint-enable max-len */
};

RSVPContainerHeader.propTypes = {
	available: PropTypes.number,
	description: PropTypes.string,
	isAddEditOpen: PropTypes.bool,
	isCreated: PropTypes.bool,
	setAddEditOpen: PropTypes.func,
	title: PropTypes.string,
};

export default RSVPContainerHeader;
