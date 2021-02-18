<?php

namespace Tribe\Tickets\ORM\Attendees\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use WP_Post;

/**
 * Class UpdateTest
 *
 * @package Tribe\Tickets\ORM\Attendees\RSVP
 * @group orm-create-update
 */
class UpdateTest extends \Codeception\TestCase\WPTestCase {

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
	 * It should allow updating an attendee for an RSVP ticket.
	 *
	 * @test
	 */
	public function should_allow_updating_an_attendee_for_an_rsvp_ticket() {
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

		$updated_attendee_data = [
			'attendee_id' => $attendee->ID,
			'full_name'   => 'New full name',
			'email'       => 'new@email.com',
			'user_id'     => 1235,
		];

		$updated = $attendees->update_attendee( $updated_attendee_data );

		$updated_attendee = get_post( $attendee->ID );

		// Confirm the attendee was updated as intended.
		$this->assertEquals( [ $attendee->ID => true ], $updated );

		// The title should still be the same as it was before, we only changed the full_name.
		$this->assertEquals( $attendee_data['full_name'], $updated_attendee->post_title );

		// These things should be the same no matter what update.
		$this->assertEquals( $provider::ATTENDEE_OBJECT, $attendee->post_type );
		$this->assertEquals( 'publish', $attendee->post_status );
		$this->assertEquals( 0, $attendee->post_parent );

		// Confirm the original attendee data is intact.
		$this->assertEquals( $ticket_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_PRODUCT_KEY, true ) );
		$this->assertEquals( $post_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_EVENT_KEY, true ) );
		$this->assertEquals( 'yes', get_post_meta( $attendee->ID, $provider::ATTENDEE_RSVP_KEY, true ) );
		$this->assertEquals( '1', get_post_meta( $attendee->ID, $provider::ATTENDEE_OPTOUT_KEY, true ) );
		$this->assertNotEmpty( get_post_meta( $attendee->ID, $provider->order_key, true ) );
		$this->assertNotEmpty( get_post_meta( $attendee->ID, $provider->security_code, true ) );

		// Confirm the attendee meta was updated as intended.
		$this->assertEquals( $updated_attendee_data['full_name'], get_post_meta( $attendee->ID, $provider->full_name, true ) );
		$this->assertEquals( $updated_attendee_data['email'], get_post_meta( $attendee->ID, $provider->email, true ) );
		$this->assertEquals( $updated_attendee_data['user_id'], (int) get_post_meta( $attendee->ID, $provider->attendee_user_id, true ) );
	}
}
