<?php

namespace Tribe\Tickets\ORM\Attendees\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use WP_Post;

/**
 * Class CreateTest
 *
 * @package Tribe\Tickets\ORM\Attendees\RSVP
 * @group orm-create-update
 */
class CreateTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;

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
	 * It should not allow creating an attendee from the rsvp context without required args.
	 *
	 * @test
	 */
	public function should_not_allow_creating_attendee_from_rsvp_context_without_required_args() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees( 'rsvp' );

		$args = [
			'title' => 'A test attendee',
		];

		$attendee = $attendees->set_args( $args )->create();

		$this->assertFalse( $attendee );
	}

	/**
	 * It should not allow creating an attendee from an RSVP ticket ID.
	 *
	 * @test
	 */
	public function should_not_allow_creating_an_attendee_from_an_rsvp_ticket_id() {
		/** @var \Tribe__Tickets__Repositories__Attendee__RSVP $attendees */
		$attendees = tribe_attendees( 'rsvp' );

		$post_id = $this->factory->post->create();

		$attendee_data = [
			'full_name' => 'A test attendee',
			'email'     => 'attendee@test.com',
		];

		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->expectException( \Tribe__Repository__Usage_Error::class );

		$attendees->create_attendee_for_ticket( $attendee_data, $ticket_id );
	}

	/**
	 * It should allow creating an attendee from an RSVP ticket object.
	 *
	 * @test
	 */
	public function should_allow_creating_an_attendee_from_an_rsvp_ticket_object() {
		/** @var \Tribe__Tickets__Repositories__Attendee__RSVP $attendees */
		$attendees = tribe_attendees( 'rsvp' );

		$post_id = $this->factory->post->create();

		$attendee_data = [
			'full_name' => 'A test attendee',
			'email'     => 'attendee@test.com',
			'user_id'   => 1234,
			// @todo Add a test that tests that you can override the title, default is full_name.
			//'title' => 'My custom title',
			// @todo Add a test that tests that you can override the status, default is yes.
			//'attendee_status' => 'no',
			// @todo Add a test that tests that you can override the optout, default is yes.
			//'optout' => null,
		];

		$ticket_id = $this->create_rsvp_ticket( $post_id );

		/** @var \Tribe__Tickets__RSVP $provider */
		$provider = tribe( 'tickets.rsvp' );

		$ticket = $provider->get_ticket( $post_id, $ticket_id );

		$attendee = $attendees->create_attendee_for_ticket( $ticket, $attendee_data );

		$meta = get_post_meta( $attendee->ID );

		// Confirm the attendee was created as intended.
		$this->assertInstanceOf( WP_Post::class, $attendee );
		$this->assertEquals( $attendee_data['full_name'], $attendee->post_title );
		$this->assertEquals( $provider::ATTENDEE_OBJECT, $attendee->post_type );
		$this->assertEquals( 'publish', $attendee->post_status );
		$this->assertEquals( 0, $attendee->post_parent );

		// Confirm the attendee meta was set as intended.
		$this->assertEquals( $attendee_data['full_name'], get_post_meta( $attendee->ID, $provider->full_name, true ) );
		$this->assertEquals( $attendee_data['email'], get_post_meta( $attendee->ID, $provider->email, true ) );
		$this->assertEquals( $ticket_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_PRODUCT_KEY, true ) );
		$this->assertEquals( $post_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_EVENT_KEY, true ) );
		$this->assertEquals( 'yes', get_post_meta( $attendee->ID, $provider::ATTENDEE_RSVP_KEY, true ) );
		$this->assertEquals( '1', get_post_meta( $attendee->ID, $provider::ATTENDEE_OPTOUT_KEY, true ) );
		$this->assertNotEmpty( get_post_meta( $attendee->ID, $provider->order_key, true ) );
		$this->assertNotEmpty( get_post_meta( $attendee->ID, $provider->security_code, true ) );
		$this->assertEquals( $attendee_data['user_id'], (int) get_post_meta( $attendee->ID, $provider->attendee_user_id, true ) );
		$this->assertCount( 9, $meta, 'There appears to be untested meta on this attendee, please add them to the test: ' . var_export( $meta, true ) );
	}
}
