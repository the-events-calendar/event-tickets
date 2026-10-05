<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Attendee_Maker;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Tickets as Tickets;

class Stock_Capacity_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use Attendee_Maker;

	public function test_stock_and_capacity_with_no_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 50, $ticket->stock() );
		$this->assertEquals( 50, $ticket->inventory() );
		$this->assertEquals( 50, $ticket->available() );
		$this->assertEquals( 0, $ticket->qty_sold() );
	}

	public function test_stock_decreases_for_going_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		$this->create_order( [ $ticket_id => 3 ] );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 47, $ticket->stock() );
		$this->assertEquals( 47, $ticket->inventory() );
		$this->assertEquals( 47, $ticket->available() );
		$this->assertEquals( 3, $ticket->qty_sold() );
	}

	public function test_stock_and_inventory_with_not_going_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		$this->create_not_going_rsvp_order( $ticket_id, 2 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// Not Going attendees do not hold seats: Decrease_Stock bails, so _stock and qty_sold stay at their baseline.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 50, $ticket->stock() );
		$this->assertEquals( 0, $ticket->qty_sold() );
		$this->assertEquals( 50, $ticket->inventory() );
		$this->assertEquals( 50, $ticket->available() );
	}

	public function test_stock_and_inventory_with_mixed_going_and_not_going(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		// 3 going attendees: a normal Decrease_Stock decrement.
		$this->create_order( [ $ticket_id => 3 ] );

		// 2 not-going attendees: order is created but Decrease_Stock bails for these items.
		$this->create_not_going_rsvp_order( $ticket_id, 2 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// 3 going hold seats; 2 not-going do not, so every counter lands at 50 − 3 = 47.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 47, $ticket->stock() );
		$this->assertEquals( 3, $ticket->qty_sold() );
		$this->assertEquals( 47, $ticket->inventory() );
		$this->assertEquals( 47, $ticket->available() );
	}

	public function test_show_not_going_disabled_does_not_affect_stock_or_inventory(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '' );

		// 2 going + 1 not-going.
		$this->create_order( [ $ticket_id => 2 ] );
		$this->create_not_going_rsvp_order( $ticket_id, 1 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// show_not_going only controls the UI; counting is unaffected.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 48, $ticket->stock() );
		$this->assertEquals( 2, $ticket->qty_sold() );
		$this->assertEquals( 48, $ticket->inventory() );
		$this->assertEquals( 48, $ticket->available() );
	}

	public function test_show_not_going_enabled_does_not_affect_stock(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		// 2 going + 1 not-going.
		$this->create_order( [ $ticket_id => 2 ] );
		$this->create_not_going_rsvp_order( $ticket_id, 1 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 48, $ticket->stock() );
		$this->assertEquals( 2, $ticket->qty_sold() );
	}

	/**
	 * Regression: after the per-request attendee cache is warmed (e.g. the event page rendered
	 * the RSVP block once), creating attendees during the same request must invalidate it so
	 * inventory()/available() reflect the new attendees immediately. Previously the
	 * `tec_tickets_attendees_by_ticket_id` cache was stored without the save_post trigger, so the
	 * RSVP success view reported the pre-RSVP "remaining" count until the next page load.
	 *
	 * @see \TEC\Tickets\Commerce\Attendee::get_attendees_by_ticket_id()
	 */
	public function test_attendee_list_cache_refreshes_when_attendees_are_created(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$attendees = tribe( Attendee::class );
		$provider  = tribe( Module::class )->orm_provider;

		// Warm the per-request cache, as a page view of the RSVP block (which reads inventory) would.
		$this->assertCount( 0, $attendees->get_attendees_by_ticket_id( $ticket_id, $provider ) );

		// Three attendees come into existence during the same request. The save_post on each must
		// invalidate the cached attendee list; otherwise inventory()/available() keep reporting the
		// pre-RSVP "remaining" count until the next page load.
		$this->create_going_tc_rsvp_attendees( 3, $ticket_id, $post_id );

		$this->assertCount(
			3,
			$attendees->get_attendees_by_ticket_id( $ticket_id, $provider ),
			'The cached attendee list must refresh after attendees are created during the same request.'
		);
	}

	/**
	 * Regression: saving a TC-RSVP ticket must not re-count a Going attendee that was flipped to
	 * Not Going as sold. The flip (RSVP V2 frontend, `Frontend::update_attendee_data()`) only
	 * updates the `_tec_tickets_commerce_rsvp_status` meta and never decrements `total_sales`, so
	 * `Ticket::save()` would recompute `_stock` as `capacity - stale_sold` and silently reintroduce
	 * the Not Going over-count into `available()`/`get_ticket_max_purchase()`.
	 *
	 * @see \TEC\Tickets\Commerce\Ticket::save()
	 * @see \TEC\Tickets\RSVP\V2\Frontend::update_attendee_data()
	 */
	public function test_save_does_not_recount_flipped_not_going_attendees_as_sold(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		// Going order: Decrease_Stock runs, so total_sales = 1 and _stock = 49.
		$order     = $this->create_order( [ $ticket_id => 1 ] );
		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$this->assertCount( 1, $attendees );
		$attendee_id = $attendees[0]['attendee_id'];

		// Flip the attendee to Not Going, exactly what the RSVP V2 frontend status toggle does.
		update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'no' );

		// Save the ticket (e.g. a title change in the admin editor).
		$this->save_renamed_ticket( $post_id, $ticket_id, Constants::TC_RSVP_TYPE );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// The flipped attendee does not hold a seat, so every counter must land at 50, not 49.
		$this->assertEquals( 50, $ticket->available() );
		$this->assertEquals( 50, $ticket->inventory() );
		$this->assertEquals( 50, (int) get_post_meta( $ticket_id, '_stock', true ) );

		// `total_sales` is a separate legacy counter the flip does not decrement; this fix only
		// stops it from over-subtracting `_stock` on save.
		$this->assertEquals( 1, $ticket->qty_sold() );
	}

	/**
	 * Sanity: saving a TC-RSVP ticket with a directly-created Not Going attendee (which never
	 * touched `total_sales` because `Decrease_Stock` bails for it) must keep stock correct. This
	 * guards the fix against over-subtracting when `total_sales` already excludes the Not Going
	 * attendee.
	 *
	 * @see \TEC\Tickets\Commerce\Flag_Actions\Decrease_Stock::handle()
	 */
	public function test_save_keeps_stock_for_directly_created_not_going_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		// 1 going attendee (total_sales = 1, _stock = 49) + 1 directly-created Not Going attendee.
		$this->create_order( [ $ticket_id => 1 ] );
		$this->create_not_going_rsvp_order( $ticket_id, 1 );

		$this->save_renamed_ticket( $post_id, $ticket_id, Constants::TC_RSVP_TYPE );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// Only the 1 going attendee holds a seat: 50 − 1 = 49.
		$this->assertEquals( 49, $ticket->available() );
		$this->assertEquals( 49, (int) get_post_meta( $ticket_id, '_stock', true ) );
		$this->assertEquals( 1, $ticket->qty_sold() );
	}

	/**
	 * Sanity: saving a regular paid Tickets Commerce ticket must keep the pre-existing `sold`
	 * recompute untouched. The RSVP V2 Not Going exclusion only applies to TC-RSVP tickets.
	 */
	public function test_save_stock_unaffected_for_regular_tc_tickets(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		$this->create_order( [ $ticket_id => 2 ] );

		$this->save_renamed_ticket( $post_id, $ticket_id );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 48, $ticket->available() );
		$this->assertEquals( 48, (int) get_post_meta( $ticket_id, '_stock', true ) );
		$this->assertEquals( 2, $ticket->qty_sold() );
	}

	/**
	 * Saves a ticket through the real provider flow, as the admin editor does on any change.
	 *
	 * A no-op title change is enough to trigger the `'update'` branch of `Ticket::save()`. The
	 * `$ticket_type` is passed through as the production `ticket_add()` flow does: `Ticket::save()`
	 * rewrites the `_type` meta from `$raw_data['ticket_type'] ?? 'default'`, so omitting it would
	 * strip the ticket's type (and the RSVP V2 Not Going exclusion along with it). The
	 * `tec_tickets` wp_cache is not invalidated by the save (only `clean_post_cache()` runs), so
	 * it is cleared here for the follow-up `load_ticket_object()` to read the freshly persisted
	 * `_stock` rather than the pre-save cached value.
	 */
	private function save_renamed_ticket( int $post_id, int $ticket_id, string $ticket_type = 'default' ): void {
		$ticket       = Tickets::load_ticket_object( $ticket_id );
		$ticket->name = 'Renamed';

		tribe( Module::class )->save_ticket(
			$post_id,
			$ticket,
			[
				'ticket_type'  => $ticket_type,
				'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 50 ],
			]
		);

		wp_cache_delete( $ticket_id, 'tec_tickets' );
	}

	/**
	 * Mirrors the production Order_Endpoint flow for Not Going: places a cart item with
	 * type=tc-rsvp + order_status=no (so Decrease_Stock bails) and stamps the rsvp_status
	 * meta on the resulting attendees (so inventory() excludes them).
	 */
	private function create_not_going_rsvp_order( int $ticket_id, int $quantity ): void {
		$order = $this->create_order(
			[
				$ticket_id => [
					'quantity' => $quantity,
					'extras'   => [
						'type'         => Constants::TC_RSVP_TYPE,
						'order_status' => 'no',
					],
				],
			]
		);

		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		foreach ( $attendees as $attendee ) {
			update_post_meta( $attendee['attendee_id'], Constants::RSVP_STATUS_META_KEY, 'no' );
		}
	}
}
