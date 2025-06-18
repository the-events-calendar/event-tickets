<?php
/**
 * Link to Tickets included on the Events Single Page after the meta.
 * The message that will link to the Tickets page.
 *
 * Override this template in your own theme by creating a file at:
 *
 * [your-theme]/tribe/tickets/view-link.php
 *
 * If you are using Event Tickets Plus and V2 templates (new form experience) then create a file at:
 *
 * [your-theme]/tribe/tickets/tickets/view-link.php
 *
 * @since 4.2
 * @since 4.10.8 Renamed template from order-links.php to view-link.php. Updated to not use the now-deprecated third
 *                 parameter of `get_description_rsvp_ticket()`.
 * @since 4.10.9  Use customizable ticket name functions.
 * @since 4.11.0 Made template more like new blocks-based template in terms of logic.
 * @since 4.12.1 Account for empty post type object, such as if post type got disabled. Fix typo in sprintf placeholders.
 * @since 5.0.1 Add additional checks to prevent PHP errors when called from automated testing.
 * @since 5.0.2 Fix template path in documentation block.
 * @since 5.1.3 Use /tribe-events/ for the template path in documentation block.
 * @since 5.3.2 Added use of $hide_view_my_tickets_link variable to hide link as an option.
 * @since 5.8.0 Re-use the same template from the blocks.
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 *
 * @var Tribe__Tickets__Tickets_View $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
/** @var Tribe__Tickets__Editor__Template $template */
$template = tribe( 'tickets.editor.template' );
$template->template( 'blocks/attendees/view-link' );
