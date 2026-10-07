<?php
/**
 * The scripts of Recurring Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use Tribe__Events__Main as TEC;

/**
 * Registers the editor scripts, loaded only when editing an event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Assets {
	/**
	 * Registers the scripts.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		$plugin = tribe( 'tickets.main' );

		tec_asset(
			$plugin,
			'tec-tickets-recurring-tickets-classic-editor',
			$plugin->plugin_url . 'build/RecurringTickets/classic-editor.js',
			// After the tickets panel script, whose recurrence events it follows.
			[ 'jquery', 'event-tickets-admin-js' ],
			'admin_enqueue_scripts',
			[
				'in_footer'    => true,
				'conditionals' => [ $this, 'is_editing_an_event_in_the_classic_editor' ],
			]
		);
	}

	/**
	 * Whether the current screen edits an event in the classic editor.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the classic editor is editing an event.
	 */
	public function is_editing_an_event_in_the_classic_editor(): bool {
		return tribe_context()->is_editing_post( TEC::POSTTYPE ) && ! tribe( 'editor' )->should_load_blocks();
	}
}
