<?php

namespace TEC\Tickets\QR;

use Codeception\TestCase\WPTestCase;
use Exception;
use Tribe\Tests\Traits\With_Uopz;

class Connector_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * The response captured from the handler's terminating wp_send_json_* call.
	 *
	 * Static because uopz replaces the function body without a $this binding, so the
	 * capturing closure cannot write to an instance property.
	 *
	 * @var array{type: string, data: mixed}|null
	 */
	public static ?array $response = null;

	public function setUp(): void {
		parent::setUp();

		self::$response = null;

		// wp_send_json_* end the request with wp_die(); throwing reproduces that halt so the
		// handler cannot run past the point where it rejected (or accepted) the request.
		$capture = static function ( string $type ): callable {
			return static function ( $data = null ) use ( $type ) {
				Connector_Test::$response = [
					'type' => $type,
					'data' => $data,
				];
				throw new Exception( 'wp_send_json halted the request.' );
			};
		};

		$this->set_fn_return( 'wp_send_json_error', $capture( 'error' ), true );
		$this->set_fn_return( 'wp_send_json_success', $capture( 'success' ), true );
	}

	protected function call_handler(): void {
		try {
			tribe( Connector::class )->handle_ajax_generate_api_key();
		} catch ( Exception $e ) {
			// Expected: stands in for wp_die() ending the request.
		}
	}

	/**
	 * @test
	 */
	public function it_should_not_regenerate_the_key_for_a_subscriber_with_a_valid_nonce(): void {
		// Seed a known key so a regeneration would be observable.
		$original_key = tribe( Settings::class )->get_api_key();
		$this->assertNotEmpty( $original_key );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		// A real, valid nonce: the whole point of the report is that a Subscriber can obtain one.
		$_REQUEST['confirm'] = wp_create_nonce( tribe( Connector::class )->get_nonce_key() );

		$this->call_handler();

		unset( $_REQUEST['confirm'] );

		$this->assertSame( 'error', self::$response['type'], 'The Subscriber request should be rejected.' );
		$this->assertSame( 'Permission Error', self::$response['data'] );
		$this->assertSame( $original_key, tribe( Settings::class )->get_api_key(), 'The API key must be left untouched.' );
	}

	/**
	 * @test
	 */
	public function it_should_let_an_administrator_past_the_capability_gate(): void {
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$_REQUEST['confirm'] = wp_create_nonce( tribe( Connector::class )->get_nonce_key() );

		$this->call_handler();

		unset( $_REQUEST['confirm'] );

		// Reaching any step past the gate (here, the key regeneration itself) proves the
		// administrator was not blocked by the capability check the fix added.
		$this->assertNotSame( 'Permission Error', self::$response['data'], 'An administrator must not be blocked by the capability check.' );
	}
}
