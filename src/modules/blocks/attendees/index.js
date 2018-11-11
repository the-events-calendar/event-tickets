/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Attendees from './container';
import { Attendees as AttendeesIcon } from '@moderntribe/tickets/icons';

/**
 * Module Code
 */
export default {
	id: 'attendees',
	title: __( 'Attendee List', 'events-gutenberg' ),
	description: __(
		'Show the gravatars of people coming to this event.',
		'events-gutenberg'
	),
	icon: <AttendeesIcon/>,
	category: 'tribe-tickets',
	keywords: [ 'event', 'events-gutenberg', 'tribe' ],

	supports: {
		html: false,
	},

	attributes: {
		title: {
			type: 'html',
			default: __( 'Who\'s Attending?', 'events-gutenberg' ),
		},
	},

	edit: Attendees,

	save: () => null,
};
