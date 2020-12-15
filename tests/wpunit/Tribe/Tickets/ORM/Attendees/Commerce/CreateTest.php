<?php

namespace Tribe\Tickets\ORM\Attendees\Commerce;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use Tribe__Tickets__Data_API as Data_API;
use WP_Post;

/**
 * Class CreateTest
 *
 * @package Tribe\Tickets\ORM\Attendees\Commerce
 * @group orm-create-update
 */
class CreateTest extends \Codeception\TestCase\WPTestCase {

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
	 * It should not allow creating an attendee from the Tribe Commerce context without required args.
	 *
	 * @test
	 */
	public function should_not_allow_creating_attendee_from_tribe_commerce_context_without_required_args() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees( 'tribe-commerce' );

		$args = [
			'title' => 'A test attendee',
		];

		$attendee = $attendees->set_args( $args )->create();

		$this->assertFalse( $attendee );
	}

	/**
	 * It should not allow creating an attendee from a Tribe Commerce ticket ID.
	 *
	 * @test
	 */
	public function should_not_allow_creating_an_attendee_from_a_tribe_commerce_ticket_id() {
		/** @var \Tribe__Tickets__Repositories__Attendee__Commerce $attendees */
		$attendees = tribe_attendees( 'tribe-commerce' );

		$post_id = $this->factory->post->create();

		$attendee_data = [
			'full_name' => 'A test attendee',
			'email'     => 'attendee@test.com',
		];

		$ticket_id = $this->create_paypal_ticket( $post_id );

		$this->expectException( \Tribe__Repository__Usage_Error::class );

		$attendees->create_attendee_for_ticket( $attendee_data, $ticket_id );
	}

	/**
	 * It should allow creating an attendee from a Tribe Commerce ticket object.
	 *
	 * @test
	 */
	public function should_allow_creating_an_attendee_from_a_tribe_commerce_ticket_object() {
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

		// Confirm the attendee was created as intended.
		$this->assertInstanceOf( WP_Post::class, $attendee );
		$this->assertEquals( $attendee_data['order_id'] . ' | ' . $attendee_data['full_name'], $attendee->post_title );
		$this->assertEquals( $provider::ATTENDEE_OBJECT, $attendee->post_type );
		$this->assertEquals( 'publish', $attendee->post_status );
		$this->assertEquals( 0, $attendee->post_parent );

		// Confirm the attendee meta was set as intended.
		$this->assertEquals( $attendee_data['full_name'], get_post_meta( $attendee->ID, $provider->full_name, true ) );
		$this->assertEquals( $attendee_data['email'], get_post_meta( $attendee->ID, $provider->email, true ) );
		$this->assertEquals( $ticket_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_PRODUCT_KEY, true ) );
		$this->assertEquals( $post_id, get_post_meta( $attendee->ID, $provider::ATTENDEE_EVENT_KEY, true ) );
		$this->assertEquals( 'completed', get_post_meta( $attendee->ID, $provider->attendee_tpp_key, true ) );
		$this->assertEquals( '1', get_post_meta( $attendee->ID, $provider->attendee_optout_key, true ) );
		$this->assertEquals( $attendee_data['order_id'], get_post_meta( $attendee->ID, $provider->order_key, true ) );
		$this->assertNotEmpty( get_post_meta( $attendee->ID, $provider->security_code, true ) );
		$this->assertEquals( $attendee_data['user_id'], (int) get_post_meta( $attendee->ID, $provider->attendee_user_id, true ) );
	}
}
