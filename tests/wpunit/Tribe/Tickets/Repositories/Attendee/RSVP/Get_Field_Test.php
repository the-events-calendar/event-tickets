<?php

namespace Tribe\Tickets\Repositories\Attendee\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Test the get_field() method in the RSVP Attendee Repository.
 *
 * @package Tribe\Tickets\Repositories\Attendee\RSVP
 */
class Get_Field_Test extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
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
	}

	/**
	 * It should get attendee_status field using alias.
	 *
	 * @test
	 */
	public function should_get_attendee_status_field_using_alias() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$status = $repository->get_field( $attendee_id, 'attendee_status' );

		$this->assertEquals( 'yes', $status );
	}

	/**
	 * It should get email field.
	 *
	 * @test
	 */
	public function should_get_email_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$rsvp = tribe( 'tickets.rsvp' );
		$expected_email = get_post_meta( $attendee_id, $rsvp->email, true );

		$repository = tribe_attendees( 'rsvp' );
		$email = $repository->get_field( $attendee_id, 'email' );

		$this->assertEquals( $expected_email, $email );
	}

	/**
	 * It should get full_name field.
	 *
	 * @test
	 */
	public function should_get_full_name_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$rsvp = tribe( 'tickets.rsvp' );
		$expected_name = get_post_meta( $attendee_id, $rsvp->full_name, true );

		$repository = tribe_attendees( 'rsvp' );
		$name = $repository->get_field( $attendee_id, 'full_name' );

		$this->assertEquals( $expected_name, $name );
	}

	/**
	 * It should get ticket_id field.
	 *
	 * @test
	 */
	public function should_get_ticket_id_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$retrieved_ticket_id = $repository->get_field( $attendee_id, 'ticket_id' );

		$this->assertEquals( $ticket_id, $retrieved_ticket_id );
	}

	/**
	 * It should get event_id field.
	 *
	 * @test
	 */
	public function should_get_event_id_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$event_id = $repository->get_field( $attendee_id, 'event_id' );

		$this->assertEquals( $post_id, $event_id );
	}

	/**
	 * It should get security_code field.
	 *
	 * @test
	 */
	public function should_get_security_code_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$security_code = $repository->get_field( $attendee_id, 'security_code' );

		$this->assertNotEmpty( $security_code );
	}

	/**
	 * It should get order_id field.
	 *
	 * @test
	 */
	public function should_get_order_id_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$rsvp = tribe( 'tickets.rsvp' );
		$expected_order_id = get_post_meta( $attendee_id, $rsvp->order_key, true );

		$repository = tribe_attendees( 'rsvp' );
		$retrieved_order_id = $repository->get_field( $attendee_id, 'order_id' );

		$this->assertEquals( $expected_order_id, $retrieved_order_id );
	}

	/**
	 * It should get user_id field.
	 *
	 * @test
	 */
	public function should_get_user_id_field() {
		$user_id = $this->factory->user->create();
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'user_id' => $user_id,
		] );

		$repository = tribe_attendees( 'rsvp' );
		$retrieved_user_id = $repository->get_field( $attendee_id, 'user_id' );

		$this->assertEquals( $user_id, $retrieved_user_id );
	}

	/**
	 * It should get optout field.
	 *
	 * @test
	 */
	public function should_get_optout_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'optout' => '1',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$optout = $repository->get_field( $attendee_id, 'optout' );

		$this->assertEquals( '1', $optout );
	}

	/**
	 * It should return empty string for non-existent field.
	 *
	 * @test
	 */
	public function should_return_empty_string_for_nonexistent_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$value = $repository->get_field( $attendee_id, 'non_existent_field' );

		$this->assertSame( '', $value );
	}

	/**
	 * It should return empty string for non-existent attendee.
	 *
	 * @test
	 */
	public function should_return_empty_string_for_nonexistent_attendee() {
		$repository = tribe_attendees( 'rsvp' );
		$value = $repository->get_field( 99999, 'email' );

		$this->assertSame( '', $value );
	}

	/**
	 * It should get field by direct meta key (no alias).
	 *
	 * @test
	 */
	public function should_get_field_by_direct_meta_key() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$rsvp = tribe( 'tickets.rsvp' );
		$expected_email = get_post_meta( $attendee_id, $rsvp->email, true );

		$repository = tribe_attendees( 'rsvp' );
		$email = $repository->get_field( $attendee_id, $rsvp->email );

		$this->assertEquals( $expected_email, $email );
	}

	/**
	 * It should distinguish empty string from nonexistent field.
	 *
	 * @test
	 */
	public function should_distinguish_empty_string_from_nonexistent_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		// Set a field to empty string
		update_post_meta( $attendee_id, '_custom_field', '' );

		$repository = tribe_attendees( 'rsvp' );

		// Empty string should return empty string, not null
		$this->assertSame( '', $repository->get_field( $attendee_id, '_custom_field' ) );

		// Nonexistent field should return empty string
		$this->assertSame( '', $repository->get_field( $attendee_id, 'nonexistent_field' ) );
	}
}
