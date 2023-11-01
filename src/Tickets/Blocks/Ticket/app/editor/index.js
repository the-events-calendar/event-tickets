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
	icon: <TicketsIcon/>,

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
		const blockProps = useBlockProps();
		return ( <div { ...blockProps }><Ticket { ...editProps }/></div> )
	},
	save: function () {
		const blockProps = useBlockProps.save ()
		return <div { ...blockProps }><InnerBlocks.Content/></div>;
	}
};

registerBlockType ( `tribe/tickets-item`, block );
