<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Common\StellarWP\Assets\Assets;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Admin\Pages;
use Closure;
use Generator;

class Modifier_Admin_Handler_Test extends Controller_Test_Case {
	use With_Uopz;

	protected string $controller_class = Modifier_Admin_Handler::class;

	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ) {
		$this->make_controller()->register();

		$this->assertTrue( Assets::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = Assets::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-order-modifiers-table' => 'src/resources/js/admin/order-modifiers/table.js',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	public function should_enqueue_assets_data_provider(): Generator {
		yield 'On the incorrect page' => [
			function (): bool {
				$this->set_class_fn_return( Pages::class, 'get_current_page', 'invalid-slug' );

				return false;
			},
		];
		yield 'On the correct page' => [
			function (): bool {
				$this->set_class_fn_return( Pages::class, 'get_current_page', 'tec-tickets-order-modifiers' );

				return true;
			},
		];
	}

	/**
	 * @dataProvider should_enqueue_assets_data_provider
	 */
	public function test_should_enqueue_assets( Closure $fixture ): void {
		$should_enqueue_assets = $fixture();

		$controller = $this->make_controller();

		$this->assertEquals( $should_enqueue_assets, $controller->is_on_page() );
	}
}
