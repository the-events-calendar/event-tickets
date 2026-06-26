<?php

namespace TEC\Tickets\Admin;

use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Integration tests for the Glance_Items attendee-count fix (Panel > Dashboard > "At a Glance" widget).
 *
 * Covers three scenarios introduced by the SMTNC-1631:
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
 *
 * @covers \TEC\Tickets\Admin\Glance_Items
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */
class Glance_ItemsTest extends \Codeception\TestCase\WPTestCase {

	use With_Uopz;
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

		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * update_attendee_count() stores the correct attendee total in the transient.
	 *
	 * @test
	 */
	public function update_attendee_count_sets_transient_to_real_count_when_attendees_exist(): void {
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
}
