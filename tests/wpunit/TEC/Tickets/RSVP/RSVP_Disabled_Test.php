<?php
/**
 * Tests for the RSVP_Disabled null-object class.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;

/**
 * Class RSVP_Disabled_Test
 *
 * @since TBD
 */
class RSVP_Disabled_Test extends WPTestCase {
	/**
	 * The RSVP_Disabled instance.
	 *
	 * @var RSVP_Disabled
	 */
	private $rsvp_disabled;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp_disabled = new RSVP_Disabled();
	}

	public function test_get_tickets_returns_empty_array(): void {
		$this->assertSame( [], $this->rsvp_disabled->get_tickets( 1 ) );
	}

	public function test_get_ticket_returns_null(): void {
		$this->assertNull( $this->rsvp_disabled->get_ticket( 1, 2 ) );
	}

	public function test_get_attendees_by_id_returns_empty_array(): void {
		$this->assertSame( [], $this->rsvp_disabled->get_attendees_by_id( 1 ) );
	}

	public function test_get_attendee_returns_false(): void {
		$this->assertFalse( $this->rsvp_disabled->get_attendee( 1 ) );
	}

	public function test_get_attendees_count_going_returns_zero(): void {
		$this->assertSame( 0, $this->rsvp_disabled->get_attendees_count_going( 1 ) );
	}

	public function test_get_attendees_count_not_going_returns_zero(): void {
		$this->assertSame( 0, $this->rsvp_disabled->get_attendees_count_not_going( 1 ) );
	}

	public function test_save_ticket_returns_false(): void {
		$this->assertFalse( $this->rsvp_disabled->save_ticket( 1, null ) );
	}

	public function test_delete_ticket_returns_false(): void {
		$this->assertFalse( $this->rsvp_disabled->delete_ticket( 1, 2 ) );
	}

	public function test_checkin_returns_false(): void {
		$this->assertFalse( $this->rsvp_disabled->checkin( 1 ) );
	}

	public function test_uncheckin_returns_false(): void {
		$this->assertFalse( $this->rsvp_disabled->uncheckin( 1 ) );
	}

	public function test_get_event_for_ticket_returns_null(): void {
		$this->assertNull( $this->rsvp_disabled->get_event_for_ticket( 1 ) );
	}

	public function test_get_order_data_returns_empty_array(): void {
		$this->assertSame( [], $this->rsvp_disabled->get_order_data( 1 ) );
	}

	public function test_get_messages_returns_empty_array(): void {
		$this->assertSame( [], $this->rsvp_disabled->get_messages() );
	}

	public function test_get_statuses_by_action_returns_empty_array(): void {
		$this->assertSame( [], $this->rsvp_disabled->get_statuses_by_action( 'going' ) );
	}

	public function test_create_attendee_for_ticket_throws_exception(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Cannot create RSVP attendee: RSVP feature is disabled.' );
		$this->rsvp_disabled->create_attendee_for_ticket( null, [] );
	}

	public function test_is_not_going_enabled_returns_false(): void {
		$this->assertFalse( $this->rsvp_disabled->is_not_going_enabled( 1 ) );
	}

	public function test_init_is_noop(): void {
		// Should not throw or have side effects.
		$this->rsvp_disabled->init();
		$this->assertTrue( true );
	}

	public function test_register_types_is_noop(): void {
		// Should not throw or have side effects.
		$this->rsvp_disabled->register_types();
		$this->assertTrue( true );
	}
}
