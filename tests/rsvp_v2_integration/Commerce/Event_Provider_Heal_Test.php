<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Events wrongly stored as Tickets Commerce, or storing no provider, heal to the provider that owns their tickets.
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

	/**
	 * The ticket makers store their own provider on the event, so call this after creating tickets to leave the event with none.
	 */
	private function clear_stored_provider( int $post_id ): void {
		delete_post_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field );
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

	/**
	 * The object cache keeps a kept Tickets Commerce from being checked again until the next post save.
	 */
	public function test_keeps_tickets_commerce_until_the_next_post_save(): void {
		$post_id      = $this->make_event();
		$tc_ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$this->create_paypal_ticket( $post_id, 10 );

		$this->store_tickets_commerce( $post_id );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );

		// Detaching the TC ticket through its meta is not a post save.
		delete_post_meta( $tc_ticket_id, Ticket::$event_relation_meta_key );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );

		wp_update_post( [ 'ID' => $post_id ] );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );
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

	/**
	 * The other paid provider is the site default, so without the heal these tickets would be hidden on the frontend.
	 */
	public function test_unstored_event_heals_to_tickets_commerce_when_it_owns_the_tickets(): void {
		$post_id      = $this->make_event();
		$tc_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$this->clear_stored_provider( $post_id );

		$this->assertSame( self::PAYPAL, Tickets::get_default_module() );
		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( Module::class, $this->stored_provider( $post_id ) );
		$this->assertSame( [ $tc_ticket_id ], wp_list_pluck( tribe( Module::class )->get_tickets( $post_id ), 'ID' ) );

		// The next request reads the stored value: the namespaced class must have survived the save.
		wp_cache_flush();

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
	}

	/**
	 * Tickets Commerce's meta sanitizer repairs only its own class, so the stored value must not depend on it.
	 */
	public function test_unstored_event_stores_the_namespaced_provider_without_the_sanitizer(): void {
		$post_id = $this->make_event();
		$this->create_tc_ticket( $post_id, 10 );

		$this->clear_stored_provider( $post_id );
		remove_all_filters( 'sanitize_post_meta_' . tribe( 'tickets.handler' )->key_provider_field );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( Module::class, $this->stored_provider( $post_id ) );
	}

	public function test_unstored_event_heals_to_the_other_provider_that_owns_the_tickets(): void {
		add_filter( 'tribe_tickets_get_default_module', fn() => Module::class, PHP_INT_MAX );

		$post_id = $this->make_event();
		$this->create_paypal_ticket( $post_id, 10 );
		$this->create_tc_rsvp_ticket( $post_id );

		$this->clear_stored_provider( $post_id );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( self::PAYPAL, $this->stored_provider( $post_id ) );
	}

	public function test_unstored_event_with_tickets_of_the_default_provider_uses_it(): void {
		$post_id = $this->make_event();
		$this->create_paypal_ticket( $post_id, 10 );

		$this->clear_stored_provider( $post_id );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( '', $this->stored_provider( $post_id ) );
	}

	public function test_unstored_event_without_tickets_uses_the_default(): void {
		$post_id = $this->make_event();

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( '', $this->stored_provider( $post_id ) );
	}

	public function test_unstored_event_with_only_an_rsvp_uses_the_default(): void {
		$post_id = $this->make_event();
		$this->create_tc_rsvp_ticket( $post_id );

		$this->clear_stored_provider( $post_id );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( '', $this->stored_provider( $post_id ) );
	}

	/**
	 * Before 5.30.3 such an event resolved to Tickets Commerce, so its Tickets Commerce tickets were the ones shown.
	 */
	public function test_unstored_event_with_tickets_from_two_providers_heals_to_tickets_commerce(): void {
		$post_id      = $this->make_event();
		$tc_ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$this->create_paypal_ticket( $post_id, 10 );

		$this->clear_stored_provider( $post_id );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( Module::class, $this->stored_provider( $post_id ) );
		$this->assertSame( [ $tc_ticket_id ], wp_list_pluck( tribe( Module::class )->get_tickets( $post_id ), 'ID' ) );
	}

	/**
	 * The object cache keeps an event left on the default from being checked again until the next post save.
	 */
	public function test_unstored_event_keeps_the_default_until_the_next_post_save(): void {
		$post_id      = $this->make_event();
		$tc_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Re-attaching the TC ticket through its meta below is not a post save; creating one would be.
		delete_post_meta( $tc_ticket_id, Ticket::$event_relation_meta_key );
		$this->clear_stored_provider( $post_id );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );

		update_post_meta( $tc_ticket_id, Ticket::$event_relation_meta_key, $post_id );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );

		wp_update_post( [ 'ID' => $post_id ] );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( [ $tc_ticket_id ], wp_list_pluck( tribe( Module::class )->get_tickets( $post_id ), 'ID' ) );
	}

	/**
	 * Most events storing no provider have no tickets, and they are looked up on frontend requests.
	 */
	public function test_unstored_event_left_on_the_default_writes_no_option(): void {
		$post_id = $this->make_event();
		$writes  = did_action( 'added_option' ) + did_action( 'updated_option' );

		$this->assertSame( self::PAYPAL, Tickets::get_event_ticket_provider( $post_id ) );
		$this->assertSame( $writes, did_action( 'added_option' ) + did_action( 'updated_option' ) );
	}

	public function test_unstored_event_uses_tickets_commerce_when_it_is_the_only_paid_provider(): void {
		remove_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		remove_filter( 'tribe_tickets_get_modules', $this->add_paypal_module );

		$post_id = $this->make_event();
		$this->create_tc_ticket( $post_id, 10 );

		$this->clear_stored_provider( $post_id );

		$this->assertSame( Module::class, Tickets::get_event_ticket_provider( $post_id ) );
	}
}
