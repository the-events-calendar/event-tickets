<?php
/**
 * Tests for Tickets Commerce attendee object-cache behavior.
 *
 * @since TBD
 */

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Class Attendee_Cache_Test.
 *
 * @since TBD
 */
class Attendee_Cache_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable post as ticketable type.
		add_filter(
			'tribe_tickets_post_types',
			static function () {
				return [ 'post' ];
			}
		);

		// Enable Tickets Commerce as a provider.
		add_filter(
			'tribe_tickets_get_modules',
			static function ( $modules ) {
				$modules[ Commerce::class ] = Commerce::class;

				return $modules;
			}
		);
	}

	/**
	 * It should return updated check-in status from cache after check-in without flushing.
	 *
	 * @test
	 */
	public function should_reflect_checkin_in_cached_attendee_without_flushing(): void {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_tc_ticket( $post_id, 10 );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		// Prime the week-long `tec_tc_get_attendee` cache while unchecked.
		$cached_before = tec_tc_get_attendee( $attendee_id, ARRAY_A );
		$this->assertNotEmpty( $cached_before );
		$this->assertEmpty( $cached_before['check_in'] );

		$this->assertTrue( tribe( Commerce::class )->checkin( $attendee_id ) );

		$cached_after = tec_tc_get_attendee( $attendee_id, ARRAY_A );

		$this->assertNotEmpty( $cached_after );
		$this->assertEquals(
			1,
			(int) $cached_after['check_in'],
			'Check-in must invalidate the cached attendee without an object-cache flush.'
		);
	}

	/**
	 * It should return updated check-in status from cache after uncheck-in without flushing.
	 *
	 * @test
	 */
	public function should_reflect_uncheckin_in_cached_attendee_without_flushing(): void {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_tc_ticket( $post_id, 10 );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		$commerce    = tribe( Commerce::class );

		$this->assertTrue( $commerce->checkin( $attendee_id ) );

		// Prime the week-long cache while checked in.
		$cached_before = tec_tc_get_attendee( $attendee_id, ARRAY_A );
		$this->assertNotEmpty( $cached_before );
		$this->assertEquals( 1, (int) $cached_before['check_in'] );

		$this->assertTrue( $commerce->uncheckin( $attendee_id ) );

		$cached_after = tec_tc_get_attendee( $attendee_id, ARRAY_A );

		$this->assertNotEmpty( $cached_after );
		$this->assertEmpty(
			$cached_after['check_in'],
			'Uncheck-in must invalidate the cached attendee without an object-cache flush.'
		);
	}
}
