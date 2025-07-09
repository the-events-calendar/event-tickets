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
		$controller->define_tests();

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
		$controller->define_tests();

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
		$controller->define_tests();

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
		$controller->define_tests();

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
		$controller->define_tests();

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
		$controller->define_tests();

		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_send_json_success = $this->mock_wp_send_json_success();

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( self::NONCE_ACTION );

		$this->set_class_fn_return( Service::class, 'check_connection', true );

		$controller->check_slr_can_see_sass();

		$this->assertTrue( $wp_send_json_success->was_called() );
		$this->assertFalse( $wp_send_json_error->was_called() );

		$this->assertMatchesJsonSnapshot( $wp_send_json_success->get_pretty_arguments() );
	}

	private function set_valid_license_for_slr() {
		$this->set_class_fn_return( License::class, 'is_valid', true );
	}
}
