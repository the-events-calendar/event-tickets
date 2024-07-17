<?php
/**
 * Provides methods to mock `wp_remove_` functions in tests using the `uopz` extension.
 *
 * @since   TBD
 *
 * @package Traits;
 */

namespace Tribe\Tickets\Test\Traits;

use Generator;
use PHPUnit\Framework\Assert;

class WP_Remote_Mock_Spy {
	private string $name;
	private string $method;
	private string $url;
	private array $calls = [];
	private $expects_calls = true;
	private bool $was_verified = false;

	public function __construct( string $method, string $url ) {
		$this->name   = 'wp_remote_' . strtolower( $method );
		$this->method = strtoupper( $method );
		$this->url    = $url;
	}

	public function register_call( array $args ): void {
		$this->calls[] = [ $args, microtime( true ) ];
	}

	public function expects_calls(): bool {
		return $this->expects_calls;
	}

	public function was_called(): bool {
		$this->was_verified = true;

		return count( $this->calls ) > 0;
	}

	public function was_verified(): bool {
		return $this->was_verified;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_url(): string {
		return $this->url;
	}

	public function get_method(): string {
		return $this->method;
	}
}

/**
 * Class WP_Request_Mocking.
 *
 * @since   TBD
 *
 * @package Traits;
 */
trait WP_Remote_Mocks {
	/**
	 * A map from the hash of the mock function to the calls that were made to it.
	 *
	 * @var WP_Remote_Mock_Spy[]
	 */
	private array $wp_remote_spies = [];

	/**
	 * Mocks a `wp_remote_` function based on the URL.
	 *
	 * Note this function will not throw an exception if the mock is never called.
	 *
	 * @since TBD
	 *
	 * @param string                       $type                    The type of function to mock, e.g. `post` will mock `wp_remote_post`.
	 * @param string                       $mock_url                The URL to mock requests for; requests that do not match this will not
	 *                                                              be mocked. Requests for another URL will be passed through to the
	 *                                                              original function.
	 * @param array<string,mixed>|callable $expected_args           The set of arguments to check against the request.
	 *                                                              This does not have to be a comprehensive list of all
	 *                                                              arguments, but it should be enough to cover the ones that are
	 *                                                              relevant to the test. If the callable returns a Generator, it will
	 *                                                              be called to get the expected arguments at each step.
	 * @param mixed                        $mock_response           The response to return for the mocked request; it can be a WP_Error
	 *                                                              to simulate an HTTP API failure. If the callable
	 *                                                              returns a Generator, it will be called to get the response at each
	 *                                                              step.
	 *
	 * @return WP_Remote_Mock_Spy The spy object that can be used to assert the calls.
	 *
	 * @throws \ReflectionException
	 */
	protected function mock_wp_remote( string $type, string $mock_url, $expected_args, $mock_response ): object {
		// Extract the expected arguments' generator.
		if (
			is_callable( $expected_args )
			&& ( $return_type = ( new \ReflectionFunction( $expected_args ) )->getReturnType() )
			&& $return_type->getName() === Generator::class
		) {
			$expected_args = $expected_args();
		}

		// Extract the mock response generator.
		if (
			is_callable( $mock_response )
			&& ( $return_type = ( new \ReflectionFunction( $mock_response ) )->getReturnType() )
			&& $return_type->getName() === Generator::class
		) {
			$mock_response = $mock_response();
		}

		// Pass a pointer to a pointer to get around the unbound context in the `uopz` use of Closures.
		$spy = new WP_Remote_Mock_Spy( $type, $mock_url );

		$mock = function ( string $url, array $args ) use ( $mock_url, $expected_args, $mock_response, &$spy ) {
			if ( $url !== $mock_url ) {
				return wp_remote_post( $url, $args );
			}

			$compare_args = $expected_args;
			if ( is_callable( $expected_args ) ) {
				$compare_args = $expected_args( $args );
			} elseif ( $expected_args instanceof \Generator ) {
				$compare_args = $expected_args->current();
				$expected_args->next();
			}

			foreach ( $compare_args as $key => $value ) {
				Assert::assertEquals( $value, $args[ $key ], 'Argument ' . $key . ' does not match.' );
			}

			$current_mock_response = $mock_response;
			if ( is_callable( $mock_response ) ) {
				$current_mock_response = $mock_response();
			} elseif ( $mock_response instanceof \Generator ) {
				$current_mock_response = $mock_response->current();
				$mock_response->next();
			}

			$spy->register_call( $args );

			return $current_mock_response;
		};

		$this->wp_remote_spies[] = $spy;

		$this->set_fn_return( "wp_remote_{$type}", $mock, true );

		return $spy;
	}

	/**
	 * @after
	 */
	public function assert_wp_remote_mocks_post_conditions(): void {
		foreach ( $this->wp_remote_spies as $spy ) {
			if ( ! $spy->was_verified() && $spy->expects_calls() ) {
				$this->assertTrue( $spy->was_called(),
					sprintf(
						"The %s mock function for [%s %s] was not called.",
						$spy->get_name(),
						$spy->get_method(),
						$spy->get_url(),
					)
				);
			}
		}

		$this->wp_remote_spies = [];
	}
}