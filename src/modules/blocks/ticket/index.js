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
import Ticket from './container';

export default {
	id: 'tickets-item',
	icon: <TicketsIcon />,

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

	edit: function (){
		const blockProps = useBlockProps();

		return ( <div { ...blockProps }><Ticket/></div> );
	},
	save: () => <div><InnerBlocks.Content /></div>,
};
