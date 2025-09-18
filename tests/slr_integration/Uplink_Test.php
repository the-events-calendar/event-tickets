<?php

namespace TEC\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use TEC\Common\StellarWP\Uplink\Resources\Collection;
use TEC\Common\StellarWP\Uplink\Storage\Contracts\Storage;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Service\Layouts as Layouts_Service;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Seat_Types;
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
		$this->set_valid_license();
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
		$this->set_invalid_license();
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

	public function test_reset_data_on_new_connection() {
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-map-1',
					'name'          => 'Some Map 1',
					'seats'         => 10,
					'screenshotUrl' => 'https://example.com/some-map-1.png',
				],
				[
					'id'            => 'some-map-2',
					'name'          => 'Some Map 2',
					'seats'         => 20,
					'screenshotUrl' => 'https://example.com/some-map-2.png',
				],
			]
		);
		set_transient( Maps_Service::update_transient_name(), time() );

		Layouts_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-1',
					'name'          => 'Some Layout 1',
					'seats'         => 10,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-1',
					'screenshotUrl' => 'https://example.com/some-layouts-1.png',
				],
				[
					'id'            => 'some-layout-2',
					'name'          => 'Some Layout 2',
					'seats'         => 20,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-2',
					'screenshotUrl' => 'https://example.com/some-layouts-2.png',
				],
			]
		);
		set_transient( Layouts_Service::update_transient_name(), time() );

		Seat_Types::insert_many(
			[
				[
					'id'     => 'some-seat-type-1',
					'name'   => 'Some Seat Type 1',
					'seats'  => 10,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'some-seat-type-2',
					'name'   => 'Some Seat Type 2',
					'seats'  => 20,
					'map'    => 'some-map-2',
					'layout' => 'some-layout-2',
				],
			]
		);
		set_transient( Service\Seat_Types::update_transient_name(), time() );

		$this->assertCount( 2, iterator_to_array( Tables\Maps::fetch_all() ) );
		$this->assertCount( 2, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 2, iterator_to_array( Seat_Types::fetch_all() ) );

		$this->make_controller()->register();

		do_action( 'stellarwp/uplink/tec/tec-seating/connected' );

		$this->assertEmpty( get_transient( Maps_Service::update_transient_name() ) );
		$this->assertEmpty( get_transient( Layouts_Service::update_transient_name() ) );
		$this->assertEmpty( get_transient( Service\Seat_Types::update_transient_name() ) );

		$this->assertEmpty( iterator_to_array( Tables\Maps::fetch_all() ) );
		$this->assertEmpty( iterator_to_array( Layouts::fetch_all() ) );
		$this->assertEmpty( iterator_to_array( Seat_Types::fetch_all() ) );
	}

	/**
	 * Test customize_field_html method with seating service plugin and invalid license.
	 *
	 * @since TBD
	 */
	public function test_customize_field_html_with_seating_service_invalid_license(): void {
		/** @var Uplink $controller */
		$controller = $this->make_controller();

		// Set up an invalid license state.
		$this->set_invalid_license();

		// Create sample field HTML with the tooltip paragraph structure that the regex expects.
		$original_field_html = '<div class="license-field">
			<label for="license-key">License Key</label>
			<input type="text" id="license-key" name="license-key" />
			<p class="tooltip description">A valid license key is required for support and updates</p>
		</div>';

		// Call the method with the actual resource.
		$customized_html = $controller->customize_field_html( $original_field_html, $this->resource );

		// Assert that the HTML was customized with the upsell tooltip.
		$this->assertStringContainsString( 'Buy a license', $customized_html );
		$this->assertStringContainsString( 'https://evnt.is/1bed', $customized_html );
		$this->assertStringContainsString( 'target="_blank"', $customized_html );
		$this->assertStringNotContainsString( 'A valid license key is required for support and updates', $customized_html );

		// Test the snapshot to ensure consistent output.
		$this->assertMatchesHtmlSnapshot( $customized_html );
	}

	/**
	 * Test customize_field_html method with non-seating service plugin.
	 *
	 * @since TBD
	 */
	public function test_customize_field_html_with_other_plugin(): void {
		/** @var Uplink $controller */
		$controller = $this->make_controller();

		// Create a mock plugin object with a different slug.
		$plugin = $this->createMock( \TEC\Common\StellarWP\Uplink\Resources\Resource::class );
		$plugin->method( 'get_slug' )->willReturn( 'tec-events-pro' );

		// Create sample field HTML with the tooltip paragraph structure.
		$original_field_html = '<div class="license-field">
			<label for="license-key">License Key</label>
			<input type="text" id="license-key" name="license-key" />
			<p class="tooltip description">A valid license key is required for support and updates</p>
		</div>';

		// Call the method.
		$customized_html = $controller->customize_field_html( $original_field_html, $plugin );

		// Assert that the HTML was not modified since it's not the seating service.
		$this->assertEquals( $original_field_html, $customized_html );
		$this->assertStringNotContainsString( 'Buy a license', $customized_html );
		$this->assertStringNotContainsString( 'https://evnt.is/1bed', $customized_html );
	}
}
