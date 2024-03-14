<?php

namespace TEC\Tickets\Commerce\Shortcodes;

use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Manual\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Template;
use Tribe__Tickets__Main;

use function Codeception\Extension\codecept_log;

class Checkout_ShortcodeTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;
	use Ticket_Maker;
	use With_Uopz;

	public function setUp() {
		parent::setUp();

		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		$this->set_fn_return( 'wp_create_nonce', 'a1b2c3d4e5f6' );
	}

	/**
	 * @dataProvider cart_data
	 * @test
	 */
	public function test_matches_snapshots( \Closure $ticket_data_provider ) {
		[ $items, $post, $ticket ] = $ticket_data_provider();

		$sections    = array_unique( array_filter( wp_list_pluck( $items, 'event_id' ) ) );
		$sub_totals  = Value::build_list( array_filter( wp_list_pluck( $items, 'sub_total' ) ) );
		$total_value = Value::create();

		$gateways = [
			'manual' => new Gateway(),
		];

		$args = [
			'provider_id'        => Module::class,
			'provider'           => tribe( Module::class ),
			'items'              => $items,
			'sections'           => $sections,
			'total_value'        => $total_value->total( $sub_totals ),
			'must_login'         => ! is_user_logged_in() && tribe( Module::class )->login_required(),
			'login_url'          => tribe( Checkout::class )->get_login_url(),
			'registration_url'   => tribe( Checkout::class )->get_registration_url(),
			'is_tec_active'      => defined( 'TRIBE_EVENTS_FILE' ) && class_exists( 'Tribe__Events__Main' ),
			'gateways'           => $gateways,
			'gateways_active'    => 1,
			'gateways_connected' => 1,
		];

		$template = new Tribe__Template();
		$template->set_template_origin( Tribe__Tickets__Main::instance() );
		$template->set_template_folder( 'src/views/v2/commerce' );
		$template->set_template_context_extract( true );
		$template->set_template_folder_lookup( true );
		$html = $template->template( 'checkout', $args, false );
		wp_delete_post( $items[0]['ticket_id'], true );
		wp_delete_post( $items[0]['event_id'], true );

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );
		$driver->setTolerableDifferences( [
			$items[0]['ticket_id'],
			$items[0]['event_id'],
			$post->post_title,
			$ticket->name,
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @dataProvider test_matches_snapshots
	 */
	public function cart_data() {
		global $post;

		yield 'ticket on sale' => [
			function(): array {
				global $post;
				$post = $this->factory->post->create_and_get( [
					'post_title' => 'Post with Ticket',
				] );
				$ticket_id = $this->create_tc_ticket( $post->ID, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, '1');
				update_post_meta( $ticket_id, Ticket::$sale_price_key, '5');
				$ticket = tribe( Module::class )->get_ticket( $post->ID, $ticket_id );
				$sub_total_value = Value::create();
				$sub_total_value->set_value($ticket->price );
				$items = [
					[
						'ticket_id'     => $ticket_id,
						'event_id'      => $post->ID,
						'quantity'      => 2,
						'obj'           => $ticket,
						'sub_total'     => $sub_total_value->sub_total( 2 ),
						'regular_price' => Value::create()->set_value( 10 ),
					]
				];
				return [
					$items,
					$post,
					$ticket,
				];
			}
		];

		yield 'ticket not on sale' => [
			function(): array {
				global $post;
				$post = $this->factory->post->create_and_get( [
					'post_title' => 'Post with Ticket',
				] );
				$ticket_id = $this->create_tc_ticket( $post->ID, 10 );
				$ticket = tribe( Module::class )->get_ticket( $post->ID, $ticket_id );
				$sub_total_value = Value::create();
				$sub_total_value->set_value($ticket->price );
				$items = [
					[
						'ticket_id'     => $ticket_id,
						'event_id'      => $post->ID,
						'quantity'      => 2,
						'obj'           => $ticket,
						'sub_total'     => $sub_total_value->sub_total( 2 ),
						'regular_price' => Value::create()->set_value( 10 ),
					]
				];
				return [
					$items,
					$post,
					$ticket,
				];
			}
		];
	}
}