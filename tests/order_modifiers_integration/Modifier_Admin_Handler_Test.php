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
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifier_Repository;

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
		$this->set_fn_return( 'tribe_exit', null );

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
		$modifier_id = intval( $query_params['modifier_id'] ?? 0 );
		$this->assertGreaterThan( 0, $modifier_id, 'The "modifier_id" parameter should be greater than 0.' );

		// Validate data stored in the repository.
		$modifier_repository = new Order_Modifier_Repository( $data['post_data']['modifier'] );
		$stored_modifier     = $modifier_repository->find_by_id( $modifier_id );

		$this->assertNotNull( $stored_modifier, 'The stored modifier should exist in the repository.' );

		// Validate specific fields in the stored modifier.
		$this->assertEquals(
			$data['post_data']['order_modifier_display_name'],
			$stored_modifier->display_name,
			'The "display_name" field does not match the stored value.'
		);
		$this->assertEquals(
			$data['post_data']['order_modifier_amount'],
			$stored_modifier->raw_amount,
			'The "amount" field does not match the stored value.'
		);
		$this->assertEquals(
			$data['post_data']['order_modifier_sub_type'],
			$stored_modifier->sub_type,
			'The "sub_type" field does not match the stored value.'
		);
		$this->assertEquals(
			$data['post_data']['order_modifier_status'],
			$stored_modifier->status,
			'The "status" field does not match the stored value.'
		);
		$this->assertEquals(
			$data['post_data']['order_modifier_slug'],
			$stored_modifier->slug,
			'The "slug" field does not match the stored value.'
		);
	}

	/**
	 * @test
	 * @dataProvider handle_form_submission_missing_field_data_provider
	 */
	public function it_should_handle_form_submission_with_invalid_data( Closure $fixture ): void {
		// Setup necessary overrides.
		$captured_error_message = null;

		// Mock `render_error_message` to capture the error messages.
		$this->set_class_fn_return(
			$this->controller_class,
			'render_error_message',
			function ( $message ) use ( &$captured_error_message ) {
				$captured_error_message = $message;
			},
			true
		);

		// Prevent `exit` from terminating the test.
		$this->set_fn_return( 'tribe_exit', null );

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

		// Assert no redirect happened.
		$this->assertNull( $redirect_url ?? null, 'Redirect should not occur on invalid submission.' );

		// Assert error message is captured.
		$this->assertNotNull( $captured_error_message, 'An error message should be captured on invalid submission.' );
		$this->assertStringContainsString(
			$data['expected_error'],
			$captured_error_message,
			'The captured error message does not match the expected value.'
		);
	}

	public function handle_form_submission_data_provider(): Generator {
		$scenarios = [
			'Flat, active, valid slug'                 => [
				'sub_type'     => 'flat',
				'amount'       => 1000,
				'slug'         => 'valid_slug',
				'display_name' => 'Fee Test',
				'status'       => 'active',
			],
			'Percent, draft, random slug'              => [
				'sub_type'     => 'percent',
				'amount'       => 500,
				'slug'         => 'random_slug_123',
				'display_name' => 'Another Test',
				'status'       => 'draft',
			],
			'Flat, inactive, emoji name'               => [
				'sub_type'     => 'flat',
				'amount'       => 250,
				'slug'         => 'emoji_slug',
				'display_name' => 'Emoji 😃 Name',
				'status'       => 'inactive',
			],
			'Percent, active, long display name'       => [
				'sub_type'     => 'percent',
				'amount'       => 750,
				'slug'         => 'long_slug_name',
				'display_name' => 'A very long display name for testing purposes',
				'status'       => 'active',
			],
			// Additional scenarios
			'Flat, active, numeric slug'               => [
				'sub_type'     => 'flat',
				'amount'       => 1200,
				'slug'         => '123456',
				'display_name' => 'Numeric Slug Test',
				'status'       => 'active',
			],
			'Percent, draft, alphanumeric slug'        => [
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
			'Percent, inactive, no spaces in name'     => [
				'sub_type'     => 'percent',
				'amount'       => 100,
				'slug'         => 'no_spaces_slug',
				'display_name' => 'NoSpacesName',
				'status'       => 'inactive',
			],
			'Flat, active, simple display name'        => [
				'sub_type'     => 'flat',
				'amount'       => 2000,
				'slug'         => 'simple_slug',
				'display_name' => 'Simple Name',
				'status'       => 'active',
			],
			'Percent, active, camelCase slug'          => [
				'sub_type'     => 'percent',
				'amount'       => 1500,
				'slug'         => 'camelCaseSlug',
				'display_name' => 'Camel Case Slug Test',
				'status'       => 'active',
			],
			'Flat, inactive, short display name'       => [
				'sub_type'     => 'flat',
				'amount'       => 800,
				'slug'         => 'short_slug',
				'display_name' => 'Short',
				'status'       => 'inactive',
			],
			'Percent, draft, unique slug'              => [
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

	public function handle_form_submission_missing_field_data_provider(): Generator {
		yield 'Missing slug' => [
			function () {
				return [
					'post_data'      => [
						'order_modifier_sub_type'     => 'flat',
						'order_modifier_amount'       => '1000',
						'order_modifier_display_name' => 'Test 1 Fee',
						'order_modifier_status'       => 'active',
						'order_modifier_form_save'    => true,
						'modifier'                    => 'fee',
					],
					'nonce'          => 'valid_nonce',
					'valid_nonce'    => true,
					'expected_error' => 'The field "slug" is required and cannot be empty.',
				];
			},
		];

		yield 'Missing sub_type' => [
			function () {
				return [
					'post_data'      => [
						'order_modifier_amount'       => '1000',
						'order_modifier_slug'         => 'test_slug',
						'order_modifier_display_name' => 'Test 2 Fee',
						'order_modifier_status'       => 'active',
						'order_modifier_form_save'    => true,
						'modifier'                    => 'fee',
					],
					'nonce'          => 'valid_nonce',
					'valid_nonce'    => true,
					'expected_error' => 'The field "sub_type" is required and cannot be empty.',
				];
			},
		];
		yield 'Missing display name' => [
			function () {
				return [
					'post_data'      => [
						'order_modifier_sub_type'  => 'flat',
						'order_modifier_amount'    => '750',
						'order_modifier_slug'      => 'test_slug_3',
						'order_modifier_status'    => 'draft',
						'order_modifier_form_save' => true,
						'modifier'                 => 'fee',
					],
					'nonce'          => 'valid_nonce',
					'valid_nonce'    => true,
					'expected_error' => 'The field "display_name" is required and cannot be empty.',
				];
			},
		];

		yield 'Missing status' => [
			function () {
				return [
					'post_data'      => [
						'order_modifier_sub_type'     => 'percent',
						'order_modifier_amount'       => '1200',
						'order_modifier_slug'         => 'test_slug_4',
						'order_modifier_display_name' => 'Test 4 Fee',
						'order_modifier_form_save'    => true,
						'modifier'                    => 'fee',
					],
					'nonce'          => 'valid_nonce',
					'valid_nonce'    => true,
					'expected_error' => 'The field "status" is required and cannot be empty.',
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider handle_notices_data_provider
	 */
	public function it_should_handle_notices_correctly( Closure $fixture ): void {
		// Capture the success message output.
		$captured_success_message = null;

		// Mock `render_success_message` to capture the success message.
		$this->set_class_fn_return(
			$this->controller_class,
			'render_success_message',
			function ( $message ) use ( &$captured_success_message ) {
				$captured_success_message = $message;
			},
			true
		);

		// Setup the fixture.
		$data = $fixture();

		// Set request variables for the test.
		$_GET     = $data['get_data'];
		$_REQUEST = $data['get_data'];

		// Call the method.
		$controller = $this->make_controller();
		$controller->handle_notices();

		// Assert the outcomes.
		if ( $data['should_display_message'] ) {
			$this->assertNotNull( $captured_success_message, 'Success message should be displayed.' );
			$this->assertEquals(
				__( 'Modifier saved successfully!', 'event-tickets' ),
				$captured_success_message,
				'Success message does not match the expected value.'
			);
		} else {
			$this->assertNull( $captured_success_message, 'Success message should not be displayed.' );
		}
	}

	public function handle_notices_data_provider(): Generator {
		yield 'Valid notice display' => [
			function () {
				return [
					'get_data'               => [
						'updated'  => 1,
						'edit'     => 1,
						'modifier' => 'fee',
						'page'     => 'tec-tickets-order-modifiers',
					],
					'should_display_message' => true,
				];
			},
		];

		yield 'Updated flag not set' => [
			function () {
				return [
					'get_data'               => [
						'edit'     => 1,
						'modifier' => 'fee',
						'page'     => 'tec-tickets-order-modifiers',
					],
					'should_display_message' => false,
				];
			},
		];

		yield 'Edit flag not set' => [
			function () {
				return [
					'get_data'               => [
						'updated'  => 1,
						'modifier' => 'fee',
						'page'     => 'tec-tickets-order-modifiers',
					],
					'should_display_message' => false,
				];
			},
		];

		yield 'Page slug mismatch' => [
			function () {
				return [
					'get_data'               => [
						'updated'  => 1,
						'edit'     => 1,
						'modifier' => 'fee',
						'page'     => 'invalid_slug',
					],
					'should_display_message' => false,
				];
			},
		];
	}

	/**
	 * @test
	 */
	public function it_should_delete_modifier_correctly(): void {
		// Mock wp_safe_redirect to capture the redirect URL.
		$redirect_url = null;
		$this->set_fn_return(
			'wp_safe_redirect',
			function ( $url ) use ( &$redirect_url ) {
				$redirect_url = $url;
			},
			true
		);

		// Prevent exit.
		$this->set_fn_return( 'tribe_exit', null );

		// Step 1: Use `handle_form_submission` to create a modifier.
		$_POST                               = [
			'order_modifier_sub_type'     => 'flat',
			'order_modifier_amount'       => '1000',
			'order_modifier_slug'         => 'test-slug',
			'order_modifier_display_name' => 'Test Modifier',
			'order_modifier_status'       => 'active',
			'order_modifier_form_save'    => true,
			'modifier'                    => 'fee',
		];
		$_REQUEST                            = $_POST; // phpcs:ignore WordPress.Security.NonceVerification
		$_POST['order_modifier_save_action'] = wp_create_nonce( 'order_modifier_save_action' );

		// Mock nonce verification.
		$this->set_fn_return(
			'check_admin_referer',
			function () {
				return true;
			},
			true
		);

		$controller = $this->make_controller();
		$controller->handle_form_submission();

		// Extract the modifier ID from the redirect URL.
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $query_params );
		$modifier_id = $query_params['modifier_id'] ?? null;

		// Ensure the modifier ID is valid.
		$this->assertNotNull( $modifier_id, 'Modifier ID should not be null.' );
		$this->assertGreaterThan( 0, intval( $modifier_id ), 'Modifier ID should be greater than 0.' );

		// Step 2: Use `handle_delete_modifier` to delete the created modifier.
		$_GET     = [
			'action'      => 'delete_modifier',
			'modifier_id' => $modifier_id,
			'modifier'    => 'fee',
			'_wpnonce'    => wp_create_nonce( 'delete_modifier_' . $modifier_id ),
		];
		$_REQUEST = $_GET; // phpcs:ignore WordPress.Security.NonceVerification

		// Call the delete method.
		$controller->handle_delete_modifier();

		// Validate the redirection URL.
		$this->assertNotNull( $redirect_url, 'Redirect URL should not be null after deletion.' );
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $query_params );
		$this->assertEquals( 'success', $query_params['deleted'] ?? null, 'Modifier should be successfully deleted.' );

		// Step 3: Confirm the modifier was deleted.
		$modifier_repository = new Order_Modifier_Repository( 'fee' );

		try {
			$deleted_modifier = $modifier_repository->find_by_id( $modifier_id );
			// If no exception is thrown, fail the test.
			$this->fail( 'Expected Not_Found_Exception was not thrown.' );
		} catch ( Not_Found_Exception $exception ) {
			$this->assertEquals(
				'Order Modifier not found.',
				$exception->getMessage(),
				'Expected exception message does not match.'
			);
		}
	}

	/**
	 * @test
	 */
	public function it_should_handle_modifier_lifecycle_correctly(): void {
		// Mock wp_safe_redirect to capture the redirect URL.
		$redirect_url = null;
		$this->set_fn_return(
			'wp_safe_redirect',
			function ( $url ) use ( &$redirect_url ) {
				$redirect_url = $url;
			},
			true
		);

		// Prevent exit.
		$this->set_fn_return( 'tribe_exit', null );

		// Step 1: Create a new modifier.
		$_POST = [
			'order_modifier_sub_type'     => 'flat',
			'order_modifier_amount'       => '1000',
			'order_modifier_slug'         => 'test-slug',
			'order_modifier_display_name' => 'Test Modifier',
			'order_modifier_status'       => 'active',
			'order_modifier_form_save'    => true,
			'modifier'                    => 'fee',
		];
		$_REQUEST                            = $_POST; // phpcs:ignore WordPress.Security.NonceVerification
		$_POST['order_modifier_save_action'] = wp_create_nonce( 'order_modifier_save_action' );

		// Mock nonce verification.
		$this->set_fn_return(
			'check_admin_referer',
			function () {
				return true;
			},
			true
		);

		$controller = $this->make_controller();
		$controller->handle_form_submission();

		// Extract the modifier ID from the redirect URL.
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $query_params );
		$modifier_id = $query_params['modifier_id'] ?? null;

		// Ensure the modifier ID is valid.
		$this->assertNotNull( $modifier_id, 'Modifier ID should not be null.' );
		$this->assertGreaterThan( 0, intval( $modifier_id ), 'Modifier ID should be greater than 0.' );

		// Step 2: Confirm the modifier exists in the repository.
		$modifier_repository = new Order_Modifier_Repository( 'fee' );
		$created_modifier    = $modifier_repository->find_by_id( $modifier_id );

		$this->assertNotNull( $created_modifier, 'Created modifier should exist in the repository.' );
		$this->assertEquals( 'flat', $created_modifier->sub_type, 'Sub-type should match the created value.' );
		$this->assertEquals( 1000, $created_modifier->raw_amount, 'Amount should match the created value.' );
		$this->assertEquals( 'test-slug', $created_modifier->slug, 'Slug should match the created value.' );
		$this->assertEquals( 'Test Modifier', $created_modifier->display_name, 'Display name should match the created value.' );
		$this->assertEquals( 'active', $created_modifier->status, 'Status should match the created value.' );

		// Step 3: Edit the modifier.
		$_POST = [
			'order_modifier_sub_type'     => 'percent',
			'order_modifier_amount'       => '500',
			'order_modifier_slug'         => 'edited-slug',
			'order_modifier_display_name' => 'Edited Modifier',
			'order_modifier_status'       => 'inactive',
			'order_modifier_form_save'    => true,
			'modifier'                    => 'fee',
			'modifier_id'                 => $modifier_id,
			'edit'                        => true,
		];
		$_REQUEST                            = $_POST; // phpcs:ignore WordPress.Security.NonceVerification
		$_POST['order_modifier_save_action'] = wp_create_nonce( 'order_modifier_save_action' );

		$controller->handle_form_submission();

		// Extract the updated modifier ID from the redirect URL.
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $query_params );
		$updated_modifier_id = $query_params['modifier_id'] ?? null;

		// Ensure the updated modifier ID matches the original.
		$this->assertEquals( $modifier_id, $updated_modifier_id, 'Updated modifier ID should match the original.' );

		// Step 4: Confirm the modifier was updated.
		$updated_modifier = $modifier_repository->find_by_id( $modifier_id );

		$this->assertNotNull( $updated_modifier, 'Updated modifier should exist in the repository.' );
		$this->assertEquals( 'percent', $updated_modifier->sub_type, 'Sub-type should match the updated value.' );
		$this->assertEquals( 500, $updated_modifier->raw_amount, 'Amount should match the updated value.' );
		$this->assertEquals( 'edited-slug', $updated_modifier->slug, 'Slug should match the updated value.' );
		$this->assertEquals( 'Edited Modifier', $updated_modifier->display_name, 'Display name should match the updated value.' );
		$this->assertEquals( 'inactive', $updated_modifier->status, 'Status should match the updated value.' );

		// Step 5: Delete the modifier.
		$_GET = [
			'action'      => 'delete_modifier',
			'modifier_id' => $modifier_id,
			'modifier'    => 'fee',
			'_wpnonce'    => wp_create_nonce( 'delete_modifier_' . $modifier_id ),
		];
		$_REQUEST = $_GET; // phpcs:ignore WordPress.Security.NonceVerification

		$controller->handle_delete_modifier();

		// Validate the redirection URL after deletion.
		$this->assertNotNull( $redirect_url, 'Redirect URL should not be null after deletion.' );
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $query_params );
		$this->assertEquals( 'success', $query_params['deleted'] ?? null, 'Modifier should be successfully deleted.' );

		// Step 6: Confirm the modifier was deleted.
		try {
			$deleted_modifier = $modifier_repository->find_by_id( $modifier_id );
			$this->fail( 'Expected Not_Found_Exception was not thrown.' );
		} catch ( Not_Found_Exception $exception ) {
			$this->assertEquals(
				'Order Modifier not found.',
				$exception->getMessage(),
				'Expected exception message does not match.'
			);
		}
	}
}
