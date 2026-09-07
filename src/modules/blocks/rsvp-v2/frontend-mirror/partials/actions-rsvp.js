/**
 * Mirrors `src/views/v2/commerce/rsvp/actions/rsvp.php`.
 */
import React from 'react';
import PropTypes from 'prop-types';
import RSVPActionsGoing from './actions-going';
import RSVPActionsNotGoing from './actions-not-going';

const RSVPActionsRsvp = ( { showNotGoing } ) => (
	<div className="tribe-tickets__rsvp-actions-rsvp tribe-editor__rsvp-frontend-mirror__actions">
		<RSVPActionsGoing />
		{ showNotGoing && <RSVPActionsNotGoing /> }
	</div>
);

RSVPActionsRsvp.propTypes = {
	showNotGoing: PropTypes.bool,
};

export default RSVPActionsRsvp;
