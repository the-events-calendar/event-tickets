<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Events wrongly stored as Tickets Commerce heal to the provider that owns their tickets.
 *
 * Legacy PayPal stands in for WooCommerce: it is the only other paid provider ET's suites can load.
 */
class Event_Provider_Heal_Test extends WPTestCase {
	use Ticket_Maker;
	use PayPal_Ticket_Maker;

	private const PAYPAL = 'Tribe__Tickets__Commerce__PayPal__Main';

	/**
	 * @var callable
	 */
	private $add_paypal_module;

	public function setUp(): void {
		parent::setUp();

		$this->add_paypal_module = function ( array $modules ): array {
			$modules[ self::PAYPAL ] = tribe( $this->get_paypal_ticket_provider() )->plugin_name;

			return $modules;
		};
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', $this->add_paypal_module );
	}

	private function make_event(): int {
		return static::factory()->post->create( [ 'post_status' => 'publish' ] );
	}

	/**
	 * Stores Tickets Commerce last: the ticket makers store their own provider on the event.
	 */
	private function store_tickets_commerce( int $post_id ): void {
		update_post_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field, Module::class );
	}

	private function stored_provider( int $post_id ): string {
		return get_post_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field, true );
	}

	public function test_heals_to_the_provider_that_owns_the_tickets(): void {
		$post_id = $this->make_event();
		$this->create_paypal_ticket( $post_id, 10 );
		$this->create_tc_rsvp_ticket( $post_id );

		$this->store_tickets_commerce( $post_id );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( self::PAYPAL, $this->stored_provider( $post_id ) );
	}

	public function test_keeps_tickets_commerce_when_it_has_tickets(): void {
		$post_id = $this->make_event();
		$this->create_tc_ticket( $post_id, 10 );
		$this->create_paypal_ticket( $post_id, 10 );

		$this->store_tickets_commerce( $post_id );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( Module::class, $this->stored_provider( $post_id ) );
	}

	public function test_keeps_tickets_commerce_without_tickets(): void {
		$post_id = $this->make_event();

		$this->store_tickets_commerce( $post_id );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( Module::class, $this->stored_provider( $post_id ) );
	}

	public function test_keeps_tickets_commerce_with_only_an_rsvp(): void {
		$post_id = $this->make_event();
		$this->create_tc_rsvp_ticket( $post_id );

		$this->store_tickets_commerce( $post_id );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( Module::class, $this->stored_provider( $post_id ) );
	}

	public function test_keeps_tickets_commerce_when_the_owning_provider_is_inactive(): void {
		$post_id = $this->make_event();
		$this->create_paypal_ticket( $post_id, 10 );
		remove_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		remove_filter( 'tribe_tickets_get_modules', $this->add_paypal_module );

		$this->store_tickets_commerce( $post_id );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( Module::class, $this->stored_provider( $post_id ) );
	}
}
