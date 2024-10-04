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
	 * Service_Status constructor.
	 *
	 * @since TBD
	 *
	 * @param string   $backend_base_url The base URL of the service from the site backend.
	 * @param int|null $status           The status of the service.
	 *
	 * @throws \InvalidArgumentException If the status is not one of the valid statuses.
	 */
	public function __construct( string $backend_base_url, int $status = null ) {
		$this->backend_base_url = $backend_base_url;

		if (
			null !== $status
			&& ! in_array( $status, [ self::OK, self::SERVICE_DOWN, self::NOT_CONNECTED, self::INVALID_LICENSE ], true )
		) {
			/*
			 * While it should not be cached directly, the status could be built from client code during a cache read,
			 * for this reason do not freak out on invalid status, just do not consider it.
			 */
			$status = null;
		}

		$this->status = $status;
	}

	/**
	 * Updates the status of the service with a check of the license, token and the connection to the service.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function update(): void {
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
		$this->update();

		return $this->status === self::OK;
	}

	/**
	 * Returns the status of the service.
	 *
	 * @since TBD
	 *
	 * @return int The status of the service as an integer, one of the `self::*` constants.
	 */
	public function get_status(): int {
		$this->update();

		return $this->status;
	}

	/**
	 * Returns the status of the service as a string.
	 *
	 * @since TBD
	 *
	 * @return string The status of the service as a string.
	 */
	public function get_status_string(): string {
		$this->update();

		switch ( $this->status ) {
			case self::OK:
				return 'ok';
			case self::SERVICE_DOWN:
				return 'down';
			case self::NOT_CONNECTED:
				return 'not-connected';
			case self::INVALID_LICENSE:
				return 'invalid-license';
		}
	}

	/**
	 * Returns the URI to connect to the service.
	 *
	 * @since TBD
	 *
	 * @return string The URI to connect to the service.
	 */
	public function get_connnect_url(): string {
		return admin_url( 'admin.php?page=tec-tickets-settings&tab=licenses' );
	}
}