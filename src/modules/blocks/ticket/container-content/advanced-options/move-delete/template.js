/**
 * External Dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@moderntribe/common/elements';
import './style.pcss';

const MoveDelete = ( {
	moveTicket,
	removeTicket,
} ) => {
	return (
		<div className="tribe-editor__ticket__content-row--move-delete">
			<Button type="button" onClick={ moveTicket }>
				{ __( 'Move Ticket', 'events-tickets' ) }
			</Button>
			<Button type="button" onClick={ removeTicket }>
				{ __( 'Remove Ticket', 'events-tickets' ) }
			</Button>
		</div>
	);
};

MoveDelete.propTypes = {
	moveTicket: PropTypes.func.isRequired,
	removeTicket: PropTypes.func.isRequired,
};

export default MoveDelete;
