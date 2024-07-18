<?php

namespace TEC\Tickets\Libraries;

use Spatie\Snapshots\MatchesSnapshots;
use TEC\Common\StellarWP\Uplink\Resources\Collection;
use TEC\Common\StellarWP\Uplink\Auth\Token\Token_Manager;
use TEC\Common\Libraries\Provider as Libraries_Provider;
use function Code_Snippets\code_snippets;


class ControllerTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;

	/**
	 * @var Token_Manager
	 */
	private $token_manager;


	protected $collection;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected string $et_slr_plugin_slug = 'tec-seating';

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected string $et_slr_plugin_name = 'Seat Layouts & Reservations';

	/**
	 * Main plugin object.
	 *
	 * @var object
	 */
	protected object $et_main;

	protected $resource;


	public function setUp() {
		parent::setUp();
		set_current_user( 1 );
		$this->collection = tribe( Collection::class );
		$this->resource   = $this->collection->get( $this->et_slr_plugin_slug );

		$prefix = tribe( Libraries_Provider::class )->get_hook_prefix();

		$this->token_manager = new Token_Manager( $prefix, $this->collection );

		$test = $this->collection->offsetGet('tec-seating');
		codecept_debug($test);

	}

	/**
	 * @test
	 */
	public function it_should_have_the_fields_for_seating_licenses() {
		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );

		$this->assertIsArray( $license_fields );

		// Extract the relevant keys
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

		// Assert that the relevant keys exist
		foreach ( $relevant_keys as $key ) {
			$this->assertArrayHasKey( $key, $license_fields );
		}

		// Snapshot test only the relevant fields
		$this->assertMatchesSnapshot( $relevant_fields );
	}

	/**
	 * @test
	 */
	public function it_should_have_the_fields_for_seating_licenses_with_valid_license() {

		$test_license_key = '22222222222222222';
		$this->resource->set_license_key( $test_license_key );
		$license_key = $this->resource->get_license_key();
		$this->assertEquals( $test_license_key, $license_key );

		$option_name          = $this->resource->get_license_object()->get_key_option_name();
		$option_license_value = get_option( $option_name );
		$this->assertEquals( $test_license_key, $option_license_value );

		$token = '22222222222222222';
		$this->assertTrue( $this->token_manager->store( $token, $this->et_slr_plugin_slug ) );
		$this->assertEquals( $token, $this->token_manager->get( $this->et_slr_plugin_slug ) );

		$license_fields = apply_filters( 'tribe_license_fields', [], 0, 999 );

		$this->assertIsArray( $license_fields );

		// Extract the relevant keys
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

		// Assert that the relevant keys exist
		foreach ( $relevant_keys as $key ) {
			$this->assertArrayHasKey( $key, $license_fields );
		}

		// Snapshot test only the relevant fields
		$this->assertMatchesSnapshot( $relevant_fields );
	}

}
