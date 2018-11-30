/**
 * External Dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@moderntribe/common/elements';
import './style.pcss';

const MoveDelete = ( {
	moveRSVP,
	removeRSVP,
} ) => {
	return (
		<div className="tribe-editor__rsvp__content-row--move-delete">
			<Button type="button" onClick={ moveRSVP }>
				{ __( 'Move RSVP', 'events-tickets' ) }
			</Button>
			<Button type="button" onClick={ removeRSVP }>
				{ __( 'Remove RSVP', 'events-tickets' ) }
			</Button>
		</div>
	);
};

MoveDelete.propTypes = {
	moveRSVP: PropTypes.func.isRequired,
	removeRSVP: PropTypes.func.isRequired,
};

export default MoveDelete;
