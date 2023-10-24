/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

const { InnerBlocks, useBlockProps } = wp.blockEditor;

/**
 * Internal dependencies
 */
import { Tickets as TicketsIcon } from '@moderntribe/tickets/icons';
import Ticket from './container';

const block = {
	title: __ ( 'Event Ticket', 'event-tickets' ),
	description: __ ( 'A single configured ticket type.', 'event-tickets' ),
	icon: <TicketsIcon/>,
	category: 'tribe-tickets',
	keywords: [ 'event', 'event-tickets', 'tribe' ],

	parent: [ 'tribe/tickets' ],

	supports: {
		html: false,
		customClassName: false,
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

	edit: function ( editProps ) {
		return ( <div { ...useBlockProps () }><Ticket { ...editProps }/></div> )
	},
	save: () => <div { ...useBlockProps () }><InnerBlocks.Content/></div>,
};

registerBlockType ( `tribe/ticket-item`, block );
