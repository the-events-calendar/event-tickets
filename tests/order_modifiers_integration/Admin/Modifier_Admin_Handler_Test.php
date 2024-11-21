<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use InvalidArgumentException;
use ReflectionMethod;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Common\StellarWP\Assets\Assets;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Admin\Pages;
use Closure;
use Generator;
use Tribe\Tickets\Test\Traits\Order_Modifiers;
use TEC\Tickets\Exceptions\Not_Found_Exception;

class Modifier_Admin_Handler_Test extends Controller_Test_Case {
	use With_Uopz;
	use SnapshotAssertions;
	use Order_Modifiers;

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

	/**
	 * @test
	 * @dataProvider get_url_data_provider
	 */
	public function it_should_generate_the_correct_order_modifiers_url( Closure $fixture ): void {
		// Execute the fixture to set up filters and arguments.
		$result = $fixture();

		$args         = $result['args'] ?? [];
		$expected_url = $result['expected_url'] ?? '';

		$controller = $this->make_controller();

		// Get the URL and assert the result.
		$this->assertEquals( $expected_url, $controller->get_url( $args ), 'The generated URL does not match the expected value.' );
	}

	/**
	 * Data provider for `it_should_generate_the_correct_order_modifiers_url`.
	 *
	 * @return Generator
	 */
	public function get_url_data_provider(): Generator {
		$controller_slug = Modifier_Admin_Handler::$slug;

		yield 'default arguments' => [
			function () use ( $controller_slug ): array {
				remove_all_filters( 'tec_tickets_commerce_order_modifiers_page_url' );

				return [
					'args'         => [],
					'expected_url' => admin_url( "admin.php?page={$controller_slug}" ),
				];
			},
		];

		yield 'additional arguments' => [
			function () use ( $controller_slug ): array {
				remove_all_filters( 'tec_tickets_commerce_order_modifiers_page_url' );

				return [
					'args'         => [
						'section' => 'fees',
						'filter'  => 'active',
					],
					'expected_url' => admin_url( "admin.php?page={$controller_slug}&section=fees&filter=active" ),
				];
			},
		];

		yield 'modified via filter' => [
			function () use ( $controller_slug ): array {
				add_filter(
					'tec_tickets_commerce_order_modifiers_page_url',
					function ( $url ) {
						return $url . '&custom_param=1';
					}
				);

				return [
					'args'         => [],
					'expected_url' => admin_url( "admin.php?page={$controller_slug}&custom_param=1" ),
				];
			},
		];

		yield 'filter with arguments' => [
			function () use ( $controller_slug ): array {
				add_filter(
					'tec_tickets_commerce_order_modifiers_page_url',
					function ( $url ) {
						return $url . '&custom_param=1';
					}
				);

				return [
					'args'         => [
						'section' => 'fees',
						'filter'  => 'active',
					],
					'expected_url' => admin_url( "admin.php?page={$controller_slug}&section=fees&filter=active&custom_param=1" ),
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider modifier_data_provider
	 */
	public function it_should_return_modifier_data_or_throw_exception_based_on_id_and_type( Closure $fixture ): void {
		// Setup the test environment with the provided fixture.
		$data = $fixture();

		// Extract variables from the fixture.
		$modifier_id        = $data['modifier_id'];
		$modifier_type      = $data['modifier_type'];
		$expected_result    = $data['expected_result'];
		$expected_exception = $data['expected_exception'];

		// Set the request variable.
		$_GET['modifier'] = $modifier_type;

		// Use reflection to access the protected method.
		$controller = $this->make_controller();
		$method     = new ReflectionMethod( $controller, 'get_modifier_data_by_id' );
		$method->setAccessible( true );

		// Handle cases where an exception is expected.
		if ( $expected_exception ) {
			$this->expectException( $expected_exception );
			$method->invoke( $controller, $modifier_id );

			return; // Exit after asserting the exception.
		}

		// Call the method and assert the result.
		$modifier_data = $method->invoke( $controller, $modifier_id );
		// Assert specific keys only.
		foreach ( $expected_result as $key => $value ) {
			$this->assertArrayHasKey( $key, $modifier_data, "Key '{$key}' is missing in the modifier data." );
			$this->assertEquals( $value, $modifier_data[ $key ], "Value for '{$key}' does not match." );
		}
	}

	/**
	 * Data provider for `it_should_return_modifier_data_or_throw_exception_based_on_id_and_type`.
	 *
	 * @return Generator
	 */
	public function modifier_data_provider(): Generator {
		yield 'Valid modifier ID and type' => [
			function (): array {
				// Set up a valid modifier.
				$modifier = $this->upsert_order_modifier_for_test(
					[
						'modifier'                    => 'fee',
						'order_modifier_amount'       => '1000',
						'order_modifier_display_name' => 'Test Fee',
						'order_modifier_sub_type'     => 'flat',
						'order_modifier_slug'         => 'test_modifier',
						'order_modifier_status'       => 'active',
					]
				);

				return [
					'modifier_id'        => $modifier->id,
					'modifier_type'      => 'fee',
					'expected_result'    => [
						'id'            => $modifier->id,
						'modifier_type' => 'fee',
						'sub_type'      => 'flat',
						'raw_amount'    => 1000.0,
						'slug'          => 'test_modifier',
						'display_name'  => 'Test Fee',
						'status'        => 'active',
						'start_time'    => null,
						'end_time'      => null,
					],
					'expected_exception' => null,
				];
			},
		];

		yield 'Invalid modifier ID' => [
			function (): array {
				return [
					'modifier_id'        => 99999,
					'modifier_type'      => 'fee',
					'expected_result'    => null, // No result expected.
					'expected_exception' => Not_Found_Exception::class, // Exception expected.
				];
			},
		];

		yield 'Invalid modifier type' => [
			function (): array {
				// Set up a valid modifier.
				$modifier = $this->upsert_order_modifier_for_test(
					[
						'modifier'                    => 'fee',
						'order_modifier_amount'       => '1000',
						'order_modifier_display_name' => 'Test Fee',
					]
				);

				return [
					'modifier_id'        => $modifier->id,
					'modifier_type'      => 'invalid-modifier',
					'expected_result'    => null, // No result expected.
					'expected_exception' => InvalidArgumentException::class, // Exception expected.
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider handle_form_submission_data_provider
	 */
	public function it_should_handle_form_submission_correctly_with_valid_data( Closure $fixture ): void {
		// Setup necessary overrides.
		$redirect_url = null;

		// Mock wp_safe_redirect to capture the redirect URL.
		$this->set_fn_return(
			'wp_safe_redirect',
			function ( $url ) use ( &$redirect_url ) {
				$redirect_url = $url;
			},
			true
		);

		// Prevent `exit` from terminating the test.
		uopz_allow_exit( false );

		// Setup the fixture.
		$data = $fixture();

		// Set request variables and nonce for the test.
		$_POST    = $data['post_data'];
		$_REQUEST = $data['post_data'];

		if ( isset( $data['nonce'] ) ) {
			$_POST['order_modifier_save_action'] = $data['nonce'];
		}

		// Mock nonce verification.
		$this->set_fn_return(
			'check_admin_referer',
			function () use ( $data ) {
				return $data['valid_nonce'] ?? false;
			},
			true
		);

		// Call the method.
		$controller = $this->make_controller();
		$controller->handle_form_submission();

		// Assert that the redirect URL is not null.
		$this->assertNotNull( $redirect_url, 'The redirect URL should not be null.' );

		// Parse the redirect URL into its components for validation.
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $query_params );

		// Assert the required parameters.
		$this->assertEquals(
			'tec-tickets-order-modifiers',
			$query_params['page'] ?? null,
			'The "page" parameter does not match the expected value.'
		);
		$this->assertEquals(
			$data['post_data']['modifier'],
			$query_params['modifier'] ?? null,
			'The "modifier" parameter does not match the expected value.'
		);
		$this->assertEquals(
			'1',
			$query_params['updated'] ?? null,
			'The "updated" parameter should always be "1".'
		);

		// Assert `modifier_id` is greater than 0.
		$this->assertGreaterThan(
			0,
			intval( $query_params['modifier_id'] ?? 0 ),
			'The "modifier_id" parameter should be greater than 0.'
		);
	}

	public function handle_form_submission_data_provider(): Generator {
		$scenarios = [
			'Flat, active, valid slug' => [
				'sub_type'     => 'flat',
				'amount'       => 1000,
				'slug'         => 'valid_slug',
				'display_name' => 'Fee Test',
				'status'       => 'active',
			],
			'Percent, draft, random slug' => [
				'sub_type'     => 'percent',
				'amount'       => 500,
				'slug'         => 'random_slug_123',
				'display_name' => 'Another Test',
				'status'       => 'draft',
			],
			'Flat, inactive, emoji name' => [
				'sub_type'     => 'flat',
				'amount'       => 250,
				'slug'         => 'emoji_slug',
				'display_name' => 'Emoji 😃 Name',
				'status'       => 'inactive',
			],
			'Percent, active, long display name' => [
				'sub_type'     => 'percent',
				'amount'       => 750,
				'slug'         => 'long_slug_name',
				'display_name' => 'A very long display name for testing purposes',
				'status'       => 'active',
			],
			// Additional scenarios
			'Flat, active, numeric slug' => [
				'sub_type'     => 'flat',
				'amount'       => 1200,
				'slug'         => '123456',
				'display_name' => 'Numeric Slug Test',
				'status'       => 'active',
			],
			'Percent, draft, alphanumeric slug' => [
				'sub_type'     => 'percent',
				'amount'       => 300,
				'slug'         => 'abc123xyz',
				'display_name' => 'Alphanumeric Slug Test',
				'status'       => 'draft',
			],
			'Flat, active, special characters in slug' => [
				'sub_type'     => 'flat',
				'amount'       => 500,
				'slug'         => 'slug_with-special.chars',
				'display_name' => 'Special Characters in Slug',
				'status'       => 'active',
			],
			'Percent, inactive, no spaces in name' => [
				'sub_type'     => 'percent',
				'amount'       => 100,
				'slug'         => 'no_spaces_slug',
				'display_name' => 'NoSpacesName',
				'status'       => 'inactive',
			],
			'Flat, active, simple display name' => [
				'sub_type'     => 'flat',
				'amount'       => 2000,
				'slug'         => 'simple_slug',
				'display_name' => 'Simple Name',
				'status'       => 'active',
			],
			'Percent, active, camelCase slug' => [
				'sub_type'     => 'percent',
				'amount'       => 1500,
				'slug'         => 'camelCaseSlug',
				'display_name' => 'Camel Case Slug Test',
				'status'       => 'active',
			],
			'Flat, inactive, short display name' => [
				'sub_type'     => 'flat',
				'amount'       => 800,
				'slug'         => 'short_slug',
				'display_name' => 'Short',
				'status'       => 'inactive',
			],
			'Percent, draft, unique slug' => [
				'sub_type'     => 'percent',
				'amount'       => 1800,
				'slug'         => 'unique-slug',
				'display_name' => 'Unique Test Display Name',
				'status'       => 'draft',
			],
		];


		foreach ( $scenarios as $scenario_name => $data ) {
			yield $scenario_name => [
				function () use ( $data ) {
					return [
						'post_data'   => [
							'order_modifier_sub_type'     => $data['sub_type'],
							'order_modifier_amount'       => $data['amount'],
							'order_modifier_slug'         => $data['slug'],
							'order_modifier_display_name' => $data['display_name'],
							'order_modifier_status'       => $data['status'],
							'order_modifier_form_save'    => true,
							'modifier'                    => 'fee',
						],
						'nonce'       => 'valid_nonce',
						'valid_nonce' => true,
					];
				},
			];
		}
	}

}
