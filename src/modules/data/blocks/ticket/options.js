/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SHARED, INDEPENDENT, UNLIMITED, TICKET_TYPES } from './constants';

export const CAPACITY_TYPE_OPTIONS = [
	{
		label: __( 'Share capacity with other tickets', 'event-tickets' ),
		value: TICKET_TYPES[ SHARED ],
	},
	{
		label: __( 'Set capacity for this ticket only', 'event-tickets' ),
		value: TICKET_TYPES[ INDEPENDENT ],
	},
	{
		label: __( 'Unlimited', 'event-tickets' ),
		value: TICKET_TYPES[ UNLIMITED ],
	},
];
