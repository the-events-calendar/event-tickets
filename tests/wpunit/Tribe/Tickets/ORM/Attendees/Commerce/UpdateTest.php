<?php

namespace Tribe\Tickets\ORM\Attendees\Commerce;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

/**
 * Class UpdateTest
 *
 * @package Tribe\Tickets\ORM\Attendees\Commerce
 * @group orm-create-update
 */
class UpdateTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;

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
	 * It should allow updating an attendee for a Tribe Commmerce ticket.
	 *
	 * @test
	 */
	public function should_allow_updating_an_attendee_for_a_tribe_commerce_ticket() {
		/** @var \Tribe__Tickets__Repositories__Attendee__Commerce $attendees */
		$attendees = tribe_attendees( 'tribe-commerce' );

		$post_id = $this->factory->post->create();

		$attendee_data = [
			'full_name' => 'A test attendee',
			'email'     => 'attendee@test.com',
			'user_id'   => 1234,
			'order_id'  => 'paypal-transaction-id',
			// @todo Add a test that tests that you can override the title, default is order_id | full_name.
			//'title' => 'My custom title',
			// @todo Add a test that tests that you can override the status, default is completed.
			//'attendee_status' => 'pending',
			// @todo Add a test that tests that you can override the optout, default is yes.
			//'optout' => 0,
		];

		$ticket_id = $this->create_paypal_ticket( $post_id );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $provider */
		$provider = tribe( 'tickets.commerce.paypal' );

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
		$this->assertEquals( $attendee_data['order_id'] . ' | ' . $attendee_data['full_name'], $updated_attendee->post_title );

		// These things should be the same no matter what update.
		$this->assertEquals( $provider::ATTENDEE_OBJECT, $attendee->post_type );
		$this->assertEquals( 'publish', $attendee->post_status );
		$this->assertEquals( 0, $attendee->post_parent );

		// Confirm the original attendee data is intact.
		$this->assertEquals( $ticket_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_PRODUCT_KEY, true ) );
		$this->assertEquals( $post_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_EVENT_KEY, true ) );
		$this->assertEquals( 'completed', get_post_meta( $attendee->ID, $provider->attendee_tpp_key, true ) );
		$this->assertEquals( '1', get_post_meta( $attendee->ID, $provider->attendee_optout_key, true ) );
		$this->assertEquals( $attendee_data['order_id'], get_post_meta( $attendee->ID, $provider->order_key, true ) );
		$this->assertNotEmpty( get_post_meta( $attendee->ID, $provider->security_code, true ) );

		// Confirm the attendee meta was updated as intended.
		$this->assertEquals( $updated_attendee_data['full_name'], get_post_meta( $attendee->ID, $provider->full_name, true ) );
		$this->assertEquals( $updated_attendee_data['email'], get_post_meta( $attendee->ID, $provider->email, true ) );
		$this->assertEquals( $updated_attendee_data['user_id'], (int) get_post_meta( $attendee->ID, $provider->attendee_user_id, true ) );
	}
}
