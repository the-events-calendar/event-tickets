<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Generator;
use Closure;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;
use Tribe\Tickets\Test\Traits\With_WhoDat_Mocks;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;

class Ajax_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use WP_Send_Json_Mocks;
	use With_WhoDat_Mocks;

	protected string $controller_class = Ajax::class;

	/**
	 * @after
	 */
	public function reset(): void {
		wp_set_current_user( 0 );
		$_REQUEST = [];
	}

	/**
	 * @test
	 * @dataProvider ajax_connect_provider
	 */
	public function it_should_provide_onboard_url_when_expected( Closure $fixture ): void {
		$this->make_controller()->register();
		$should_succeed = $fixture();

		$error = $this->mock_wp_send_json_error();
		$success = $this->mock_wp_send_json_success();

		do_action( 'wp_ajax_tec_tickets_commerce_square_connect' );

		if ( ! $should_succeed ) {
			$this->assertTrue(
				$error->was_called_times_with( 1,
					[
						'message' => current_user_can( 'manage_options' ) ? 'Failed to generate connection URL.' : 'You do not have permission to perform this action.',
					],
					current_user_can( 'manage_options' ) ? 400 : 401
				)
			);
			$this->assertFalse( $success->was_called() );
			$this->assertEquals( 1000, Commerce_Settings::get( 'tickets_commerce_gateways_square_remotely_disconnected_%s' ) );
			return;
		}

		$this->assertTrue( $success->was_called_times_with( 1, [ 'url' => $this->get_mock_auth_url() ] ) );
		$this->assertFalse( $error->was_called() );
		$this->assertFalse( Commerce_Settings::get( 'tickets_commerce_gateways_square_remotely_disconnected_%s' ) );
	}

	/**
	 * @test
	 * @dataProvider ajax_disconnect_provider
	 */
	public function it_should_disconnect_the_account_when_expected( Closure $fixture ): void {
		$this->make_controller()->register();
		$should_succeed = $fixture();

		$error = $this->mock_wp_send_json_error();
		$success = $this->mock_wp_send_json_success();

		do_action( 'wp_ajax_tec_tickets_commerce_square_disconnect' );

		if ( ! $should_succeed ) {
			$this->assertTrue(
				$error->was_called_times_with( 1,
					[
						'message' => 'You do not have permission to perform this action.',
					],
					401
				)
			);
			$this->assertFalse( $success->was_called() );
			$this->assertEquals( get_option( tribe( Merchant::class )->get_signup_data_key() ), tec_tickets_tests_get_fake_merchant_data() );
			return;
		}

		$this->assertTrue( $success->was_called_times_with( 1, [ 'message' => __( 'Successfully disconnected from Square.', 'event-tickets' ) ] ) );
		$this->assertFalse( $error->was_called() );
		$this->assertEquals( false, get_option( tribe( Merchant::class )->get_signup_data_key() ) );
	}

	public function ajax_connect_provider(): Generator {
		yield 'guest user' => [ function (): bool {
			wp_set_current_user( 0 );
			Commerce_Settings::set( 'tickets_commerce_gateways_square_remotely_disconnected_%s', 1000 );
			return false;
		} ];

		yield 'editor user' => [ function (): bool {
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
			Commerce_Settings::set( 'tickets_commerce_gateways_square_remotely_disconnected_%s', 1000 );
			return false;
		} ];

		yield 'administrator user - no connect URL' => [ function (): bool {
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
			Commerce_Settings::set( 'tickets_commerce_gateways_square_remotely_disconnected_%s', 1000 );
			$this->set_class_fn_return( WhoDat::class, 'connect_account', '', false );
			return false;
		} ];

		yield 'administrator user - success' => [ function (): bool {
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
			Commerce_Settings::set( 'tickets_commerce_gateways_square_remotely_disconnected_%s', 1000 );
			return true;
		} ];
	}

	public function ajax_disconnect_provider(): Generator {
		yield 'guest user' => [ function (): bool {
			wp_set_current_user( 0 );
			$_REQUEST['_wpnonce'] = wp_create_nonce( tribe( Merchant::class )->get_disconnect_action() );
			return false;
		} ];

		yield 'editor user' => [ function (): bool {
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
			$_REQUEST['_wpnonce'] = wp_create_nonce( tribe( Merchant::class )->get_disconnect_action() );
			return false;
		} ];

		yield 'administrator user' => [ function (): bool {
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
			$_REQUEST['_wpnonce'] = wp_create_nonce( tribe( Merchant::class )->get_disconnect_action() );
			return true;
		} ];
	}
}
