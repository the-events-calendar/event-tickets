<?php

namespace TEC\Tickets\Seating\Libraries;

use Spatie\Snapshots\MatchesSnapshots;
use TEC\Common\StellarWP\Uplink\Resources\Collection;
use TEC\Common\StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use TEC\Common\StellarWP\Uplink\Storage\Contracts\Storage;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;

class Uplink_Test extends \Codeception\TestCase\WPTestCase {
	use MatchesSnapshots;
	use With_Uopz;
	use WP_Remote_Mocks;

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * Collection instance.
	 *
	 * @var Collection
	 */
	protected $collection;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected string $et_slr_plugin_slug = 'tec-seating';

	/**
	 * Resource instance.
	 *
	 * @var object
	 */
	protected $resource;

	/**
	 * @before
	 */
	public function before_each(): void {
		wp_set_current_user( 1 );
		$this->collection    = tribe( Collection::class );
		$this->resource      = $this->collection->get( $this->et_slr_plugin_slug );
		$this->token_manager = tribe( Token_Manager::class );
		$storage = tribe( Storage::class );
		$storage->set(
			'stellarwp_auth_url_tec_seating',
			'https://my.theeventscalendar.com/account-auth/?uplink_callback=aHR0cHM6Ly90ZWNkZXYubG5kby5zaXRlL3dwLWFkbWluL2FkbWluLnBocD9wYWdlPXRlYy10aWNrZXRzLXNldHRpbmdzJnRhYj1saWNlbnNlcyZ1cGxpbmtfc2x1Zz10ZWMtc2VhdGluZyZfdXBsaW5rX25vbmNlPU1zb3ptQlZJVUp4aFh6c0Q%3D'
		);
	}

	/**
	 * @test
	 */
	public function it_should_have_the_fields_for_seating_licenses_with_incorrect_permissions_snapshot(): void {
		wp_set_current_user( 0 );
		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );

		$relevant_fields = $this->get_relevant_license_fields( $license_fields );

		// Assert that the relevant keys exist.
		$this->assertArrayHasKey( 'stellarwp-uplink_tec-seating-heading', $license_fields );
		$this->assertArrayHasKey( 'stellarwp-uplink_tec-seating', $license_fields );

		$this->assertStringContainsString( 'Contact your network administrator to connect', $relevant_fields['stellarwp-uplink_tec-seating']['html'] );

		// Snapshot test only the relevant fields.
		$this->assertMatchesSnapshot( $relevant_fields );
	}

	/**
	 * @test
	 */
	public function it_should_have_the_fields_for_seating_licenses_with_no_license_snapshot(): void {
		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );

		$relevant_fields = $this->get_relevant_license_fields( $license_fields );

		// Assert that the relevant keys exist.
		$this->assertArrayHasKey( 'stellarwp-uplink_tec-seating-heading', $license_fields );
		$this->assertArrayHasKey( 'stellarwp-uplink_tec-seating', $license_fields );

		// Snapshot test only the relevant fields.
		$this->assertMatchesSnapshot( $relevant_fields );
	}

	/**
	 * @test
	 */
	public function it_should_have_the_fields_for_seating_licenses_with_valid_license_snapshot(): void {
		$this->set_valid_license();
		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );

		$relevant_fields = $this->get_relevant_license_fields( $license_fields );

		// Assert that the relevant keys exist.
		$this->assertArrayHasKey( 'stellarwp-uplink_tec-seating-heading', $license_fields );
		$this->assertArrayHasKey( 'stellarwp-uplink_tec-seating', $license_fields );

		// Snapshot test only the relevant fields.
		$this->assertMatchesSnapshot( $relevant_fields );
	}

	/**
	 * @test
	 */
	public function it_should_have_the_fields_for_seating_licenses_with_valid_license(): void {
		$this->set_valid_license();
		$this->verify_license_fields_contain_class( 'authorized' );
	}

	/**
	 * @test
	 */
	public function it_should_toggle_license_and_check_authorization_status(): void {
		$this->set_valid_license();
		$this->verify_license_fields_contain_class( 'authorized' );

		// Disconnect the license
		$this->resource->set_license_key( '' );
		$this->assertEquals( '', $this->resource->get_license_key() );
		$this->assertEquals( '', get_option( $this->resource->get_license_object()->get_key_option_name() ) );
		$this->assertTrue( $this->token_manager->delete( $this->et_slr_plugin_slug ) );

		// Confirm lack of authorization
		$this->verify_license_fields_contain_class( 'not-authorized' );

		// Reconnect the license
		$this->set_valid_license();
		$this->verify_license_fields_contain_class( 'authorized' );
	}

	/**
	 * @test
	 */
	public function it_should_handle_invalid_license(): void {
		$this->set_invalid_license();
		$this->verify_license_fields_contain_class( 'not-authorized' );
	}

	/**
	 * @test
	 */
	public function it_should_handle_expired_license(): void {
		$this->set_expired_license();
		$this->verify_license_fields_contain_class( 'not-authorized' );
	}

	/**
	 * @test
	 */
	public function it_should_handle_no_license_key(): void {
		$this->resource->set_license_key( '' );
		$this->assertEquals( '', $this->resource->get_license_key() );
		$this->assertEquals( '', get_option( $this->resource->get_license_object()->get_key_option_name() ) );
		$this->verify_license_fields_contain_class( 'not-authorized' );
	}

	/**
	 * Sets a valid license key for testing.
	 */
	private function set_valid_license(): void {
		$this->set_license_key( '22222222222222222', true );
	}

	/**
	 * Sets an invalid license key for testing.
	 */
	private function set_invalid_license(): void {
		$this->set_license_key( 'invalid_key', false );
	}

	/**
	 * Sets an expired license key for testing.
	 */
	private function set_expired_license(): void {
		$this->set_license_key( 'expired_key', false );
	}

	/**
	 * Sets a license key and optionally mark it as valid for testing.
	 *
	 * @param string $key The license key to set.
	 * @param bool   $valid Whether the license key should be considered valid.
	 */
	private function set_license_key( string $key, bool $valid ): void {
		$this->resource->set_license_key( $key );
		$this->assertEquals( $key, $this->resource->get_license_key() );
		$this->assertEquals( $key, get_option( $this->resource->get_license_object()->get_key_option_name() ) );

		if ( $valid ) {
			$this->assertTrue( $this->resource->is_using_oauth() );
			$this->token_manager->store( $key, $this->resource );
		}

		$this->set_fn_return( 'wp_create_nonce', '12345678' );
	}

	/**
	 * Helper method to verify that license fields contain a specific class.
	 *
	 * @param string $class The class to check for.
	 */
	private function verify_license_fields_contain_class( string $class ): void {
		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );

		$relevant_fields = $this->get_relevant_license_fields( $license_fields );

		$this->assertStringContainsString( $class, $relevant_fields['stellarwp-uplink_tec-seating']['html'] );
	}

	/**
	 * Extracts and modifies the relevant license fields for snapshot testing.
	 *
	 * @param array $license_fields The license fields.
	 *
	 * @return array The modified relevant license fields.
	 */
	private function get_relevant_license_fields( array $license_fields ): array {
		$relevant_keys = [
			'stellarwp-uplink_tec-seating-heading',
			'stellarwp-uplink_tec-seating',
		];

		$relevant_fields = array_intersect_key( $license_fields, array_flip( $relevant_keys ) );

		// Modify the `html` field to replace the dynamic part with a static placeholder.
		if ( isset( $relevant_fields['stellarwp-uplink_tec-seating']['html'] ) ) {
			$relevant_fields['stellarwp-uplink_tec-seating']['html'] = preg_replace(
				'/uplink_callback=[^"]+/',
				'uplink_callback={STATIC_CALLBACK}',
				$relevant_fields['stellarwp-uplink_tec-seating']['html']
			);
		}

		return $relevant_fields;
	}
}
