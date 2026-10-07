<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__RSVP as Other_Provider;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * An event keeps the ticket provider it was given, whichever providers are active alongside Tickets Commerce.
 *
 * `Tribe__Tickets__RSVP` stands in for any non-Tickets-Commerce provider (e.g. WooCommerce from ETP): the
 * handler and `get_event_ticket_provider_object()` only deal in provider class names.
 */
class Tickets_Handler_Provider_Test extends \Codeception\TestCase\WPTestCase {
	/**
	 * @before
	 */
	public function activate_tickets_commerce_and_another_provider(): void {
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter(
			'tribe_tickets_get_modules',
			static function ( array $modules ): array {
				$modules[ Module::class ]         = tribe( Module::class )->plugin_name;
				$modules[ Other_Provider::class ] = 'Other provider';

				return $modules;
			}
		);
	}

	private function get_handler(): Tickets_Handler {
		return tribe( 'tickets.handler' );
	}

	public function test_saving_settings_without_default_provider_keeps_the_stored_provider(): void {
		$event_id = static::factory()->post->create();
		$handler  = $this->get_handler();
		update_post_meta( $event_id, $handler->key_provider_field, Other_Provider::class );

		// A locked (disabled) radio is not serialized by the panel, so the field is absent from the payload.
		$handler->save_form_settings( $event_id, [ 'event_capacity' => 10 ] );

		$this->assertSame( Other_Provider::class, get_post_meta( $event_id, $handler->key_provider_field, true ) );
	}

	public function test_saving_settings_with_default_provider_updates_the_stored_provider(): void {
		$event_id = static::factory()->post->create();
		$handler  = $this->get_handler();
		update_post_meta( $event_id, $handler->key_provider_field, Module::class );

		$handler->save_form_settings( $event_id, [ 'default_provider' => Other_Provider::class ] );

		$this->assertSame( Other_Provider::class, get_post_meta( $event_id, $handler->key_provider_field, true ) );
	}

	public function test_event_provider_stays_the_stored_one_after_saving_settings_with_commerce_enabled(): void {
		$event_id = static::factory()->post->create();
		$handler  = $this->get_handler();
		update_post_meta( $event_id, $handler->key_provider_field, Other_Provider::class );

		$handler->save_form_settings( $event_id, [ 'event_capacity' => 10 ] );

		$this->assertSame( Other_Provider::class, Tickets::get_event_ticket_provider( $event_id ) );
	}
}
