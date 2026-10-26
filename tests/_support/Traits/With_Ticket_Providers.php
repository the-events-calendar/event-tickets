<?php

namespace Tribe\Tickets\Test\Traits;

use Faker\Factory;
use Faker\Generator as Faker;
use Generator;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker as TC_RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__RSVP as RSVP;

/**
 * Activates Tickets Commerce, PayPal and RSVP on posts and pages, and names the method that builds a ticket of each.
 */
trait With_Ticket_Providers {
	use PayPal_Ticket_Maker;
	use RSVP_Ticket_Maker;
	use TC_RSVP_Ticket_Maker;

	protected Faker $faker;

	private array $ticketable_post_types = [];

	/**
	 * @before
	 */
	public function enable_providers(): void {
		$this->faker                 = Factory::create();
		$this->ticketable_post_types = tribe_get_option( 'ticket-enabled-post-types', [] );
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( array_merge( $this->ticketable_post_types, [ 'post', 'page' ] ) ) ) );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', [ self::class, 'add_modules' ] );
		tribe_singleton( 'tickets.data_api', new Data_API() );
	}

	/**
	 * @after
	 */
	public function reset_providers(): void {
		tribe_update_option( 'ticket-enabled-post-types', $this->ticketable_post_types );
		remove_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		remove_filter( 'tribe_tickets_get_modules', [ self::class, 'add_modules' ] );
		tribe_singleton( 'tickets.data_api', new Data_API() );
	}

	public static function add_modules( array $modules ): array {
		$modules[ Commerce::class ] = 'Tickets Commerce';
		$modules[ PayPal::class ]   = tribe( 'tickets.commerce.paypal' )->plugin_name;

		return $modules;
	}

	/**
	 * @return Generator<string,array{0: class-string, 1: string}> The provider and the method building one of its paid tickets.
	 */
	public function provider_paid_ticket_makers(): Generator {
		yield 'Tickets Commerce' => [ Commerce::class, 'create_tc_ticket' ];
		yield 'PayPal' => [ PayPal::class, 'create_paypal_ticket' ];
	}

	/**
	 * @return Generator<string,array{0: class-string, 1: string}> The provider and the method building one of its tickets.
	 */
	public function provider_ticket_makers(): Generator {
		yield from $this->provider_paid_ticket_makers();
		yield 'Tickets Commerce RSVP' => [ Commerce::class, 'create_tc_rsvp_ticket' ];
		yield 'RSVP' => [ RSVP::class, 'create_rsvp_ticket' ];
	}

	private function log_in_as_contributor_with_own_draft(): int {
		$contributor = static::factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $contributor );

		return static::factory()->post->create(
			[
				'post_author' => $contributor,
				'post_status' => 'draft',
			]
		);
	}

	private function log_in_as_editor_of_another_authors_post(): int {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );

		return static::factory()->post->create( [ 'post_author' => static::factory()->user->create( [ 'role' => 'author' ] ) ] );
	}
}
