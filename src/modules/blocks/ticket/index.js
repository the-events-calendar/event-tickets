/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { BlockIcon } from '@moderntribe/common/elements';
import Ticket from './container';

export default {
	id: 'tickets-item',
	title: __( 'Event Ticket', 'events-gutenberg' ),
	description: __( 'A single configured ticket type.', 'events-gutenberg' ),
	icon: BlockIcon,
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
		dateIsPristine: {
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
