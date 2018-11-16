/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SHARED, INDEPENDENT, UNLIMITED, TICKET_TYPES } from './constants'

export const CAPACITY_TYPE_OPTIONS = [
	{
		label: __( 'Share capacity with other tickets', 'events-gutenberg' ),
		value: TICKET_TYPES[ SHARED ],
	}, {
		label: __( 'Set capacity for this ticket only', 'events-gutenberg' ),
		value: TICKET_TYPES[ INDEPENDENT ],
	}, {
		label: __( 'Unlimited', 'events-gutenberg' ),
		value: TICKET_TYPES[ UNLIMITED ],
	},
];
