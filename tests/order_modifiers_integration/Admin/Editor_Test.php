<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\Admin\Editor;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Common\StellarWP\Assets\Assets;

class Editor_Test extends Controller_Test_Case {
	use With_Uopz;

	protected string $controller_class = Editor::class;

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
			'tec-tickets-order-modifiers-block-editor' => 'build/OrderModifiers/block-editor.js',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}
}
