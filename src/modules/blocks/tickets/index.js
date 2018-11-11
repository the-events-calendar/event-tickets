/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { InnerBlocks } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { Tickets as TicketsIcon } from '@moderntribe/tickets/icons';
import {
	KEY_TICKET_HEADER,
	KEY_TICKET_CAPACITY,
	KEY_TICKET_DEFAULT_PROVIDER,
} from '@moderntribe/tickets/data/utils';
import Tickets from './container';

/**
 * Module Code
 */
export default {
	id: 'tickets',
	title: __( 'Tickets', 'events-gutenberg' ),
	description: __( 'Sell tickets and register attendees.', 'events-gutenberg' ),
	icon: <TicketsIcon/>,
	category: 'tribe-tickets',
	keywords: [ 'event', 'events-gutenberg', 'tribe' ],

	supports: {
		html: false,
	},

	attributes: {
		sharedCapacity: {
			type: 'string',
			source: 'meta',
			meta: KEY_TICKET_CAPACITY,
		},
		header: {
			type: 'string',
			source: 'meta',
			meta: KEY_TICKET_HEADER,
		},
		provider: {
			type: 'string',
			source: 'meta',
			meta: KEY_TICKET_DEFAULT_PROVIDER,
		},
	},

	edit: Tickets,
	save: () => (
		<div>
			<InnerBlocks.Content />
		</div>
	),
};
