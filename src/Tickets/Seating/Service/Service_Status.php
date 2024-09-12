<?php
/**
 * A value object representing the status of the Seating Service.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Service;
 */

namespace TEC\Tickets\Seating\Service;

use function TEC\Common\StellarWP\Uplink\get_resource;

/**
 * Class Service_Status.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Service;
 */
class Service_Status {
	use OAuth_Token;

	/**
	 * A constant representing a generic error status in the connection to the service.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const OK = 0;

	/**
	 * A constant representing the fact that the service is not responding.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const SERVICE_DOWN = 2;

	/**
	 * A constant representing the fact that the site is not connected to the service.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const NOT_CONNECTED = 4;

	/**
	 * A constant representing the fact that the site is connected to the service but the license is invalid.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const INVALID_LICENSE = 8;

	/**
	 * The base URL of the service from the site backend.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $backend_base_url;

	/**
	 * The status of the service, a combination of the constants (flags) above.
	 *
	 * @since TBD
	 *
	 * @var int|null
	 */
	private ?int $status = null;

	/**
	 * Returns the Service Status instance.
	 *
	 * Note the status is memoized and will only be rebuilt if the `$force` parameter is set to `true`.
	 * The returned instance is shared among all code that holds a reference to it. Calling methods that
	 * affect the status will affect the same instance.
	 *
	 * @since TBD
	 *
	 * @param string $backend_base_url The base URL of the service from the site backend.
	 * @param bool   $force            Whether to force the rebuilding of the status for this request
	 *                                 or not.
	 *
	 * @return Service_Status The Service Status instance.
	 */
	public static function build( string $backend_base_url, bool $force = false ): Service_Status {
		$cache     = tribe_cache();
		$cache_key = 'tec_tickets_seating_service_status_' . $backend_base_url;
		$status    = $cache[ $cache_key ] ?? null;

		if ( ! $force && $status && $status instanceof Service_Status ) {
			return $status;
		}

		$status = new self( $backend_base_url );

		$cache[ $cache_key ] = $status;

		return $status;
	}

	public function __construct( string $backend_base_url ) {
		$this->backend_base_url = $backend_base_url;
		$this->status           = null;
	}

	/**
	 * Updates the status of the service with a check of the license, token and the connection to the service.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function update_status(): void {
		if ( $this->status !== null ) {
			return;
		}

		$resource = get_resource( 'tec-seating' );

		if ( ! $resource->has_valid_license() ) {
			// There is a license key, but it is not valid or expired.
			$this->status = self::INVALID_LICENSE;

			return;
		}

		$token = $resource->get_token();

		if ( empty( $token ) ) {
			$this->status = self::NOT_CONNECTED;

			return;

		}

		// Check if the service is running with a quick HEAD request.
		$response = wp_remote_head( $this->backend_base_url );

		if ( is_wp_error( $response ) ) {
			$this->status = self::SERVICE_DOWN;

			return;
		}

		$this->status = self::OK;
	}

	/**
	 * Returns whether the service status is OK or not.
	 *
	 * @since TBD
	 *
	 * @return bool  Whether the service status is OK or not.
	 */
	public function is_ok(): bool {
		$this->update_status();

		return $this->status === self::OK;
	}

	public function get_status():int {
		$this->update_status();

		return $this->status;
	}
}
