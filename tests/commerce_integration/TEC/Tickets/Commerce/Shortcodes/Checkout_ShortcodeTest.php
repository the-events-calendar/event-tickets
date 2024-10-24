<?php

namespace TEC\Tickets\Commerce\Shortcodes;

use Codeception\TestCase\WPTestCase;
use Illuminate\Support\Arr;
use Spatie\Snapshots\MatchesSnapshots;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart;
use TEC\Tickets\Commerce\Gateways\Manual\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Shortcode\Manager;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Checkout_ShortcodeTest extends WPTestCase {
	use MatchesSnapshots;
	use Ticket_Maker;
	use With_Uopz;
	use With_Tickets_Commerce;

	/**
	 * @var int
	 */
	public $page_id;

	/**
	 * @var int
	 */
	public $ticket_id1;

	/**
	 * @var int
	 */
	public $ticket_id2;

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
		tribe_update_option( 'tickets-commerce-currency-code', 'USD' );
		tribe_update_option( 'tickets-commerce-currency-decimal-separator', '.' );
		tribe_update_option( 'tickets-commerce-currency-thousands-separator', ',' );
		tribe_update_option( 'tickets-commerce-currency-number-of-decimals', '2' );
		tribe_update_option( 'tickets-commerce-currency-position', 'prefix' );
	}

	/**
	 * @before
	 */
	public function create_event_and_tickets() {
		$this->page_id = static::factory()->post->create( [
			'post_title' => 'Page with Tickets',
			'post_type'  => 'page',
		] );
		$this->ticket_id1 = $this->create_tc_ticket( $this->page_id, 10 );
		$this->ticket_id2 = $this->create_tc_ticket( $this->page_id, 20 );
		update_post_meta( $this->ticket_id2, Ticket::$sale_price_checked_key, '1');
		update_post_meta( $this->ticket_id2, Ticket::$sale_price_key, '10');
	}

	/**
	 * @test
	 */
	public function ticket_on_sale_should_match_html() {
		tribe( Cart::class )->clear_cart();
		tribe( Cart::class )->add_ticket( $this->ticket_id2, 1 );

		$shortcode_manager = new Manager();
		$shortcode_manager->add_shortcodes();

		$html = do_shortcode( '[tec_tickets_checkout]' );

		$html = str_replace(
			[
				$this->page_id,
				$this->ticket_id1,
				$this->ticket_id2,
				'wp-content/plugins/the-events-calendar/common',
			],
			[
				'{{page_id}}',
				'{{ticket_id1}}',
				'{{ticket_id2}}',
				'wp-content/plugins/event-tickets/common',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function ticket_not_on_sale_should_match_html() {
		tribe( Cart::class )->clear_cart();
		tribe( Cart::class )->add_ticket( $this->ticket_id1, 1 );

		$shortcode_manager = new Manager();
		$shortcode_manager->add_shortcodes();

		$html = do_shortcode( '[tec_tickets_checkout]' );

		$html = str_replace(
			[
				$this->page_id,
				$this->ticket_id1,
				'wp-content/plugins/the-events-calendar/common',
			],
			[
				'{{page_id}}',
				'{{ticket_id1}}',
				'wp-content/plugins/event-tickets/common',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html );
	}
}