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

/**
 * Class WP_Request_Mocking.
 *
 * @since   TBD
 *
 * @package Traits;
 */
trait WP_Remote_Mocks {
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
	 * @return void
	 *
	 * @throws \ReflectionException
	 */
	protected function mock_wp_remote( string $type, string $mock_url, $expected_args, $mock_response ): void {
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

		$mock = function ( string $url, array $args ) use ( $mock_url, $expected_args, $mock_response ) {
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

			return $current_mock_response;
		};

		$this->set_fn_return( "wp_remote_{$type}", $mock, true );
	}
}