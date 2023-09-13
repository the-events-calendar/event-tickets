/**
 * External dependencies
 */
import React from 'react';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';


/**
 * Internal dependencies
 */
import { Card } from '@moderntribe/tickets/elements';

const InactiveTickets = () => {
	return (
		<Card className="tribe-editor__card-no-bottom-border">
			<div className="tickets-heading tickets-row-line">{ __( 'Tickets', 'event-tickets' ) }</div>
			<div className="tickets-description tickets-row-line">{ __( 'Add a ticket to get started.', 'event-tickets' ) }</div>
		</Card>

	);
}

export default InactiveTickets;
