/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { RSVP as RSVPIcon } from '../../icons';
import RSVPDisabledBlock from './template';
import { KEY_TICKET_GOING_COUNT, KEY_TICKET_NOT_GOING_COUNT, KEY_TICKET_HEADER } from '../../data/utils';

/**
 * Disabled RSVP Block definition.
 *
 * Uses the same block ID ('rsvp') so it replaces the active block
 * when RSVP is disabled during migration.
 *
 * @since TBD
 */
export default {
	id: 'rsvp',
	title: __( 'RSVP', 'event-tickets' ),
	description: __( 'RSVP is temporarily unavailable during migration.', 'event-tickets' ),
	icon: <RSVPIcon />,
	category: 'tribe-tickets',
	keywords: [],

	supports: {
		html: false,
		multiple: false,
		customClassName: false,
		inserter: false,
	},

	attributes: {
		goingCount: {
			type: 'integer',
			source: 'meta',
			meta: KEY_TICKET_GOING_COUNT,
		},
		notGoingCount: {
			type: 'integer',
			source: 'meta',
			meta: KEY_TICKET_NOT_GOING_COUNT,
		},
		headerImageId: {
			type: 'integer',
			source: 'meta',
			meta: KEY_TICKET_HEADER,
		},
	},

	edit: RSVPDisabledBlock,

	save: () => null,
};
