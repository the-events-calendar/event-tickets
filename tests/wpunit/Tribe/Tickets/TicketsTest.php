<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe__Tickets__Tickets as Tickets;

class TicketsTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * It should allow fetching ticket attendees.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees() {
		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$paypal_attendee_ids2 = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id2, $post_id2 );
		$rsvp_attendee_ids2   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id2, $post_id2 );

		$attendees    = Tickets::get_event_attendees( $post_id );
		$attendee_ids = wp_list_pluck( $attendees, 'attendee_id' );

		$this->assertEqualSets( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ), $attendee_ids );

		$attendees2    = Tickets::get_event_attendees( $post_id2 );
		$attendee_ids2 = wp_list_pluck( $attendees2, 'attendee_id' );

		$this->assertEqualSets( array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 ), $attendee_ids2 );
	}

	/**
	 * It should allow fetching ticket attendees count.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_count() {
		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$paypal_attendee_ids2 = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id2, $post_id2 );
		$rsvp_attendee_ids2   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id2, $post_id2 );

		$this->assertEquals( 10, Tickets::get_event_attendees_count( $post_id ) );
		$this->assertEquals( 10, Tickets::get_event_attendees_count( $post_id2 ) );
	}

	/**
	 * It should allow fetching ticket attendees count by user.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_count_by_user() {
		$user_id = $this->factory->user->create();

		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id, [ 'user_id' => $user_id ] );
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id, [ 'user_id' => $user_id ] );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$this->create_many_attendees_for_ticket( 5, $paypal_ticket_id2, $post_id2 );
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id2, $post_id2 );

		$this->create_many_attendees_for_ticket( 5, $paypal_ticket_id2, $post_id2, [ 'user_id' => $user_id ] );
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id2, $post_id2, [ 'user_id' => $user_id ] );

		$this->assertEquals( 10, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'user' => $user_id ] ] ) );
		$this->assertEquals( 10, Tickets::get_event_attendees_count( $post_id2, [ 'by' => [ 'user' => $user_id ] ] ) );
	}

	/**
	 * It should allow fetching ticket attendees count by provider.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_count_by_provider() {
		$user_id = $this->factory->user->create();

		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 4, $paypal_ticket_id, $post_id );
		$this->create_many_attendees_for_ticket( 6, $rsvp_ticket_id, $post_id );

		$this->assertEquals( 4, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider' => 'tribe-commerce' ] ] ) );
		$this->assertEquals( 6, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider' => 'rsvp' ] ] ) );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$this->create_many_attendees_for_ticket( 6, $paypal_ticket_id2, $post_id2 );
		$this->create_many_attendees_for_ticket( 4, $rsvp_ticket_id2, $post_id2 );

		$this->assertEquals( 6, Tickets::get_event_attendees_count( $post_id2, [ 'by' => [ 'provider' => 'tribe-commerce' ] ] ) );
		$this->assertEquals( 4, Tickets::get_event_attendees_count( $post_id2, [ 'by' => [ 'provider' => 'rsvp' ] ] ) );
	}

	/**
	 * It should allow fetching ticket attendees count by provider__not_in.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_count_by_provider__not_in() {
		$user_id = $this->factory->user->create();

		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 4, $paypal_ticket_id, $post_id );
		$this->create_many_attendees_for_ticket( 6, $rsvp_ticket_id, $post_id );

		$this->assertEquals( 4, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider__not_in' => 'rsvp' ] ] ) );
		$this->assertEquals( 6, Tickets::get_event_attendees_count( $post_id2, [ 'by' => [ 'provider__not_in' => 'tribe-commerce' ] ] ) );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$this->create_many_attendees_for_ticket( 6, $paypal_ticket_id2, $post_id2 );
		$this->create_many_attendees_for_ticket( 4, $rsvp_ticket_id2, $post_id2 );

		$this->assertEquals( 6, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider__not_in' => 'rsvp' ] ] ) );
		$this->assertEquals( 4, Tickets::get_event_attendees_count( $post_id2, [ 'by' => [ 'provider__not_in' => 'tribe-commerce' ] ] ) );
	}

	/**
	 * It should allow fetching ticket attendees checkedin count.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_checkedin_count() {
		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$paypal_attendee_ids2 = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id2, $post_id2 );
		$rsvp_attendee_ids2   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id2, $post_id2 );

		$paypal_main = tribe( 'tickets.commerce.paypal' );
		$rsvp_main   = tribe( 'tickets.rsvp' );

		update_post_meta( current( $paypal_attendee_ids ), $paypal_main->checkin_key, 1 );
		update_post_meta( current( $rsvp_attendee_ids ), $rsvp_main->checkin_key, 1 );

		update_post_meta( current( $paypal_attendee_ids2 ), $paypal_main->checkin_key, 1 );
		update_post_meta( current( $rsvp_attendee_ids2 ), $rsvp_main->checkin_key, 1 );

		$this->assertEquals( 2, Tickets::get_event_checkedin_attendees_count( $post_id ) );
		$this->assertEquals( 2, Tickets::get_event_checkedin_attendees_count( $post_id2 ) );
	}

}
