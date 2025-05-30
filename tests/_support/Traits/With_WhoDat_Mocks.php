<?php

namespace Tribe\Tickets\Test\Traits;

use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_WhoDat;
use RuntimeException;

trait With_WhoDat_Mocks {
	use With_Uopz;

	/**
	 * Mock the methods of the WhoDat class.
	 *
	 * @before
	 *
	 * @return void
	 */
	public function mock_methods() {
		$auth_url = $this->get_mock_auth_url();
		$this->set_class_fn_return( Abstract_WhoDat::class, 'get_with_cache', function ( $endpoint, $args = [] ) use ( $auth_url ) {
			if ( $endpoint === 'oauth/authorize' ) {
				return [
					'auth_url' => $auth_url,
				];
			}

			throw new RuntimeException( 'Not mocked endpoint: ' . $endpoint );
		}, true );

		$this->set_class_fn_return( Abstract_WhoDat::class, 'post', function ( $endpoint, $query_args = [], $request_arguments = [] ) {
			if ( $endpoint === 'oauth/token/revoke' ) {
				return [];
			}

			throw new RuntimeException( 'Not mocked endpoint: ' . $endpoint );
		}, true );
	}

	protected function get_mock_auth_url(): string {
		return 'https://tests.com/auth';
	}
}
