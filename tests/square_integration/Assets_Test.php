<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Common\StellarWP\Assets\Assets as Stellar_Assets;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Cart;

class Assets_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;

	protected string $controller_class = Assets::class;

	/**
	 * @after
	 */
	public function reset(): void {
		unset( $_REQUEST[ Payments_Tab::$key_current_section_get_var ] );
	}

	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ): void {
		$this->make_controller()->register();

		$this->assertTrue( Stellar_Assets::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = Stellar_Assets::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	public function asset_data_provider(): Generator {
		$assets = [
			'tec-tickets-commerce-gateway-square-admin-settings' => 'build/js/admin/gateway/square/settings.js',
			'tec-tickets-commerce-gateway-square-checkout' => 'build/js/commerce/gateway/square/checkout.js',
			'tec-tickets-commerce-square-style' => 'build/css/tickets-commerce/gateway/square.css',
			'tec-tickets-commerce-gateway-square-admin-webhooks' => 'build/js/admin/gateway/square/webhooks.js',
			'tec-tickets-commerce-admin-styles' => 'build/css/tickets-admin.css',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	/**
	 * @test
	 */
	public function it_should_get_square_checkout_data(): void {
		$this->set_fn_return( 'wp_create_nonce', '9fashjbfa64' );

		$data = wp_json_encode( $this->make_controller()->get_square_checkout_data(), JSON_SNAPSHOT_OPTIONS );

		$this->assertMatchesJsonSnapshot( $data );
	}

	/**
	 * @test
	 */
	public function it_should_correctly_determine_if_current_page_is_square_section(): void {
		$controller = $this->make_controller();
		$this->assertFalse( $controller->is_square_section() );

		$this->set_fn_return( 'is_admin', true );

		$this->assertFalse( $controller->is_square_section() );

		$_REQUEST[ Payments_Tab::$key_current_section_get_var ] = Gateway::get_key();

		$this->assertTrue( $controller->is_square_section() );
	}

	/**
	 * @test
	 */
	public function it_should_correctly_determine_if_fe_assets_should_be_enqueued(): void {
		$controller = $this->make_controller();
		$this->assertFalse( $controller->should_enqueue_assets() );

		add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_true' );

		$this->assertFalse( $controller->should_enqueue_assets() );

		$this->set_class_fn_return( Cart::class, 'get_cart_total', 10 );

		$this->assertTrue( $controller->should_enqueue_assets() );
	}
}
