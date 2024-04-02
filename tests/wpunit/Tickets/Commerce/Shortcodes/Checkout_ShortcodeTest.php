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
	 * @var WP_Post
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
	}

	/**
	 * @before
	 */
	public function create_event_and_tickets() {
		$this->page_id = $this->factory->post->create( [
			'post_title' => 'Page with Tickets',
			'post_type'  => 'page',
		] );
		$this->ticket_id1 = $this->create_tc_ticket( $this->page_id, 10 );
		$this->ticket_id2 = $this->create_tc_ticket( $this->page_id, 20 );
		update_post_meta( $this->ticket_id2, Ticket::$sale_price_checked_key, '1');
		update_post_meta( $this->ticket_id2, Ticket::$sale_price_key, '10');
	}

	/**
	 * @after
	 */
	public function remove_event_and_tickets() {
		wp_delete_post( $this->page_id, true );
		wp_delete_post( $this->ticket_id1, true );
		wp_delete_post( $this->ticket_id2, true );
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

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );
		$driver->setTolerableDifferences( [
			$this->page_id,
			$this->ticket_id2,
		] );

		$this->assertMatchesSnapshot( $html, $driver );
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

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );
		$driver->setTolerableDifferences( [
			$this->page_id,
			$this->ticket_id1,
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}