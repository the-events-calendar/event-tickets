<?php

namespace TEC\Tickets\Commerce\Shortcodes;

use Illuminate\Support\Arr;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Manual\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Shortcode\Manager;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Template;
use Tribe__Tickets__Main;
use Tribe__Tickets__Ticket_Object;

use function Codeception\Extension\codecept_log;

class Checkout_ShortcodeTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * @before
	 */
	public function set_filters_and_singletons() {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class );

			return $modules;
		} );

		// Make sure at least one TC gateway is active.
		add_filter( 'tec_tickets_commerce_gateways', static function ( $gateways ) {
			$gateways['manual'] = new Gateway();

			return $gateways;
		} );

		// Add shortcode to filter.
		add_filter( 'tribe_shortcodes', function( $shortcodes ) {
			$shortcodes['tec_tickets_checkout'] = Checkout_Shortcode::class;
			return $shortcodes;
		} );

		$this->set_fn_return( 'wp_create_nonce', 'jhd73jd873' );

		tribe_singleton( Cart::class, new Cart() );
		tribe_singleton( Unmanaged_Cart::class, new Unmanaged_Cart() );
	}

	public function ticket_data( $tickets ) {
		global $post;
		$post = $this->factory->post->create_and_get( [
			'post_title' => 'Page with Ticket',
			'post_type'  => 'page',
		] );
		$ticket_id1 = $this->create_tc_ticket( $post->ID, 10 );
		$ticket_id2 = $this->create_tc_ticket( $post->ID, 20 );
		update_post_meta( $ticket_id2, Ticket::$sale_price_checked_key, '1');
		update_post_meta( $ticket_id2, Ticket::$sale_price_key, '10');

		yield 'ticket on sale' => [
			function() use ( $ticket_id2, $post ): array {
				return [
					[ $ticket_id2 ],
					$post->ID,
				];
			}
		];

		yield 'ticket not on sale' => [
			function() use ( $ticket_id1, $post ): array {
				return [
					[ $ticket_id1 ],
					$post->ID,
				];
			}
		];

		yield 'ticket on sale and ticket not on sale' => [
			function() use ( $ticket_id1, $ticket_id2, $post ): array {
				return [
					[ $ticket_id1, $ticket_id2 ],
					$post->ID,
				];
			}
		];
	}

	/**
	 * @dataProvider ticket_data
	 * @test
	 */
	public function it_should_match_html( \Closure $ticket_data ) {
		[ $ticket_ids, $post_id ] = $ticket_data();

		$diff = [
			$post_id
		];

		tribe( Cart::class )->clear_cart();
		foreach ( $ticket_ids as $tid ) {
			tribe( Cart::class )->add_ticket( $tid, 1 );
			$diff[] = $tid;
		}

		$shortcode_manager = new Manager();
		$shortcode_manager->add_shortcodes();

		$html = do_shortcode( '[tec_tickets_checkout]' );

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );
		$driver->setTolerableDifferences( $diff );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}