/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { Tickets as TicketsIcon } from '@moderntribe/tickets/icons';
import Ticket from './container';

export default {
	id: 'tickets-item',
	title: __( 'Event Ticket', 'events-gutenberg' ),
	description: __( 'A single configured ticket type.', 'events-gutenberg' ),
	icon: <TicketsIcon/>,
	category: 'tribe-tickets',
	keywords: [ 'event', 'events-tickets', 'tribe' ],

	parent: [ 'tribe/tickets' ],

	supports: {
		html: false,
	},

	attributes: {
		hasBeenCreated: {
			type: 'boolean',
			default: false,
		},
		ticketId: {
			type: 'integer',
			default: 0,
		},
	},

	edit: Ticket,
	save: () => <div><InnerBlocks.Content /></div>,
};
