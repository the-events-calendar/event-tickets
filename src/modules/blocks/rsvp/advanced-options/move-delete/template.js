/**
 * External Dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import './style.pcss';

const MoveDelete = ( {
	moveRSVP,
	removeRSVP,
} ) => {
	return (
		<div className="tribe-editor__rsvp__content-row--move-delete">
			<button type="button" onClick={ moveRSVP }>
				{ __( 'Move RSVP', 'events-tickets' ) }
			</button>
			<button type="button" onClick={ removeRSVP }>
				{ __( 'Remove RSVP', 'events-tickets' ) }
			</button>
		</div>
	);
};

MoveDelete.propTypes = {
	moveRSVP: PropTypes.func.isRequired,
	removeRSVP: PropTypes.func.isRequired,
};

export default MoveDelete;
