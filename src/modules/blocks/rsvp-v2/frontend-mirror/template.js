/**
 * Block editor mirror of the commerce RSVP frontend templates.
 *
 * Editor-only affordances live in `style.pcss`; shared frontend styles load
 * via `tec-tickets-commerce-rsvp-style` (PHP-enqueued).
 */
import './style.pcss';
import PropTypes from 'prop-types';
import React from 'react';
import RSVPActions from './partials/actions';
import RSVPDetails from './partials/details';

const RSVPFrontendMirror = ( {
	available,
	goingCount,
	notGoingCount,
	onEditRemaining,
	remainingRef,
	showEditAffordances,
	showNotGoing,
	title,
} ) => (
	<div className="tribe-common event-tickets tribe-editor__rsvp-frontend-mirror">
		<div className="tribe-tickets__rsvp-wrapper">
			<div className="tribe-tickets__rsvp tribe-common-g-row tribe-common-g-row--gutters">
				<RSVPDetails
					available={ available }
					detailsRef={ remainingRef }
					goingCount={ goingCount }
					notGoingCount={ notGoingCount }
					onEditRemaining={ onEditRemaining }
					showEditAffordances={ showEditAffordances }
					showNotGoing={ showNotGoing }
					title={ title }
				/>
				<RSVPActions showNotGoing={ showNotGoing } />
			</div>
		</div>
	</div>
);

RSVPFrontendMirror.propTypes = {
	available: PropTypes.number,
	goingCount: PropTypes.number,
	notGoingCount: PropTypes.number,
	onEditRemaining: PropTypes.func,
	remainingRef: PropTypes.shape( { current: PropTypes.instanceOf( Element ) } ),
	showEditAffordances: PropTypes.bool,
	showNotGoing: PropTypes.bool,
	title: PropTypes.string,
};

export default RSVPFrontendMirror;
