<?php

namespace TEC\Tickets\Seating\Commerce;

use Closure;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Meta;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

class Controller_Test extends Controller_Test_Case {
	use Ticket_Maker;

	protected string $controller_class = Controller::class;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function filter_timer_token_object_id_entries_data_provider(): \Generator {
		yield 'no entries' => [
			function (): array {
				return [
					[],
					[],
				];
			}
		];

		yield 'not on checkout page' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_false' );

				return [
					[ $post_id => 'test-token' ],
					[ $post_id => 'test-token' ],
				];
			}
		];

		yield 'on checkout page but no ASC post in cart' => [
			function (): array {
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_true' );
				$no_asc_post_id = static::factory()->post->create();
				$ticket_id      = $this->create_tc_ticket( $no_asc_post_id, 10 );
				/** @var Cart $cart */
				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id, 1 );
				$asc_post_id = static::factory()->post->create();
				update_post_meta( $asc_post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
				$asc_ticket_id = $this->create_tc_ticket( $asc_post_id, 10 );
				$this->create_tc_ticket( $asc_post_id, 10 );

				return [
					[ $asc_post_id => 'test-token' ],
					[],
				];
			}
		];

		yield 'on checkout page with ASC post in cart' => [
			function (): array {
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_true' );
				$no_asc_post_id   = static::factory()->post->create();
				$no_asc_ticket_id = $this->create_tc_ticket( $no_asc_post_id, 10 );
				/** @var Cart $cart */
				$cart = tribe( Cart::class );
				$cart->add_ticket( $no_asc_ticket_id, 1 );
				$asc_post_id = static::factory()->post->create();
				update_post_meta( $asc_post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
				$asc_ticket_id = $this->create_tc_ticket( $asc_post_id, 10 );
				$cart->add_ticket( $asc_ticket_id, 1 );

				return [
					[ $asc_post_id => 'test-token' ],
					[ $asc_post_id => 'test-token' ],
				];
			}
		];
	}

	/**
	 * @dataProvider filter_timer_token_object_id_entries_data_provider
	 * @return void
	 */
	public function test_filter_timer_token_object_id_entries( Closure $fixture ): void {
		[ $input_entries, $expected_entries ] = $fixture();

		$controller = $this->make_controller();
		$controller->register();

		$filtered_entries = apply_filters( 'tec_tickets_seating_timer_token_object_id_entries', $input_entries );

		$this->assertEquals(
			$expected_entries,
			$filtered_entries,
		);
	}
}
