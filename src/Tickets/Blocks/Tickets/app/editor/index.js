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
import { Tickets as TicketsIcon } from '@moderntribe/tickets/icons';
import {
	KEY_TICKET_HEADER,
	KEY_TICKET_CAPACITY,
	KEY_TICKET_DEFAULT_PROVIDER,
	KEY_TICKETS_LIST,
} from '@moderntribe/tickets/data/utils';
import Tickets from './container';

const block = {
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
			type: 'string',
			source: 'meta',
			meta: KEY_TICKETS_LIST,
		},
	},

	edit(editProps) {
		const blockProps = useBlockProps();
		return (
			<div {...blockProps}>
				<Tickets {...editProps} />
			</div>
		);
	},
	save() {
		const blockProps = useBlockProps.save();

		return (
			<div {...blockProps}>
				<InnerBlocks.Content />
			</div>
		);
	},
};

registerBlockType(`tribe/tickets`, block);
