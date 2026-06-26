<?php

namespace TEC\Tickets\Admin;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Integration tests for the Glance_Items attendee-count fix (Panel > Dashboard > "At a Glance" widget).
 *
 * Covers scenarios introduced by the SMTNC-1631:
 *
 * 1. update_attendee_count() sets the transient to the real attendee count
 *    when attendees exist.
 * 2. update_attendee_count() sets the transient to integer 0 (not false)
 *    when there are no attendees — the old code left the transient unset,
 *    causing WP-Cron to reschedule itself on every dashboard page load.
 * 3. The `tec_tickets_glance_item_attendee_count_enabled` filter returning
 *    false prevents both the transient from being set in
 *    update_attendee_count() and the cron from being scheduled in
 *    custom_glance_items_attendees().
 * 4. update_attendee_count() aggregates attendees across multiple events.
 * 5. RSVP "not going" attendees are included in the count (the Repository does
 *    not filter on the going/not-going meta key).
 * 6. Attendees of hard-deleted events are still counted, because deleting an
 *    event does not cascade-delete its attendee posts.
 *
 * @covers \TEC\Tickets\Admin\Glance_Items
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */
class Glance_ItemsTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * The transient key used by Glance_Items to cache the attendee count.
	 *
	 * @var string
	 */
	private const TRANSIENT_KEY = 'tec_tickets_glance_item_attendees_count';

	/**
	 * The WP-Cron action that triggers an attendee-count refresh.
	 *
	 * @var string
	 */
	private const CRON_HOOK = 'tec_tickets_update_glance_item_attendee_counts';

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_transient( self::TRANSIENT_KEY );
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * update_attendee_count() stores the correct attendee total in the transient.
	 *
	 * @test
	 */
	public function update_attendee_count_sets_transient_to_real_count_when_attendees_exist(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id );

		tribe( Glance_Items::class )->update_attendee_count();

		$stored = get_transient( self::TRANSIENT_KEY );

		$this->assertNotFalse( $stored, 'Transient should be set after update_attendee_count().' );
		$this->assertSame( 3, (int) $stored, 'Transient value should equal the number of created attendees.' );
	}

	/**
	 * update_attendee_count() stores integer 0 in the transient even when
	 * there are no attendees, preventing the infinite cron-rescheduling loop.
	 *
	 * Before the fix, the method returned early without calling set_transient()
	 * when the count was zero. get_transient() then returned false, which caused
	 * custom_glance_items_attendees() to reschedule the cron on every page load.
	 *
	 * @test
	 */
	public function update_attendee_count_sets_transient_to_zero_when_no_attendees_exist(): void {
		tribe( Glance_Items::class )->update_attendee_count();

		$stored = get_transient( self::TRANSIENT_KEY );

		$this->assertNotFalse( $stored, 'Transient should be set even when the attendee count is zero.' );
		$this->assertSame( 0, (int) $stored, 'Transient value should be integer 0 when no attendees exist.' );
	}

	/**
	 * When tec_tickets_glance_item_attendee_count_enabled returns false,
	 * update_attendee_count() must not write the transient.
	 *
	 * @test
	 */
	public function update_attendee_count_does_not_set_transient_when_filter_disabled(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id );

		add_filter( 'tec_tickets_glance_item_attendee_count_enabled', '__return_false' );

		tribe( Glance_Items::class )->update_attendee_count();

		$stored = get_transient( self::TRANSIENT_KEY );

		$this->assertFalse( $stored, 'Transient should NOT be set when the filter disables the glance item.' );
	}

	/**
	 * When tec_tickets_glance_item_attendee_count_enabled returns false,
	 * custom_glance_items_attendees() must not schedule the cron event.
	 *
	 * @test
	 */
	public function custom_glance_items_attendees_does_not_schedule_cron_when_filter_disabled(): void {
		add_filter( 'tec_tickets_glance_item_attendee_count_enabled', '__return_false' );

		tribe( Glance_Items::class )->custom_glance_items_attendees( [] );

		$next_scheduled = wp_next_scheduled( self::CRON_HOOK );

		$this->assertFalse(
			$next_scheduled,
			'The cron event should NOT be scheduled when the filter disables the glance item.'
		);
	}

	/**
	 * update_attendee_count() aggregates attendees across multiple events.
	 *
	 * Verifies that the Repository COUNT query is not scoped to a single event —
	 * attendees from all events are included in the dashboard total.
	 *
	 * @test
	 */
	public function update_attendee_count_counts_attendees_across_multiple_events(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );

		// Three independent events, each with a different number of attendees.
		$post_a    = static::factory()->post->create();
		$ticket_a  = $this->create_rsvp_ticket( $post_a );
		$this->create_many_attendees_for_ticket( 2, $ticket_a, $post_a );

		$post_b    = static::factory()->post->create();
		$ticket_b  = $this->create_rsvp_ticket( $post_b );
		$this->create_many_attendees_for_ticket( 3, $ticket_b, $post_b );

		$post_c    = static::factory()->post->create();
		$ticket_c  = $this->create_rsvp_ticket( $post_c );
		$this->create_many_attendees_for_ticket( 1, $ticket_c, $post_c );

		tribe( Glance_Items::class )->update_attendee_count();

		$stored = get_transient( self::TRANSIENT_KEY );

		$this->assertNotFalse( $stored, 'Transient should be set when attendees exist across multiple events.' );
		$this->assertSame( 6, (int) $stored, 'Count should be the sum of attendees across all events (2 + 3 + 1 = 6).' );
	}

	/**
	 * update_attendee_count() includes RSVP "not going" attendees in the total.
	 *
	 * The merged tribe_attendees() Repository queries by attendee post type with
	 * post_status = 'publish' and does not filter on the RSVP going/not-going
	 * meta key. "Not going" attendees are thus included in the dashboard count.
	 *
	 * @test
	 */
	public function update_attendee_count_includes_rsvp_not_going_attendees(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// One "going" and one "not going" attendee.
		$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );

		tribe( Glance_Items::class )->update_attendee_count();

		$stored = get_transient( self::TRANSIENT_KEY );

		$this->assertNotFalse( $stored, 'Transient should be set when attendees exist.' );
		$this->assertSame( 2, (int) $stored, 'Both "going" and "not going" RSVP attendees should be included in the count.' );
	}

	/**
	 * update_attendee_count() still counts attendees whose event has been deleted.
	 *
	 * Deleting an event post does not cascade-delete its attendee posts.
	 * The attendee records remain published in the database and are included
	 * in the global count, because tribe_attendees() does not filter by whether
	 * the parent event still exists.
	 *
	 * @test
	 */
	public function update_attendee_count_includes_attendees_of_deleted_events(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id );

		// Hard-delete the parent event; attendee posts are NOT cascade-deleted.
		wp_delete_post( $post_id, true );

		tribe( Glance_Items::class )->update_attendee_count();

		$stored = get_transient( self::TRANSIENT_KEY );

		$this->assertNotFalse( $stored, 'Transient should be set even when the parent event is deleted.' );
		$this->assertSame( 2, (int) $stored, 'Attendees of deleted events should still appear in the total count.' );
	}
}
