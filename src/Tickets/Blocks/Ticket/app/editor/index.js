/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

const { InnerBlocks, useBlockProps } = wp.blockEditor;

/**
 * Internal dependencies
 */
import { Tickets as TicketsIcon } from '../../../../../modules/icons';
import Ticket from './container';

function Edit( editProps ) {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Ticket { ...editProps } />
		</div>
	);
}

const block = {
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

	edit: Edit,
	save() {
		const blockProps = useBlockProps.save();
		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
			</div>
		);
	},
};

registerBlockType( `tribe/tickets-item`, block );
