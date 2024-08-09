<?php

namespace slr_integration;

use TEC\Tickets\Seating\Admin\Ajax;
use TEC\Tickets\Seating\Ajax_Methods;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;

class Ajax_Checks_Test extends \Codeception\TestCase\WPTestCase {
	use Ajax_Methods;
	use WP_Send_Json_Mocks;
	use With_Uopz;

	private ?int $user = null;

	/**
	 * @before
	 * @after
	 */
	public function clean_up(): void {
		unset( $_REQUEST['_ajax_nonce'] );
		$this->user = null;
	}


	private function setup_valid_request(): void {
		$this->user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->user );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		wp_set_current_user( 0 );
	}

	public function test_check_current_ajax_user_can_sends_error_if_nonce_missing(): void {
		$this->setup_valid_request();
		unset( $_REQUEST['_ajax_nonce'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		$this->assertFalse( $this->check_current_ajax_user_can( 'manage_options' ) );
		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1,
			[
				'error' => 'Nonce verification failed',
			],
			401
		) );
	}

	public function test_check_current_ajax_user_can_sends_error_if_nonce_not_for_action(): void {
		$this->setup_valid_request();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'test-action' );
		$wp_send_json_error      = $this->mock_wp_send_json_error();

		$this->assertFalse( $this->check_current_ajax_user_can( 'manage_options' ) );
		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1,
			[
				'error' => 'Nonce verification failed',
			],
			401
		) );
	}

	public function test_check_current_ajax_user_can_sends_error_if_nonce_for_different_user(): void {
		$this->setup_valid_request();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		wp_set_current_user( $this->user );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		$this->assertFalse( $this->check_current_ajax_user_can( 'manage_options' ) );
		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1,
			[
				'error' => 'Nonce verification failed',
			],
			401
		) );
	}

	public function test_check_current_ajax_user_can_sends_error_if_user_cant(): void {
		$this->setup_valid_request();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$wp_send_json_error      = $this->mock_wp_send_json_error();

		$this->assertFalse( $this->check_current_ajax_user_can( 'manage_options' ) );
		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1,
			[ 'error' => 'You do not have permission to perform this action.', ],
			403 )
		);
	}

	public function test_check_current_ajax_user_can_sends_error_if_user_cant_with_capability_args(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$private_post = self::factory()->post->create( [ 'post_type' => 'post', 'post_status' => 'private' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->setup_valid_request();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$wp_send_json_error      = $this->mock_wp_send_json_error();

		$this->assertFalse( $this->check_current_ajax_user_can( 'read_post', $private_post ) );
		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1, [
			'error' => 'You do not have permission to perform this action.',
		], 403 ) );
	}

	public function test_check_current_ajax_user_can_success(): void {
		$this->setup_valid_request();
		wp_set_current_user( $this->user );

		$this->assertTrue( $this->check_current_ajax_user_can( 'manage_options' ) );
	}
}
