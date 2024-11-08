<?php

namespace TEC\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Common\StellarWP\Assets\Assets as AssetsLibrary;
use TEC\Tickets\Commerce\Module;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Data_API as Data_API;

class Assets_Test extends Controller_Test_Case {
	use With_Uopz;
	use SnapshotAssertions;

	protected string $controller_class = Assets::class;

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

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-seating-service-bundle' => '/build/Seating/service.js',
			'tec-tickets-seating-utils'          => '/build/Seating/utils.js',
			'tec-tickets-seating-currency'       => '/build/Seating/currency.js',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ) {
		$this->make_controller()->register();

		$this->assertTrue( AssetsLibrary::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = AssetsLibrary::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	public function test_get_utils_data(): void {
		$this->set_fn_return( 'wp_create_nonce', '8298ff6616' );

		$controller = $this->make_controller();

		$this->assertMatchesJsonSnapshot( wp_json_encode( $controller->get_utils_data(), JSON_SNAPSHOT_OPTIONS ) );
	}

	public function test_get_currency_data(): void {
		$post = self::factory()->post->create_and_get();;
		$GLOBALS['post'] = $post;

		$controller = $this->make_controller();

		$json = str_replace(
			$post->ID,
			'{{post_id}}',
			wp_json_encode( $controller->get_currency_data(), JSON_SNAPSHOT_OPTIONS )
		);
		$this->assertMatchesJsonSnapshot( $json );
	}

	public function test_service_bundle_data(): void {
		$controller = $this->make_controller();

		$json = wp_json_encode( $controller->get_service_bundle_data(), JSON_SNAPSHOT_OPTIONS );
		$this->assertMatchesJsonSnapshot( $json );
	}
}
