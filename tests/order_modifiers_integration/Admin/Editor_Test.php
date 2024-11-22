<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\Admin\Editor;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Common\StellarWP\Assets\Assets;
use Closure;
use Generator;

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

	public function should_enqueue_block_editor_assets_data_provider(): Generator {
		yield 'Assets should not enqueue on incorrect action' => [
			function (): bool {
				// Simulate an action where the assets should NOT enqueue.
				do_action( 'some_other_action' );

				return false;
			},
		];

		yield 'Assets should enqueue on block editor action' => [
			function (): bool {
				// Simulate the block editor action.
				do_action( 'enqueue_block_editor_assets' );

				return true;
			},
		];
	}

	/**
	 * @dataProvider should_enqueue_block_editor_assets_data_provider
	 */
	public function test_should_enqueue_block_editor_assets( Closure $fixture ): void {
		$this->make_controller()->register();
		// Setup: Mock WordPress enqueue functions to capture enqueued scripts.
		$enqueued_assets = [];
		$this->set_fn_return(
			'wp_enqueue_script',
			function ( $handle ) use ( &$enqueued_assets ) {
				$enqueued_assets[] = $handle;
			},
			true
		);

		// Execute the fixture to simulate the action.
		$should_enqueue_assets = $fixture();

		// Assert whether the asset is enqueued based on the condition.
		if ( $should_enqueue_assets ) {
			$this->assertContains(
				'tec-tickets-order-modifiers-block-editor',
				$enqueued_assets,
				'The block editor asset should be enqueued.'
			);
		} else {
			$this->assertNotContains(
				'tec-tickets-order-modifiers-block-editor',
				$enqueued_assets,
				'The block editor asset should not be enqueued.'
			);
		}
	}
}
