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
import { Card, SplitContainer } from '../../../elements';

const RSVPInactiveBlock = ( { created, setAddEditOpen } ) => {
	const title = created
		? __( 'RSVP is not currently active', 'event-tickets' )
		: __( 'Add an RSVP', 'event-tickets' );

	const description = created
		? __( 'Edit this block to change RSVP settings.', 'event-tickets' )
		: __( 'Allow users to confirm their attendance.', 'event-tickets' );

	/* eslint-disable max-len */
	const leftColumn = (
		<>
			<h3 className="tribe-editor__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">{ title }</h3>

			<div className="tribe-editor__rsvp-description tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
				{ description }
			</div>
		</>
	);

	const rightColumn = (
		<>
			<button
				id="add-rsvp"
				className="tribe-common-c-btn tribe-common-b1 tribe-common-b2--min-medium"
				onClick={ setAddEditOpen }
			>
				{ __( 'Add RSVP', 'event-tickets' ) }
			</button>
		</>
	);
	/* eslint-enable max-len */

	return (
		<Card className="tribe-common tribe-editor__inactive-block--rsvp">
			<SplitContainer leftColumn={ leftColumn } rightColumn={ rightColumn } />
		</Card>
	);
};

RSVPInactiveBlock.propTypes = {
	created: PropTypes.bool.isRequired,
	setAddEditOpen: PropTypes.func.isRequired,
};

export default RSVPInactiveBlock;
