/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
const { InnerBlocks, useBlockProps } = wp.blockEditor;

/**
 * Internal dependencies
 */
import { Tickets as TicketsIcon } from '@moderntribe/tickets/icons';
import {
	KEY_TICKET_HEADER,
	KEY_TICKET_CAPACITY,
	KEY_TICKET_DEFAULT_PROVIDER,
	KEY_TICKETS_LIST,
} from '@moderntribe/tickets/data/utils';
import Tickets from './container';

/**
 * Module Code
 */
export default {
	id: 'tickets',
	icon: <TicketsIcon />,

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
		tickets: {
			type: 'array',
			source: 'meta',
			meta: KEY_TICKETS_LIST,
		},
	},

	edit: Tickets,
	save: () => (
		<div><InnerBlocks.Content /></div>
	),
};
