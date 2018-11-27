/**
 * External Dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import './style.pcss';

const MoveDelete = ( {
	moveTicket,
	removeTicket,
} ) => {
	return (
		<div className="tribe-editor__ticket__content-row--move-delete">
			<button type="button" onClick={ moveTicket }>
				{ __( 'Move Ticket', 'events-gutenberg' ) }
			</button>
			<button type="button" onClick={ removeTicket }>
				{ __( 'Remove Ticket', 'events-gutenberg' ) }
			</button>
		</div>
	);
};

MoveDelete.propTypes = {
	moveTicket: PropTypes.func.isRequired,
	removeTicket: PropTypes.func.isRequired,
};

export default MoveDelete;
