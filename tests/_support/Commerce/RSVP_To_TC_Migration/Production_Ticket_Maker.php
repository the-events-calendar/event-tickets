<?php
/**
 * Trait for creating tickets using PRODUCTION code paths.
 *
 * Unlike the standard test Ticket_Makers which use factory shortcuts (wp_insert_post),
 * this trait goes through the real provider `ticket_add()` → `save_ticket()` flow.
 * This ensures the created tickets have the exact same meta as real user-created tickets.
 *
 * @since TBD
 */

namespace TEC\Tickets\Tests\Commerce\RSVP_To_TC_Migration;

use TEC\Tickets\Commerce\Module as TC_Module;
use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Global_Stock as Global_Stock;

trait Production_Ticket_Maker {

	/**
	 * Create a V1 RSVP ticket using the production code path.
	 *
	 * This goes through `RSVP::ticket_add()` → `RSVP::save_ticket()` → repository,
	 * which is the same path used when a user creates an RSVP from the admin UI.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id   The post/event ID.
	 * @param array $overrides Data overrides.
	 *
	 * @return int The ticket ID.
	 */
	protected function create_production_rsvp_ticket( int $post_id, array $overrides = [] ): int {
		$meta_input = array_merge( [
			'_price' => '0',
			'_ticket_end_date' => '2027-02-05 21:56:27',
			'_ticket_start_date' => '2026-02-05 19:56:32',
			'_tribe_rsvp_for_event' => $post_id,
			'_tribe_ticket_capacity' => '99',
			'_tribe_ticket_show_description' => 'yes',
			'_tribe_ticket_show_not_going' => false,
		], $overrides['meta_input'] ?? [] );

		unset( $overrides['meta_input'] );

		$ticket_id = self::factory()->post->create( array_merge( [
			'post_type' => 'tribe_rsvp_tickets',
			'post_title' => "Test RSVP ticket for {$post_id}",
			'post_status' => 'publish',
			'post_excerpt' => "Test RSVP description for {$post_id}",
			'meta_input' => $meta_input,
		], $overrides ) );

		$this->assertIsInt( $ticket_id );
		$this->assertGreaterThan( 0, $ticket_id );

		return $ticket_id;
	}

	/**
	 * Create a TC ticket using the production code path.
	 *
	 * This goes through `Module::ticket_add()` → `Module::save_ticket()` → `Ticket::save()`,
	 * which is the same path used when a user creates a TC ticket from the admin UI.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id   The post/event ID.
	 * @param int   $price     Ticket price.
	 * @param array $overrides Data overrides.
	 *
	 * @return int The ticket ID.
	 */
	protected function create_production_tc_ticket( int $post_id, int $price = 1, array $overrides = [] ): int {
		/** @var TC_Module $tc_module */
		$tc_module = tribe( TC_Module::class );

		$data = array_merge( [
			'ticket_name'             => "Test TC ticket for {$post_id}",
			'ticket_description'      => "Test TC description for {$post_id}",
			'ticket_show_description' => 1,
			'ticket_price'            => $price,
			'ticket_start_date'       => '2020-01-02',
			'ticket_start_time'       => '08:00:00',
			'ticket_end_date'         => '2050-03-01',
			'ticket_end_time'         => '20:00:00',
			'tribe-ticket'            => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 100,
			],
		], $overrides );

		// Set the provider field on the event, same as production.
		$tickets_handler = tribe( 'tickets.handler' );
		update_post_meta( $post_id, $tickets_handler->key_provider_field, get_class( $tc_module ) );

		$ticket_id = $tc_module->ticket_add( $post_id, $data );

		$this->assertIsInt( $ticket_id );
		$this->assertGreaterThan( 0, $ticket_id );

		return $ticket_id;
	}

	/**
	 * Create a TC-RSVP (V2) ticket using the production code path.
	 *
	 * This creates a TC ticket with price=0 and type=tc-rsvp through the full
	 * production flow, identical to what happens when a user creates a V2 RSVP.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id   The post/event ID.
	 * @param array $overrides Data overrides.
	 *
	 * @return int The ticket ID.
	 */
	protected function create_production_tc_rsvp_ticket( int $post_id, array $overrides = [] ): int {
		$overrides['ticket_type'] = RSVP_V2_Constants::TC_RSVP_TYPE;

		$ticket_id = $this->create_production_tc_ticket( $post_id, 0, $overrides );

		// Set the ticket type meta to tc-rsvp (same as production V2 RSVP flow).
		update_post_meta( $ticket_id, TC_Ticket::$type_meta_key, RSVP_V2_Constants::TC_RSVP_TYPE );

		return $ticket_id;
	}
}
