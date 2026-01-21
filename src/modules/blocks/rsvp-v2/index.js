/**
 * V2 RSVP Block Definition
 *
 * This block extends the V1 RSVP block but uses V2 API endpoints
 * for creating, updating, and fetching RSVPs.
 */

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
import RSVPV2Container from './container';
import { KEY_TICKET_GOING_COUNT, KEY_TICKET_NOT_GOING_COUNT, KEY_TICKET_HEADER } from '../../data/utils';

/**
 * V2 RSVP Block definition.
 *
 * Uses the same block ID ('rsvp') as V1 so it can replace V1 when V2 is active.
 */
export default {
	id: 'rsvp',
	title: __( 'RSVP', 'event-tickets' ),
	description: __( 'Find out who is planning to attend!', 'event-tickets' ),
	icon: <RSVPIcon />,
	category: 'tribe-tickets',
	keywords: [ 'event', 'events-gutenberg', 'tribe' ],

	supports: {
		html: false,
		multiple: false,
		customClassName: false,
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

	edit: RSVPV2Container,

	save: () => null,
};
