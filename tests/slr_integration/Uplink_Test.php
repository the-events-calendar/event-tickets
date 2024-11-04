<?php

namespace TEC\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use TEC\Common\StellarWP\Uplink\Resources\Collection;
use TEC\Common\StellarWP\Uplink\Storage\Contracts\Storage;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;

class Uplink_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use WP_Remote_Mocks;

	protected $controller_class = Uplink::class;

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
	 * @var string|null
	 */
	private ?string $pagenow = null;

	/**
	 * @before
	 */
	public function before_each(): void {
		wp_set_current_user( 1 );
		$this->collection    = tribe( Collection::class );
		$this->resource      = $this->collection->get( $this->et_slr_plugin_slug );
		$this->token_manager = tribe( Token_Manager::class );
		global $pagenow;
		$this->pagenow = $pagenow;
		$pagenow = 'admin.php';
		$storage             = tribe( Storage::class );
		$storage->set(
			'stellarwp_auth_url_tec_seating',
			'https://my.theeventscalendar.com/account-auth/?uplink_callback=aHR0cHM6Ly90ZWNkZXYubG5kby5zaXRlL3dwLWFkbWluL2FkbWluLnBocD9wYWdlPXRlYy10aWNrZXRzLXNldHRpbmdzJnRhYj1saWNlbnNlcyZ1cGxpbmtfc2x1Zz10ZWMtc2VhdGluZyZfdXBsaW5rX25vbmNlPU1zb3ptQlZJVUp4aFh6c0Q%3D'
		);
	}

	/**
	 * @after
	 */
	public function after_each(): void {
		global $pagenow;
		$pagenow = $this->pagenow;
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
	 * @param string $key   The license key to set.
	 * @param bool   $valid Whether the license key should be considered valid.
	 */
	private function set_license_key( string $key, bool $valid ): void {
		test_remove_seating_license_key_callback();
		$this->resource->set_license_key( $key );
		$this->assertEquals( $key, $this->resource->get_license_key() );
		$this->assertEquals( $key, get_option( $this->resource->get_license_object()->get_key_option_name() ) );
		$this->assertTrue( $this->resource->is_using_oauth() );

		if ( $valid ) {
			$this->token_manager->store( $key, $this->resource );
		} else {
			$this->token_manager->delete( $this->resource->get_slug() );
		}

		$this->set_fn_return( 'wp_create_nonce', '12345678' );
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

	public function test_not_connected_empty_license_key():void{
		wp_set_current_user( 1 );
		$this->set_license_key( '', false );
		$controller = $this->make_controller();
		$controller->register();

		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );
		$fields = $this->get_relevant_license_fields( $license_fields );

		$connect_html = $fields['stellarwp-uplink_tec-seating']['html'];
		$fields['stellarwp-uplink_tec-seating']['html']  = 'tested in JSON snapshot';
		$this->assertMatchesJsonSnapshot( wp_json_encode( $fields, JSON_SNAPSHOT_OPTIONS ) );
		$this->assertMatchesHtmlSnapshot( $connect_html );
	}

	public function test_with_valid_license_key(): void {
		$this->set_valid_license( 'valid-license-key', true );
		$controller = $this->make_controller();
		$controller->register();

		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );
		$fields = $this->get_relevant_license_fields( $license_fields );

		$connect_html = $fields['stellarwp-uplink_tec-seating']['html'];
		$fields['stellarwp-uplink_tec-seating']['html']  = 'tested in JSON snapshot';
		$this->assertMatchesJsonSnapshot( wp_json_encode( $fields, JSON_SNAPSHOT_OPTIONS ) );
		$this->assertMatchesHtmlSnapshot( $connect_html );
	}

	public function test_with_invalid_license_key(): void {
		$this->set_invalid_license( 'invalid-license-key', false );
		$controller = $this->make_controller();
		$controller->register();

		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );
		$fields = $this->get_relevant_license_fields( $license_fields );

		$connect_html = $fields['stellarwp-uplink_tec-seating']['html'];
		$fields['stellarwp-uplink_tec-seating']['html']  = 'tested in JSON snapshot';
		$this->assertMatchesJsonSnapshot( wp_json_encode( $fields, JSON_SNAPSHOT_OPTIONS ) );
		$this->assertMatchesHtmlSnapshot( $connect_html );
	}

	public function test_with_expired_license_key(): void {
		$this->set_expired_license( );
		$controller = $this->make_controller();
		$controller->register();

		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );
		$this->assertIsArray( $license_fields );
		$fields = $this->get_relevant_license_fields( $license_fields );

		$connect_html = $fields['stellarwp-uplink_tec-seating']['html'];
		$fields['stellarwp-uplink_tec-seating']['html']  = 'tested in JSON snapshot';
		$this->assertMatchesJsonSnapshot( wp_json_encode( $fields, JSON_SNAPSHOT_OPTIONS ) );
		$this->assertMatchesHtmlSnapshot( $connect_html );
	}
}
