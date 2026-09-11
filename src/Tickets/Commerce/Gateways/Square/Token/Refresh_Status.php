<?php
/**
 * Stores the outcome of the Square access token refresh attempts.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Token;

use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use Tribe__Date_Utils as Dates;

/**
 * Class Refresh_Status
 *
 * The record of how the access token refresh has been going: when it last ran, how many attempts in a
 * row have failed, and whether Square has refused to renew the credentials outright. Storage only -
 * what to do about any of it belongs to Refresh_Policy, and announcing it to Token_Refresher.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */
final class Refresh_Status {
	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Refresh_Status constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant $merchant Merchant instance.
	 */
	public function __construct( Merchant $merchant ) {
		$this->merchant = $merchant;
	}

	/**
	 * Returns the outcome of the last access token refresh attempt.
	 *
	 * The timestamps are UTC, so that they compare correctly against strtotime(), which WordPress pins
	 * to UTC whatever the site timezone is.
	 *
	 * @since TBD
	 *
	 * @return array{invalid_at: string, error: int, failures: int, last_attempt_at: string, first_failure_at: string}
	 */
	public function get(): array {
		$status = get_option( $this->get_option_key(), [] );

		if ( ! is_array( $status ) ) {
			$status = [];
		}

		return array_merge(
			[
				'invalid_at'       => '',
				'error'            => 0,
				'failures'         => 0,
				'last_attempt_at'  => '',
				'first_failure_at' => '',
			],
			$status
		);
	}

	/**
	 * Returns how many consecutive refresh attempts have failed.
	 *
	 * @since TBD
	 *
	 * @return int Zero when no failure has been recorded.
	 */
	public function get_failure_count(): int {
		return absint( $this->get()['failures'] );
	}

	/**
	 * Returns when the last refresh was attempted.
	 *
	 * @since TBD
	 *
	 * @return ?int A UTC timestamp, or null when no attempt has been recorded.
	 */
	public function get_last_attempt_timestamp(): ?int {
		return $this->read_timestamp( 'last_attempt_at' );
	}

	/**
	 * Returns when the current run of failures started.
	 *
	 * @since TBD
	 *
	 * @return ?int A UTC timestamp, or null when no failure has been recorded.
	 */
	public function get_first_failure_timestamp(): ?int {
		return $this->read_timestamp( 'first_failure_at' );
	}

	/**
	 * Whether Square has refused to renew the stored credentials.
	 *
	 * @since TBD
	 *
	 * @return bool True once a permanent failure has been recorded.
	 */
	public function is_invalid(): bool {
		return ! empty( $this->get()['invalid_at'] );
	}

	/**
	 * Records that a refresh was attempted, whatever came of it.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function record_attempt(): void {
		$this->update( [ 'last_attempt_at' => $this->now() ] );
	}

	/**
	 * Clears the failure bookkeeping after the credentials were renewed.
	 *
	 * The last attempt survives: it is the only throttle a connection with no known expiration has.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function record_success(): void {
		$this->update(
			[
				'invalid_at'       => '',
				'error'            => 0,
				'failures'         => 0,
				'first_failure_at' => '',
			]
		);
	}

	/**
	 * Marks the connection as one Square will not renew.
	 *
	 * @since TBD
	 *
	 * @param int $code The response code the refresh came back with.
	 *
	 * @return void
	 */
	public function record_permanent_failure( int $code ): void {
		$this->update(
			[
				'invalid_at' => $this->now(),
				'error'      => $code,
			]
		);
	}

	/**
	 * Counts a failure that may well clear up on its own.
	 *
	 * @since TBD
	 *
	 * @return int The number of consecutive failures after this one.
	 */
	public function record_transient_failure(): int {
		$status   = $this->get();
		$failures = absint( $status['failures'] ) + 1;

		$this->update(
			[
				'failures'         => $failures,
				'first_failure_at' => $status['first_failure_at'] ?: $this->now(),
			]
		);

		return $failures;
	}

	/**
	 * Deletes the stored status.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the option was deleted.
	 */
	public function delete(): bool {
		return delete_option( $this->get_option_key() );
	}

	/**
	 * Drops the object cache entry backing the status.
	 *
	 * Used to re-read the status from the database after another process may have written it.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function flush_cache(): void {
		wp_cache_delete( $this->get_option_key(), 'options' );
	}

	/**
	 * Merges data into the stored status.
	 *
	 * The single write path: the record_* methods above are the transitions this class knows how to
	 * name, and this is the general form they specialize, matching Merchant::update() next door.
	 *
	 * @since TBD
	 *
	 * @param array{invalid_at?: string, error?: int, failures?: int, last_attempt_at?: string, first_failure_at?: string} $data The status data to merge in.
	 *
	 * @return bool Whether the option was written.
	 */
	public function update( array $data ): bool {
		return update_option( $this->get_option_key(), array_merge( $this->get(), $data ), false );
	}

	/**
	 * The option key holding the status.
	 *
	 * Scoped to the gateway mode so the sandbox and live connections never share state.
	 *
	 * @since TBD
	 *
	 * @return string The option key, scoped to the gateway mode.
	 */
	private function get_option_key(): string {
		$gateway_key = Gateway::get_key();
		$mode        = $this->merchant->get_mode();

		return "tec_tickets_commerce_{$gateway_key}_token_status_{$mode}";
	}

	/**
	 * Reads one of the stored dates as a timestamp.
	 *
	 * @since TBD
	 *
	 * @param string $key The status key to read.
	 *
	 * @return ?int A UTC timestamp, or null when nothing usable is stored under that key.
	 */
	private function read_timestamp( string $key ): ?int {
		$stored = $this->get()[ $key ];

		if ( ! is_string( $stored ) || '' === $stored ) {
			return null;
		}

		$timestamp = strtotime( $stored );

		return false === $timestamp ? null : $timestamp;
	}

	/**
	 * The current moment, in the format the stored timestamps use.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	private function now(): string {
		return Dates::build_date_object( 'now', 'UTC' )->format( Dates::DBDATETIMEFORMAT );
	}
}
