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
import './style.pcss';

const RSVPRsvpWindow = ( { anchorRef, dateRange, isWindowOpen, onEditWindow, showEditAffordances } ) => {
	if ( ! dateRange ) {
		return null;
	}

	const title = __( 'RSVP Window', 'event-tickets' );

	return (
		<div className="tribe-editor__rsvp-window">
			{ showEditAffordances ? (
				<button
					ref={ anchorRef }
					className="tribe-editor__rsvp-window__title-edit"
					disabled={ isWindowOpen }
					onClick={ onEditWindow }
					type="button"
				>
					<span className="tribe-editor__rsvp-window__title tribe-common-h6 tribe-common-h--alt">
						{ title }
					</span>
					<Dashicon className="tribe-editor__rsvp-window__edit-icon" icon="edit" />
				</button>
			) : (
				<span className="tribe-editor__rsvp-window__title tribe-common-h6 tribe-common-h--alt">{ title }</span>
			) }
			<div className="tribe-editor__rsvp-window__dates tribe-common-b2">{ dateRange }</div>
		</div>
	);
};

RSVPRsvpWindow.propTypes = {
	anchorRef: PropTypes.shape( { current: PropTypes.instanceOf( Element ) } ),
	dateRange: PropTypes.string,
	isWindowOpen: PropTypes.bool,
	onEditWindow: PropTypes.func,
	showEditAffordances: PropTypes.bool,
};

export default RSVPRsvpWindow;
