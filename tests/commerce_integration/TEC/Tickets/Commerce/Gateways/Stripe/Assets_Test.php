<?php
namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Common\StellarWP\Assets\Assets as Stellar_Assets;

class Assets_Test extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;
	
	/**
	 * @test
	 */
	public function it_should_register_admin_assets_properly(): void {
		$this->assertTrue( Stellar_Assets::init()->exists( 'tec-tickets-commerce-gateway-stripe-admin-webhooks' ) );
		$this->assertTrue( Stellar_Assets::init()->exists( 'tec-tickets-commerce-gateway-stripe-admin-webhooks-styles' ) );
		
		$this->assertTrue( wp_script_is( 'tec-tickets-commerce-gateway-stripe-admin-webhooks', 'registered' ) );
		$this->assertTrue( wp_style_is( 'tec-tickets-commerce-gateway-stripe-admin-webhooks-styles', 'registered' ) );
		
		$this->assertMatchesJsonSnapshot(
			wp_json_encode(
				[
					'js'  => Stellar_Assets::init()->get( 'tec-tickets-commerce-gateway-stripe-admin-webhooks' )->get_url(),
					'css' => Stellar_Assets::init()->get( 'tec-tickets-commerce-gateway-stripe-admin-webhooks-styles' )->get_url(),
				],
				JSON_SNAPSHOT_OPTIONS
			)
		);
	}
}
