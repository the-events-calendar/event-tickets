<?php
/**
 * The result of a single Square access token refresh attempt.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Token;

/**
 * Class Refresh_Outcome
 *
 * A refresh either produced credentials, ended the connection, or did neither and is worth retrying.
 * Modelled as an object rather than a string constant so that no caller can compare against the wrong
 * one, and so the credentials travel with the verdict that says they exist.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */
final class Refresh_Outcome {
	/**
	 * The refresh response carried a new access token.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const SUCCESS = 'success';

	/**
	 * Square refused the refresh token; only a new OAuth handshake can recover.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const PERMANENT = 'permanent';

	/**
	 * The refresh did not go through, but the credentials may still be good.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const TRANSIENT = 'transient';

	/**
	 * Which of the three verdicts this is.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $verdict;

	/**
	 * The response code the refresh came back with, 0 when the request never completed.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $code;

	/**
	 * The credentials the refresh returned, empty unless it succeeded.
	 *
	 * @since TBD
	 *
	 * @var array{access_token?: string, refresh_token?: string, expires_at?: string, token_type?: string, merchant_id?: string, whodat_signature?: string}
	 */
	private array $credentials;

	/**
	 * The refresh returned new credentials.
	 *
	 * @since TBD
	 *
	 * @param array{access_token?: string, refresh_token?: string, expires_at?: string, token_type?: string, merchant_id?: string, whodat_signature?: string} $credentials The credentials the refresh returned.
	 *
	 * @return self An outcome carrying the new credentials.
	 */
	public static function success( array $credentials ): self {
		return new self( self::SUCCESS, 200, $credentials );
	}

	/**
	 * Square will not renew this connection.
	 *
	 * @since TBD
	 *
	 * @param int $code The response code the refresh came back with.
	 *
	 * @return self An outcome only a new OAuth handshake can clear.
	 */
	public static function permanent( int $code ): self {
		return new self( self::PERMANENT, $code );
	}

	/**
	 * The refresh did not go through, and is worth trying again.
	 *
	 * @since TBD
	 *
	 * @param int $code The response code the refresh came back with, 0 when it never completed.
	 *
	 * @return self An outcome the caller may retry later.
	 */
	public static function transient( int $code ): self {
		return new self( self::TRANSIENT, $code );
	}

	/**
	 * Whether the refresh returned new credentials.
	 *
	 * @since TBD
	 *
	 * @return bool True when the refresh returned new credentials.
	 */
	public function is_success(): bool {
		return self::SUCCESS === $this->verdict;
	}

	/**
	 * Whether Square will not renew this connection.
	 *
	 * @since TBD
	 *
	 * @return bool True when Square will not renew this connection.
	 */
	public function is_permanent(): bool {
		return self::PERMANENT === $this->verdict;
	}

	/**
	 * Whether the refresh is worth trying again.
	 *
	 * @since TBD
	 *
	 * @return bool True when the refresh is worth trying again.
	 */
	public function is_transient(): bool {
		return self::TRANSIENT === $this->verdict;
	}

	/**
	 * Returns the response code the refresh came back with.
	 *
	 * @since TBD
	 *
	 * @return int Zero when the request never completed.
	 */
	public function get_code(): int {
		return $this->code;
	}

	/**
	 * Returns the credentials the refresh returned.
	 *
	 * @since TBD
	 *
	 * @return array{access_token?: string, refresh_token?: string, expires_at?: string, token_type?: string, merchant_id?: string, whodat_signature?: string} Empty unless the refresh succeeded.
	 */
	public function get_credentials(): array {
		return $this->credentials;
	}

	/**
	 * Refresh_Outcome constructor.
	 *
	 * Private: an outcome is built through one of the three named constructors, which is what keeps an
	 * unreachable combination - success with no credentials, say - from being expressible.
	 *
	 * @since TBD
	 *
	 * @param string $verdict     One of the class verdict constants.
	 * @param int    $code        The response code the refresh came back with.
	 * @param array  $credentials The credentials the refresh returned, in the shape get_credentials() documents.
	 */
	private function __construct( string $verdict, int $code, array $credentials = [] ) {
		$this->verdict     = $verdict;
		$this->code        = $code;
		$this->credentials = $credentials;
	}
}
