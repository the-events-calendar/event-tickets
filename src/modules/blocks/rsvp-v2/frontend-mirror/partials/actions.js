/**
 * Mirrors `src/views/v2/commerce/rsvp/actions.php`.
 */
import React from 'react';
import PropTypes from 'prop-types';
import RSVPActionsRsvp from './actions-rsvp';

const RSVPActions = ( { showNotGoing } ) => (
	<div className="tribe-tickets__rsvp-actions-wrapper tribe-common-g-col">
		<div className="tribe-tickets__rsvp-actions">
			<RSVPActionsRsvp showNotGoing={ showNotGoing } />
		</div>
	</div>
);

RSVPActions.propTypes = {
	showNotGoing: PropTypes.bool,
};

export default RSVPActions;
