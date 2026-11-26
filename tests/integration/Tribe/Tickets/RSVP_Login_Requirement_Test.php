<?php
/**
 * Integration tests for the RSVP login requirement on RSVP V1.
 *
 * @since TBD
 *
 * @package Tribe\Tickets
 */

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Error;

/**
 * Class RSVP_Login_Requirement_Test.
 *
 * @since TBD
 *
 * @covers \Tribe__Tickets__RSVP::generate_tickets_for
 * @covers \Tribe__Tickets__RSVP::generate_tickets
 *
 * @package Tribe\Tickets
 */
class RSVP_Login_Requirement_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_refuse_a_guest_rsvp_when_rsvp_requires_login(): void {
		$ticket = $this->make_rsvp( 5 );
		$this->require_rsvp_login( true );
		wp_set_current_user( 0 );

		$result = RSVP::get_instance()->generate_tickets_for( $ticket->ID, 1, $this->attendee_details(), false );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'rsvp-login-required', $result->get_error_code() );
		$this->assertSame( 5, $this->inventory( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_guest_rsvp_form_submission_when_rsvp_requires_login(): void {
		$ticket = $this->make_rsvp( 5 );
		$this->require_rsvp_login( true );
		wp_set_current_user( 0 );
		$_POST['attendee']                  = $this->attendee_details();
		$_POST['product_id']                = $ticket->ID;
		$_POST[ 'quantity_' . $ticket->ID ] = 1;

		$result = RSVP::get_instance()->generate_tickets( $ticket->get_event_id(), false );
		$_POST  = [];

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'rsvp-login-required', $result->get_error_code() );
		$this->assertSame( 5, $this->inventory( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_accept_a_logged_in_rsvp_when_rsvp_requires_login(): void {
		$ticket = $this->make_rsvp( 5 );
		$this->require_rsvp_login( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$result = RSVP::get_instance()->generate_tickets_for( $ticket->ID, 1, $this->attendee_details(), false );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 4, $this->inventory( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_accept_a_guest_rsvp_when_rsvp_does_not_require_login(): void {
		$ticket = $this->make_rsvp( 5 );
		$this->require_rsvp_login( false );
		wp_set_current_user( 0 );

		$result = RSVP::get_instance()->generate_tickets_for( $ticket->ID, 1, $this->attendee_details(), false );

		$this->assertIsArray( $result );
		$this->assertSame( 4, $this->inventory( $ticket ) );
	}

	/**
	 * Creates a published page with an RSVP of the given capacity.
	 *
	 * @param int $capacity The RSVP capacity.
	 *
	 * @return Ticket_Object The RSVP ticket.
	 */
	private function make_rsvp( int $capacity ): Ticket_Object {
		/* Other tests in the suite change which post types take tickets; pages must, here. */
		add_filter( 'tribe_tickets_post_types', static fn( array $types ) => array_unique( array_merge( $types, [ 'page' ] ) ) );

		$post_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$rsvp    = RSVP::get_instance();
		$rsvp->ticket_add(
			$post_id,
			[
				'ticket_name'        => 'Members RSVP',
				'ticket_description' => '',
				'tribe-ticket'       => [ 'capacity' => $capacity ],
			]
		);

		return $rsvp->get_event_tickets( $post_id )[0];
	}

	/**
	 * Gets the RSVP's remaining inventory, read fresh.
	 *
	 * @param Ticket_Object $ticket The RSVP ticket.
	 *
	 * @return int The remaining inventory.
	 */
	private function inventory( Ticket_Object $ticket ): int {
		return RSVP::get_instance()->get_ticket( $ticket->get_event_id(), $ticket->ID )->inventory();
	}

	/**
	 * Builds the attendee details of one guest.
	 *
	 * @return array<string,string|int> The attendee details.
	 */
	private function attendee_details(): array {
		return [
			'full_name'    => 'Guest Visitor',
			'email'        => 'guest@example.test',
			'order_status' => 'yes',
			'optout'       => 'no',
			'order_id'     => RSVP::generate_order_id(),
		];
	}

	/**
	 * Turns the "Require users to log in before they RSVP" setting on or off for this test.
	 *
	 * A filter rather than the option, because the settings cache outlives the test's database rollback.
	 *
	 * @param bool $required Whether RSVP requires login.
	 */
	private function require_rsvp_login( bool $required ): void {
		add_filter(
			'tribe_get_option_ticket-authentication-requirements',
			static fn() => $required ? [ 'event-tickets_rsvp' ] : []
		);
	}
}
