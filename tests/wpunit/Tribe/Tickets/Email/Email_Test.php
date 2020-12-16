<?php

namespace Tribe\Tickets\Emails;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

class Email_Test extends \Codeception\TestCase\WPTestCase {

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
	 * It should increment the email sent counter for RSVP tickets.
	 *
	 * @test
	 */
	public function should_increment_email_sent_counter_for_rsvp_tickets() {
		$post_id = $this->factory->post->create();

		$rsvp_ticket_id    = $this->create_rsvp_ticket( $post_id );
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 1, $rsvp_ticket_id, $post_id, [ 'ticket_sent' => false ] );
		$attendee_id       = $rsvp_attendee_ids[0];

		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		$ticket_sent_count = (int) get_post_meta( $attendee_id, $rsvp->attendee_ticket_sent, true );

		// Before any ticket sent this should be 0.
		$this->assertEquals( 0, $ticket_sent_count );

		// Send Tickets Email.
		$rsvp->send_tickets_email( $attendee_id, $post_id );

		$ticket_sent_count = (int) get_post_meta( $attendee_id, $rsvp->attendee_ticket_sent, true );

		// After sending email should be 1.
		$this->assertEquals( 1, $ticket_sent_count );

		// Again updating the counter.
		$rsvp->update_ticket_sent_counter( $attendee_id, $rsvp->attendee_ticket_sent );

		$ticket_sent_count = (int) get_post_meta( $attendee_id, $rsvp->attendee_ticket_sent, true );

		$this->assertEquals( 2, $ticket_sent_count );
	}

	/**
	 * It should increment the email sent counter for TribeCommerce tickets.
	 *
	 * @test
	 */
	public function should_increment_email_sent_counter_for_paypal_tickets() {
		$post_id     = $this->factory->post->create();
		$ticket_id   = $this->create_paypal_ticket( $post_id, 11 );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'ticket_sent' => false ] );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $provider */
		$provider = tribe( 'tickets.commerce.paypal' );

		$ticket_sent_count = (int) get_post_meta( $attendee_id, $provider->attendee_ticket_sent, true );

		// Before any ticket sent this should be 0.
		$this->assertEquals( 0, $ticket_sent_count );

		$provider->update_ticket_sent_counter( $attendee_id, $provider->attendee_ticket_sent );
		$ticket_sent_count = (int) get_post_meta( $attendee_id, $provider->attendee_ticket_sent, true );

		// After updating once should be 1.
		$this->assertEquals( 1, $ticket_sent_count );

		// Again updating the counter.
		$provider->update_ticket_sent_counter( $attendee_id, $provider->attendee_ticket_sent );
		$ticket_sent_count = (int) get_post_meta( $attendee_id, $provider->attendee_ticket_sent, true );

		// After updating again should be 2.
		$this->assertEquals( 2, $ticket_sent_count );
	}

	/**
	 * It should check if email activities are logged properly for RSVP.
	 *
	 * @test
	 */
	public function should_log_email_activity_data_for_rsvp() {
		$post_id = $this->factory->post->create();

		$rsvp_ticket_id    = $this->create_rsvp_ticket( $post_id );
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 1, $rsvp_ticket_id, $post_id, [ 'ticket_sent' => false ] );
		$attendee_id       = $rsvp_attendee_ids[0];

		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		$activity = (bool) get_post_meta( $attendee_id, $rsvp->attendee_activity_log, true );

		// Should be false as no activity logged yet.
		$this->assertFalse( $activity );

		// Send Tickets Email.
		$rsvp->send_tickets_email( $attendee_id, $post_id );

		$activity = get_post_meta( $attendee_id, $rsvp->attendee_activity_log, true );

		// Activity log is created.
		$this->assertCount( 1, $activity );

		$dummy_data = [
			'type'  => 'test',
			'name'  => 'dummy_name',
			'email' => 'dummy_email@mail.com',
		];

		$rsvp->update_attendee_activity_log( $attendee_id, $dummy_data );

		$activity = get_post_meta( $attendee_id, $rsvp->attendee_activity_log, true );

		// Activity log is created.
		$this->assertCount( 2, $activity );

		// Remove the timestamp data from inserted dummy data.
		unset( $activity[1]['time'] );

		// Make sure that dummy data is same as passed.
		$this->assertEqualSets( $activity[1], $dummy_data );
	}
}
