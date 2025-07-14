<?php
/**
 * A value object representing the status of the Seating Service.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Service;
 */

namespace TEC\Tickets\Seating\Service;

use InvalidArgumentException;
use function TEC\Common\StellarWP\Uplink\get_resource;

/**
 * Class Service_Status.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Service;
 */
class Service_Status {
	use OAuth_Token;

	/**
	 * A constant representing that the connection to the service is established.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	public const OK = 0;

	/**
	 * A constant representing the fact that the service is not responding.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	public const SERVICE_UNREACHABLE = 2;

	/**
	 * A constant representing the fact that the site is not connected to the service.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	public const NOT_CONNECTED = 4;

	/**
	 * A constant representing the fact that the site is connected to the service but the license is invalid.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	public const INVALID_LICENSE = 8;

	/**
	 * A constant representing the fact that the site is connected to the service but the license is expired.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	public const EXPIRED_LICENSE = 16;

	/**
	 * A constant representing the fact that there is no license.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	public const NO_LICENSE = 32;

	/**
	 * The base URL of the service from the site backend.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $backend_base_url;

	/**
	 * The status of the service, a combination of the constants (flags) above.
	 *
	 * @since 5.16.0
	 *
	 * @var int|null
	 */
	private ?int $status = null;

	/**
	 * The context of the status check.
	 *
	 * @since 5.17.0
	 *
	 * @var string
	 */
	private string $context;

	/**
	 * Service_Status constructor.
	 *
	 * @since 5.16.0
	 * @since 5.17.0 Added the `$context` argument.
	 *
	 * @param string      $backend_base_url The base URL of the service from the site backend.
	 * @param int|null    $status           The status of the service.
	 * @param string|null $context          The context of the service status check.
	 *
	 * @throws InvalidArgumentException If the status is not one of the valid statuses.
	 */
	public function __construct( string $backend_base_url, int $status = null, string $context = 'admin' ) {
		$this->backend_base_url = $backend_base_url;
		$this->context          = $context;

		if (
			null !== $status
			&& ! in_array( $status, [ self::OK, self::SERVICE_UNREACHABLE, self::NOT_CONNECTED, self::INVALID_LICENSE, self::EXPIRED_LICENSE ], true )
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
	 * @since 5.16.0
	 *
	 * @return void
	 */
	private function update(): void {
		if ( $this->status !== null ) {
			return;
		}

		$transient  = $this->get_transient_name();
		$expiration = 60;

		$cached = get_transient( $transient );

		if ( in_array(
			$cached,
			[
				self::OK,
				self::SERVICE_UNREACHABLE,
				self::NOT_CONNECTED,
				self::INVALID_LICENSE,
				self::EXPIRED_LICENSE,
				self::NO_LICENSE,
			],
			true
		) ) {
			$this->status = $cached;

			return;
		}

		$resource = get_resource( 'tec-seating' );

		if ( $this->has_no_license() ) {
			$this->status = self::NO_LICENSE;
			set_transient( $transient, $this->status, $expiration );

			return;
		}

		if ( ! $resource->has_valid_license() ) {
			if ( $resource->get_license_object()->is_expired() ) {
				// There is a license key, but it is expired.
				$this->status = self::EXPIRED_LICENSE;
				set_transient( $transient, $this->status, $expiration );

				return;
			}

			// There is a license key, but it is invalid and NOT expired.
			$this->status = self::INVALID_LICENSE;
			set_transient( $transient, $this->status, $expiration );

			return;
		}

		$token = $resource->get_token();

		if ( empty( $token ) ) {
			$this->status = self::NOT_CONNECTED;
			set_transient( $transient, $this->status, $expiration );

			return;

		}

		if ( in_array( $this->context, [ 'admin', 'rest' ], true ) ) {
			// Do not run the HEAD check in admin and REST context.
			$this->status = self::OK;
			set_transient( $transient, self::OK, $expiration );

			return;
		}

		// Check if the service is running with a quick HEAD request.
		$response = wp_remote_head( $this->backend_base_url, [] );

		if ( is_wp_error( $response ) ) {
			$this->status = self::SERVICE_UNREACHABLE;
			set_transient( $transient, $this->status, $expiration );

			return;
		}

		$this->status = self::OK;
	}

	/**
	 * Returns whether the service status is OK or not.
	 *
	 * @since 5.16.0
	 *
	 * @return bool  Whether the service status is OK or not.
	 */
	public function is_ok(): bool {
		$this->update();

		return $this->status === self::OK;
	}

	/**
	 * Returns whether the license used is invalid.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the service status is Invalid License or not.
	 */
	public function is_license_invalid(): bool {
		$this->update();

		return $this->status === self::INVALID_LICENSE;
	}

	/**
	 * Returns whether the site is not connected to the service.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the service status is Not Connected or not.
	 */
	public function has_no_license(): bool {
		return ! get_resource( 'tec-seating' )->get_license_object()->get_key();
	}

	/**
	 * Returns whether the license used is expired.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the service status is Expired License or not.
	 */
	public function is_license_expired(): bool {
		$this->update();

		return $this->status === self::EXPIRED_LICENSE;
	}

	/**
	 * Returns the status of the service.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
	 *
	 * @return string The status of the service as a string.
	 */
	public function get_status_string(): string {
		$this->update();

		switch ( $this->status ) {
			case self::SERVICE_UNREACHABLE:
				return 'down';
			case self::NOT_CONNECTED:
				return 'not-connected';
			case self::EXPIRED_LICENSE:
				return 'expired-license';
			case self::INVALID_LICENSE:
				return 'invalid-license';
			case self::NO_LICENSE:
				return 'no-license';
			default:
				return 'ok';
		}
	}

	/**
	 * Returns the URI to connect to the service.
	 *
	 * @since 5.16.0
	 *
	 * @return string The URI to connect to the service.
	 */
	public function get_connect_url(): string {
		return admin_url( 'admin.php?page=tec-tickets-settings&tab=licenses' );
	}

	/**
	 * Returns the service status transient name, independent of the context.
	 *
	 * @since 5.17.0
	 *
	 * @return string The service status transient name.
	 */
	public function get_transient_name(): string {
		return 'tec_tickets_seating_service_status';
	}

	/**
	 * Returns the context of this service status check.
	 *
	 * @since 5.17.0
	 *
	 * @return string The context of this service status check.
	 */
	public function get_context(): string {
		return $this->context;
	}
}
