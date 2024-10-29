<?php
/**
 * Fetches and updates data from the service.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use Exception;
use TEC\Tickets\Seating\Logging;

/**
 * Class Updater.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */
class Updater {
	use OAuth_Token;
	use Logging;

	/**
	 * The name of the transient to use to store the last update time.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $transient;

	/**
	 * The expiration time in seconds.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	private int $expiration;

	/**
	 * Whether the data should be updated from the service or not.
	 *
	 * @since 5.16.0
	 *
	 * @var bool
	 */
	private bool $should_update_from_service;

	/**
	 * The URL to the service used to fetch the items from the backend.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $fetch_url;

	/**
	 * Updater constructor.
	 *
	 * since 5.16.0
	 *
	 * @param string $fetch_url  The URL to fetch the services URL.
	 * @param string $transient  The name of the transient to use to store the last update time.
	 * @param int    $expiration The expiration time in seconds.
	 */
	public function __construct( string $fetch_url, string $transient, int $expiration ) {
		$this->fetch_url  = $fetch_url;
		$this->transient  = $transient;
		$this->expiration = $expiration;
	}


	/**
	 * Checks if the data should be updated from the service or not.
	 *
	 * @since 5.16.0
	 *
	 * @param bool $force If true, the data will be updated even if they are up-to-date.
	 *
	 * @return $this The instance of the Updater, for chaining.
	 */
	public function check_last_update( bool $force ): self {
		$last_update = get_transient( $this->transient );

		$this->should_update_from_service =
			$force
			|| ! $last_update
			|| ! is_numeric( $last_update )
			|| time() - $last_update >= $this->expiration;

		return $this;
	}

	/**
	 * Updates the data from the service.
	 *
	 * @since 5.16.0
	 *
	 * @param callable $before_update The callback to use to prepare the tables for the update.
	 *
	 * @return $this The instance of the Updater, for chaining.
	 */
	public function update_from_service( callable $before_update ): self {
		if ( ! $this->should_update_from_service ) {
			return $this;
		}

		try {
			$before_update();
		} catch ( Exception $e ) {
			$this->log_error(
				'Preparing for the update from the service.',
				[
					'source' => __METHOD__,
					'error'  => $e->getMessage(),
				]
			);
		}

		return $this;
	}

	/**
	 * Stores the fetched data from the service with the provided callback.
	 *
	 * @since 5.16.0
	 *
	 * @param callable $insert The callback to use to insert the data into the database.
	 *
	 * @return bool Whether the data was stored or not.
	 */
	public function store_fetched_data( callable $insert ): bool {
		if ( ! $this->should_update_from_service ) {
			return true;
		}

		$next = null;
		do {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$response = wp_remote_get(
				add_query_arg(
					[
						'from' => $next,
					],
					$this->fetch_url
				),
				[
					'headers' => [
						'Accept'        => 'application/json',
						'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
					],
				]
			);

			$code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $code ) {
				$this->log_error(
					'Fetching items from service.',
					[
						'source' => __METHOD__,
						'code'   => $code,
					]
				);

				return false;
			}

			$body = wp_remote_retrieve_body( $response );

			try {
				$decoded = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
			} catch ( \JsonException $e ) {
				$this->log_error(
					'Decoding the service response body.',
					[
						'source' => __METHOD__,
						'body'   => substr( $body, 0, 100 ),
					]
				);

				return false;
			}

			if ( ! (
				$decoded
				&& is_array( $decoded )
				&& isset( $decoded['data'], $decoded['data']['items'], $decoded['data']['next'] )
			) ) {
				$this->log_error(
					'Malformed response body from service.',
					[
						'source' => __METHOD__,
						'body'   => substr( $body, 0, 100 ),
					]
				);

				return false;
			}

			try {
				$insert( $decoded['data']['items'] );
			} catch ( \Exception $e ) {
				$this->log_error(
					'Inserting the data into the database.',
					[
						'source' => __METHOD__,
						'error'  => $e->getMessage(),
					]
				);

				return false;
			}

			$next = $decoded['data']['next'] ?: null;
		} while ( $next );

		set_transient( $this->transient, time(), $this->expiration );

		return true;
	}
}
