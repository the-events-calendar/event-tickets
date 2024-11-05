<?php

namespace TEC\Tickets\Seating\Service;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use function TEC\Common\StellarWP\Uplink\get_resource;

class Service_Status_Test extends WPTestCase {
	use With_Uopz;
	use WP_Remote_Mocks;

	protected function setUp() {
		parent::setUp();
		test_remove_seating_license_key_callback();
		test_remove_service_status_ok_callback();
	}

	private function set_valid_license_key_token(): void {
		add_filter( 'stellarwp/uplink/tec/license_get_key', fn() => 'valid-license-key' );
		$resource                    = get_resource( 'tec-seating' );
		$validity_status_option_name = $resource
			->get_license_object()
			->get_key_status_option_name();
		update_option( $validity_status_option_name, 'valid' );
		( new class {
			use OAuth_Token;

			public function open_set_oauth_token( string $token ): void {
				$this->set_oauth_token( $token );
			}
		} )->open_set_oauth_token( 'test-access-token' );
	}

	public function test_default_context_is_admin(): void {
		// We're not explicitly providing a context for the status here.
		$status = new Service_Status( 'https://example.com', );

		$this->assertEquals( 'admin', $status->get_context() );
	}

	public function test_status_is_cached_for_five_minutes_in_admin_context(): void {
		$status         = new Service_Status( 'https://example.com', null, 'admin' );
		$transient_name = $status->get_transient_name();

		delete_transient( $transient_name );
		$this->assertEmpty( get_transient( $transient_name ) );

		// This will run an update that should cache the result.
		$status->get_status();

		// We do not care about the particular cached value here, only that it's cached.
		$this->assertNotEmpty( get_transient( $transient_name ) );
		// The transient expiration should be 5 minutes in admin context.
		$this->assertEqualsWithDelta(
			time() + 600,
			get_option( '_transient_timeout_' . $transient_name ),
			10
		);
	}

	public function test_status_update_in_admin_context_will_not_fire_HEAD_request(): void {
		$status         = new Service_Status( 'https://example.com', null, 'admin' );
		$wp_remote_head = $this->mock_wp_remote( 'head', 'https://example.com', [], [] );
		// Set a valid license key and token to ensure the code will get to the point where it would ping the service.
		$this->set_valid_license_key_token();

		$status->get_status();

		$this->assertFalse( $wp_remote_head->was_called() );
	}

	public function test_status_is_cached_for_one_minute_in_non_admin_context(): void {
		$status         = new Service_Status( 'https://example.com', null, 'not-admin' );
		$transient_name = $status->get_transient_name();

		delete_transient( $transient_name );
		$this->assertEmpty( get_transient( $transient_name ) );

		// This will run an update that should cache the result.
		$status->get_status();

		// We do not care about the particular cached value here, only that it's cached.
		$this->assertNotEmpty( get_transient( $transient_name ) );
		// The transient expiration should be 5 minutes in admin context.
		$this->assertEqualsWithDelta(
			time() + 60,
			get_option( '_transient_timeout_' . $transient_name ),
			10
		);
	}

	public function test_status_update_in_non_admin_context_will_fire_HEAD_request():void{
		$status         = new Service_Status( 'https://example.com', null, 'not-admin' );
		$wp_remote_head = $this->mock_wp_remote( 'head', 'https://example.com', [], [] );
		// Set a valid license key and token to ensure the code will get to the point where it would ping the service.
		$this->set_valid_license_key_token();

		$status->get_status();

		$this->assertTrue( $wp_remote_head->was_called());
	}

	public function test_has_no_license():void{
		$status         = new Service_Status( 'https://example.com', null, 'admin' );
		$transient_name = $status->get_transient_name();

		delete_transient( $transient_name );
		$this->assertEmpty( get_transient( $transient_name ) );

		// Calling the has_no_license method on the status should not trigger an update and, thus, a transient update.
		$status->has_no_license();

		$this->assertEmpty( get_transient( $transient_name ) );
	}

}
