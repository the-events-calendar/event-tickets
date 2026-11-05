<?php

namespace TEC\Tickets\QR;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;

/**
 * A scanned QR code only checks the attendee in for a user who can manage the attendees of the attendee's event.
 */
class Observer_Test extends WPTestCase {
	use Order_Maker;
	use With_Ticket_Providers;

	/**
	 * @test
	 */
	public function it_should_not_check_in_for_a_user_who_cannot_manage_the_attendees_of_the_event(): void {
		$post_id     = static::factory()->post->create();
		$attendee_id = $this->make_attendee( $post_id );
		$this->log_in_as_contributor_with_own_draft();

		$result = tribe( Observer::class )->authorized_check_in( $post_id, $attendee_id, $this->security_code( $attendee_id ) );

		$this->assertFalse( $result['user_had_access'] );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_check_in_for_an_editor_of_another_authors_event(): void {
		$post_id     = $this->log_in_as_editor_of_another_authors_post();
		$attendee_id = $this->make_attendee( $post_id );

		$result = tribe( Observer::class )->authorized_check_in( $post_id, $attendee_id, $this->security_code( $attendee_id ) );

		$this->assertTrue( $result['user_had_access'] );
		$this->assertNotEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_check_in_for_a_user_a_plugin_grants_attendee_management(): void {
		$post_id     = static::factory()->post->create();
		$attendee_id = $this->make_attendee( $post_id );
		$this->log_in_as_contributor_with_own_draft();
		// Community Events grants attendee management to any logged-in user in the admin when an event is given.
		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );

		$result = tribe( Observer::class )->authorized_check_in( $post_id, $attendee_id, $this->security_code( $attendee_id ) );

		$this->assertFalse( $result['user_had_access'] );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_check_in_for_the_author_of_the_event(): void {
		$post_id     = $this->log_in_as_author_with_own_post();
		$attendee_id = $this->make_attendee( $post_id );

		$result = tribe( Observer::class )->authorized_check_in( $post_id, $attendee_id, $this->security_code( $attendee_id ) );

		$this->assertTrue( $result['user_had_access'] );
		$this->assertNotEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	private function make_attendee( int $post_id ): int {
		$order = $this->create_order( [ $this->create_tc_ticket( $post_id ) => 1 ] );

		return current( tribe( Commerce::class )->get_attendees_by_order_id( $order->ID ) )['attendee_id'];
	}

	private function security_code( int $attendee_id ): string {
		return get_post_meta( $attendee_id, Attendee::$security_code_meta_key, true );
	}
}
