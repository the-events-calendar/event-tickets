/**
 * Sidebar control stubs for SOFT-3333.
 *
 * Register controls via the `tec.tickets.blocks.RSVP.Controls` filter.
 *
 * Expected controls (SOFT-3333):
 *
 * 1. Enable "Can't go" responses
 *    - Redux: `notGoingResponses` via `setRSVPDetails` / `updateRSVP`
 *    - REST: `show_not_going`
 *
 * 2. Show attendees list on Event Page (TEC active only)
 *    - REST: `rsvp_show_attendees`
 *    - Meta: `_tec_show_attendees_list_rsvp`
 *
 * 3. Enable waitlist before RSVPs open
 *    - REST: `rsvp_waitlist_before_sale`
 *
 * 4. Enable waitlist if RSVPs reach limit
 *    - REST: `rsvp_waitlist_sold_out`
 *
 * Reference implementations:
 * - `src/modules/blocks/attendees/template.js` (ToggleControl in InspectorControls)
 * - `event-tickets-plus/src/Tickets_Plus/Waitlist/app/blockEditor/filters.js`
 *
 * @module rsvp-v2/sidebar-controls/stub
 */

/**
 * Filter name for RSVP block sidebar controls.
 *
 * @type {string}
 */
export const RSVP_CONTROLS_FILTER = 'tec.tickets.blocks.RSVP.Controls';

/**
 * Documented control definitions implementers.
 *
 * @type {Array<Object>}
 */
export const EXPECTED_SIDEBAR_CONTROLS = [
	{
		label: 'Enable "Can\'t go" responses',
		key: 'notGoingResponses',
	},
	{
		label: 'Show attendees list on Event Page',
		key: 'rsvp_show_attendees',
		requiresTec: true,
	},
	{
		label: 'Enable waitlist before RSVPs open',
		key: 'rsvp_waitlist_before_sale',
	},
	{
		label: 'Enable waitlist if RSVPs reach limit',
		key: 'rsvp_waitlist_sold_out',
	},
];
