/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Card } from '@moderntribe/tickets/elements';

const InactiveTickets = ( { title } ) => {
	return (
		<Card
			className="tribe-editor__card-no-bottom-border"
			header={ __( 'Tickets', 'event-tickets' ) }
		>
			<div className="tickets-description tickets-row-line">{ title }</div>
		</Card>
	);
};

InactiveTickets.propTypes = {
	title: PropTypes.string,
};

export default InactiveTickets;
