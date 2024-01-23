<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
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

		// Enable Tribe Commerce (PayPal).
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		// Ensure PayPal is the only commerce provider active.
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			unset( $modules[ Module::class ] );
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

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
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

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
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

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id, [ 'user_id' => $user_id ] );
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id, [ 'user_id' => $user_id ] );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
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

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 4, $paypal_ticket_id, $post_id );
		$this->create_many_attendees_for_ticket( 6, $rsvp_ticket_id, $post_id );

		$this->assertEquals( 4, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider' => 'tribe-commerce' ] ] ) );
		$this->assertEquals( 6, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider' => 'rsvp' ] ] ) );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
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

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 4, $paypal_ticket_id, $post_id );
		$this->create_many_attendees_for_ticket( 6, $rsvp_ticket_id, $post_id );

		$this->assertEquals( 4, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider__not_in' => 'rsvp' ] ] ) );
		$this->assertEquals( 6, Tickets::get_event_attendees_count( $post_id, [ 'by' => [ 'provider__not_in' => 'tribe-commerce' ] ] ) );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$this->create_many_attendees_for_ticket( 6, $paypal_ticket_id2, $post_id2 );
		$this->create_many_attendees_for_ticket( 4, $rsvp_ticket_id2, $post_id2 );

		$this->assertEquals( 6, Tickets::get_event_attendees_count( $post_id2, [ 'by' => [ 'provider__not_in' => 'rsvp' ] ] ) );
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

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1 );
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

	/**
	 * It should allow getting availability slug by collection.
	 *
	 * @test
	 */
	public function should_allow_getting_availability_slug_by_collection() {
		$this->markTestIncomplete( 'This test was never written' );
	}

	/**
	 * It should allow getting the default ticket provider for a Tribe Commerce post.
	 *
	 * @test
	 */
	public function should_allow_getting_default_ticket_provider_for_tribe_commerce_post() {
		$post_id          = $this->factory->post->create();
		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );

		$this->assertEquals( 'Tribe__Tickets__Commerce__PayPal__Main', Tickets::get_event_ticket_provider( $post_id ) );
	}

	/**
	 * It should allow getting the ticket provider for a Tribe Commerce post with default provider.
	 *
	 * @test
	 */
	public function should_allow_getting_ticket_provider_for_tribe_commerce_post_with_default_provider() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id          = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets__Commerce__PayPal__Main',
			],
		] );
		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );

		$this->assertEquals( 'Tribe__Tickets__Commerce__PayPal__Main', Tickets::get_event_ticket_provider( $post_id ) );
	}

	/**
	 * It should allow getting the default ticket provider for a RSVP post with no default.
	 *
	 * @test
	 */
	public function should_allow_getting_default_ticket_provider_for_rsvp_post_with_no_default() {
		$post_id        = $this->factory->post->create();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals( 'Tribe__Tickets__Commerce__PayPal__Main', Tickets::get_event_ticket_provider( $post_id ) );
	}

	/**
	 * It should allow getting the ticket provider for a RSVP post.
	 *
	 * @test
	 */
	public function should_allow_getting_ticket_provider_for_rsvp_post() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id        = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets__RSVP',
			],
		] );
		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals( 'Tribe__Tickets__RSVP', Tickets::get_event_ticket_provider( $post_id ) );
	}

	/**
	 * It should allow getting the ticket provider for a post.
	 *
	 * @test
	 */
	public function should_allow_getting_ticket_provider_for_post_with_inactive_provider() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
			],
		] );

		$this->assertEquals( false, Tickets::get_event_ticket_provider( $post_id ) );
	}

	/**
	 * It should allow getting the default ticket provider for a Tribe Commerce post.
	 *
	 * @test
	 */
	public function should_allow_getting_default_ticket_provider_object_for_tribe_commerce_post() {
		$post_id          = $this->factory->post->create();
		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );

		$this->assertInstanceOf( 'Tribe__Tickets__Commerce__PayPal__Main', Tickets::get_event_ticket_provider_object( $post_id ) );
	}

	/**
	 * It should allow getting the ticket provider for a Tribe Commerce post with default provider.
	 *
	 * @test
	 */
	public function should_allow_getting_ticket_provider_object_for_tribe_commerce_post_with_default_provider() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id          = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets__Commerce__PayPal__Main',
			],
		] );
		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );

		$this->assertInstanceOf( 'Tribe__Tickets__Commerce__PayPal__Main', Tickets::get_event_ticket_provider_object( $post_id ) );
	}

	/**
	 * It should allow getting the default ticket provider for a RSVP post with no default.
	 *
	 * @test
	 */
	public function should_allow_getting_default_ticket_provider_object_for_rsvp_post_with_no_default() {
		$post_id        = $this->factory->post->create();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertInstanceOf( 'Tribe__Tickets__Commerce__PayPal__Main', Tickets::get_event_ticket_provider_object( $post_id ) );
	}

	/**
	 * It should allow getting the ticket provider for a RSVP post.
	 *
	 * @test
	 */
	public function should_allow_getting_ticket_provider_object_for_rsvp_post() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id        = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets__RSVP',
			],
		] );
		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertInstanceOf( 'Tribe__Tickets__RSVP', Tickets::get_event_ticket_provider_object( $post_id ) );
	}

	/**
	 * It should not get the ticket provider for a post with inactive provider.
	 *
	 * @test
	 */
	public function should_not_get_ticket_provider_object_for_post_with_inactive_provider() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
			],
		] );

		$this->assertEquals( false, Tickets::get_event_ticket_provider_object( $post_id ) );
	}

	/**
	 * It should get empty list of active providers for a post with tickets that have an inactive provider.
	 *
	 * @test
	 */
	public function should_get_empty_list_of_active_providers_for_a_post_with_tickets_that_have_an_inactive_provider() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
			],
		] );

		$this->assertEquals( [], Tickets::get_active_providers_for_post( $post_id ) );
	}

	/**
	 * It should get the list of active providers for a post with tickets that have an inactive provider.
	 *
	 * @test
	 */
	public function should_get_the_list_of_active_providers_for_a_post_with_tickets_that_have_an_inactive_provider() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
			],
		] );

		$this->create_rsvp_ticket( $post_id );
		$this->create_paypal_ticket( $post_id, 1 );

		$this->assertEquals( [
			'Tribe__Tickets__RSVP' => 'Tribe__Tickets__RSVP',
			'Tribe__Tickets__Commerce__PayPal__Main' => 'Tribe__Tickets__Commerce__PayPal__Main',
		], Tickets::get_active_providers_for_post( $post_id ) );
	}

	/**
	 * It should get the list of active provider objects for a post with tickets that have an inactive provider.
	 *
	 * @test
	 */
	public function should_get_the_list_of_active_provider_objects_for_a_post_with_tickets_that_have_an_inactive_provider() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$post_id = $this->factory->post->create( [
			'meta_input' => [
				$tickets_handler->key_provider_field => 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
			],
		] );

		$this->create_rsvp_ticket( $post_id );
		$this->create_paypal_ticket( $post_id, 1 );

		$active_providers = Tickets::get_active_providers_for_post( $post_id, true );
		$active_provider_keys = array_keys( $active_providers );

		$this->assertEquals( [
			'Tribe__Tickets__RSVP',
			'Tribe__Tickets__Commerce__PayPal__Main',
		], $active_provider_keys );
		$this->assertInstanceOf( 'Tribe__Tickets__RSVP', $active_providers['Tribe__Tickets__RSVP'] );
		$this->assertInstanceOf( 'Tribe__Tickets__Commerce__PayPal__Main', $active_providers['Tribe__Tickets__Commerce__PayPal__Main'] );
	}

	/**
	 * It should set context correctly when getting tickets
	 *
	 * Using Tickets Commerce for the test, but this is a generic test for all providers.
	 *
	 * @test
	 */
	public function should_set_context_correctly_when_getting_tickets(): void {
		$post_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		$tickets = call_user_func( [ Tickets::get_event_ticket_provider($post_id), 'get_instance' ] );

		$request_context = '';
		add_filter( 'tribe_repository_tickets_query_args', function ( $query_args, $query, $repository ) use ( &$request_context ) {
			$request_context = $repository->get_request_context();

			return $query_args;
		}, 10, 3 );

		$tickets->get_tickets( $post_id );

		$this->assertNull( $request_context );

		// Run another query, this time setting the context.
		$tickets->get_tickets( $post_id, 'some-context' );

		$this->assertEquals( 'some-context', $request_context );
	}
}
