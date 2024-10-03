<?php

namespace TEC\Tickets\Seating;

use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\Uplink\Resources\License;
use TEC\Tickets\Seating\Service\Service;
use WP_Error;

class Health_Test extends Controller_Test_Case {
	use WP_Send_Json_Mocks;
	use With_Uopz;
	use WP_Remote_Mocks;
	use SnapshotAssertions;

	private ?int $user = null;

	protected string $controller_class = Health::class;

	protected const NONCE_ACTION = 'health-check-site-status';

	/**
	 * @test
	 */
	public function it_should_add_slr_tests() {
		$controller = $this->make_controller();
		$controller->register();

		$tests = $controller->get_tests();

		$this->assertTrue( is_array( $tests ) );
		$this->assertNotEmpty( $tests );

		$tests_without_extra = array_map( function ( $test ) {
			unset( $test['extra'] );
			return $test;
		}, $tests );

		$expected = [
			'async' => array_combine( wp_list_pluck( $tests_without_extra, 'test' ), array_values( $tests_without_extra ) ),
		];

		$this->assertEquals( $expected, apply_filters( 'site_status_tests', [] ) );
	}

	/**
	 * @test
	 */
	public function it_should_fail_when_no_nonce() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$this->expectException( \Exception::class );
		// Fail because no nonce.
		$controller->check_slr_valid_license();
	}

	/**
	 * @test
	 */
	public function it_should_check_slr_valid_license_with_invalid_license() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		// Fail because not valid license.
		$controller->check_slr_valid_license();

		$this->assertTrue( $wp_send_json_error->was_called() );
		$this->assertFalse( $wp_send_json_success->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_error->get_pretty_arguments() );
	}

	/**
	 * @test
	 */
	public function it_should_check_slr_valid_license() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		$this->set_valid_license_for_slr();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		// Success.
		$controller->check_slr_valid_license();
		$this->assertTrue( $wp_send_json_success->was_called() );
		$this->assertFalse( $wp_send_json_error->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_success->get_pretty_arguments() );
	}

	/**
	 * @test
	 */
	public function it_should_check_slr_can_see_sass_when_sass_unavailable() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		$this->set_class_fn_return( Service::class, 'check_connection', false );

		$controller->check_slr_can_see_sass();

		$this->assertTrue( $wp_send_json_error->was_called() );
		$this->assertFalse( $wp_send_json_success->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_error->get_pretty_arguments() );
	}

	/**
	 * @test
	 */
	public function it_should_check_slr_can_see_sass_when_sass_available() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		$this->set_class_fn_return( Service::class, 'check_connection', true );

		$controller->check_slr_can_see_sass();

		$this->assertTrue( $wp_send_json_success->was_called() );
		$this->assertFalse( $wp_send_json_error->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_success->get_pretty_arguments() );
	}

	/**
	 * @test
	 */
	public function it_should_check_ajax_rate_when_rate_is_not_enough() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		$test = $controller->get_tests()['slr_ajax_rate'];

		$action = 'tec-site-health-test-' . $test['test'];

		$this->set_fn_return( 'wp_create_nonce', '12345678' );
		$this->set_fn_return( 'wp_verify_nonce', true );
		$this->set_fn_return( 'usleep', true );
		$this->set_fn_return( 'wp_safe_remote_get', static fn( $url, $args ) => wp_remote_get( $url, $args ), true );

		$this->mock_wp_remote(
			'get',
			add_query_arg(
				[
					'action' => rawurlencode( $action ),
					'nonce'  => '12345678',
				],
				admin_url( '/admin-ajax.php' )
			),
			[
				'timeout' => 1,
				'headers' => [
					'Cookie' => $_SERVER['HTTP_COOKIE'] ?? '',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 400,
					],
					'body'     => wp_json_encode(
						[
							'data' => [
								'success' => false,
							],
						]
					),
				];
			}
		);

		$controller->check_slr_ajax_rate();

		$this->assertTrue( $wp_send_json_error->was_called() );
		$this->assertFalse( $wp_send_json_success->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_error->get_pretty_arguments() );
	}

	/**
	 * @test
	 */
	public function it_should_check_ajax_rate_when_request_returns_wp_error() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		$test = $controller->get_tests()['slr_ajax_rate'];

		$action = 'tec-site-health-test-' . $test['test'];

		$this->set_fn_return( 'wp_create_nonce', '12345678' );
		$this->set_fn_return( 'wp_verify_nonce', true );
		$this->set_fn_return( 'usleep', true );
		$this->set_fn_return( 'wp_safe_remote_get', static fn( $url, $args ) => wp_remote_get( $url, $args ), true );

		$this->mock_wp_remote(
			'get',
			add_query_arg(
				[
					'action' => rawurlencode( $action ),
					'nonce'  => '12345678',
				],
				admin_url( '/admin-ajax.php' )
			),
			[
				'timeout' => 1,
				'headers' => [
					'Cookie' => $_SERVER['HTTP_COOKIE'] ?? '',
				],
			],
			function () {
				return new WP_Error( 403, 'Forbidden' );
			}
		);

		$controller->check_slr_ajax_rate();

		$this->assertTrue( $wp_send_json_error->was_called() );
		$this->assertFalse( $wp_send_json_success->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_error->get_pretty_arguments() );
	}

	/**
	 * @test
	 */
	public function it_should_check_ajax_rate_when_rate_is_enough() {
		$test_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $test_user );

		$controller = $this->make_controller();
		$controller->register();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		$test = $controller->get_tests()['slr_ajax_rate'];

		$action = 'tec-site-health-test-' . $test['test'];

		$this->set_fn_return( 'wp_create_nonce', '12345678' );
		$this->set_fn_return( 'wp_verify_nonce', true );
		$this->set_fn_return( 'usleep', true );
		$this->set_fn_return( 'wp_safe_remote_get', static fn( $url, $args ) => wp_remote_get( $url, $args ), true );

		$this->mock_wp_remote(
			'get',
			add_query_arg(
				[
					'action' => rawurlencode( $action ),
					'nonce'  => '12345678',
				],
				admin_url( '/admin-ajax.php' )
			),
			[
				'timeout' => 1,
				'headers' => [
					'Cookie' => $_SERVER['HTTP_COOKIE'] ?? '',
				],
			],

			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'data' => [
								'success' => true,
							],
						]
					),
				];
			}
		);

		$controller->check_slr_ajax_rate();

		$this->assertTrue( $wp_send_json_success->was_called() );
		$this->assertFalse( $wp_send_json_error->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_success->get_pretty_arguments() );
	}

	private function set_valid_license_for_slr() {
		$this->set_class_fn_return( License::class, 'is_valid', true );
	}
}
