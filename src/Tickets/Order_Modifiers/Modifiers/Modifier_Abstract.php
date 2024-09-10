<?php
/**
 * Abstract class for common Order Modifier functionality.
 *
 * This class provides reusable methods and enforces structure for specific order modifier strategies (such as Coupons,
 * Fees).
 *
 * It provides common utility methods like generating slugs, converting between cents and decimals,
 * and interacting with the repository for finding modifiers by ID or slug.
 *
 * Each concrete modifier strategy (like Coupon or Fee) will extend this class and provide its own implementations
 * for sanitizing and validating data.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Order_Modifiers\Modifiers;

use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifiers_Repository;

/**
 * Class Modifier_Abstract
 *
 * Provides a base class for order modifier strategies like Coupon and Fee.
 *
 * @since TBD
 */
abstract class Modifier_Abstract implements Modifier_Strategy_Interface {

	/**
	 * The modifier type for the concrete strategy (e.g., 'coupon', 'fee').
	 *
	 * @since TBD
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * The repository for interacting with the order modifiers table.
	 *
	 * @since TBD
	 * @var Order_Modifiers_Repository
	 */
	protected Order_Modifiers_Repository $repository;

	/**
	 * Constructor to set up the repository and modifier type.
	 *
	 * @since TBD
	 *
	 * @param string $modifier_type The modifier type (e.g., 'coupon', 'fee').
	 */
	public function __construct( string $modifier_type ) {
		$this->modifier_type = $modifier_type;
		$this->repository    = new Order_Modifiers_Repository();
	}

	/**
	 * Gets the modifier type.
	 *
	 * @since TBD
	 * @return string The modifier type.
	 */
	public function get_modifier_type(): string {
		return $this->modifier_type;
	}

	/**
	 * Retrieves the modifier by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $modifier_id The modifier ID.
	 *
	 * @return array|null The modifier data or null if not found.
	 */
	public function get_modifier_by_id( int $modifier_id ): ?array {
		$modifier_data = $this->repository->find_by_id( $modifier_id );
		return $modifier_data ? $modifier_data->to_array() : null;
	}

	/**
	 * Finds a modifier by its slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug The slug to search for.
	 *
	 * @return mixed The modifier data or null if not found.
	 */
	public function find_by_slug( string $slug ): mixed {
		return $this->repository->find_by_slug( $slug, $this->modifier_type );
	}

	/**
	 * Sanitizes and maps the raw form data for the modifier.
	 *
	 * This method is to be implemented by each strategy class.
	 *
	 * @since TBD
	 *
	 * @param array $data The raw form data.
	 *
	 * @return array The sanitized and mapped data.
	 */
	abstract public function sanitize_data( array $data ): array;

	/**
	 * Validates the required fields for the modifier.
	 *
	 * This method is to be implemented by each strategy class.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	abstract public function validate_data( array $data ): bool;

	/**
	 * Converts a decimal amount to its value in cents.
	 *
	 * This method is used to convert a floating-point amount (e.g., 23.00) into an integer representing cents.
	 *
	 * @since TBD
	 *
	 * @param float $amount The amount to convert.
	 *
	 * @return int The amount converted to cents.
	 */
	public function convert_to_cents( float $amount ): int {
		return (int) round( floatval( $amount ) * 100 );
	}

	/**
	 * Converts an amount in cents to a formatted decimal string.
	 *
	 * This method is used to convert an integer amount in cents (e.g., 2300) into a string with two decimal points (e.g., 23.00).
	 *
	 * @since TBD
	 *
	 * @param int $cents The amount in cents.
	 *
	 * @return string The formatted decimal string representing the amount.
	 */
	public function convert_from_cents( int $cents ): string {
		return number_format( $cents / 100, 2, '.', '' );
	}

	/**
	 * Generates a unique alphanumeric slug of 7 characters.
	 *
	 * The slug will be checked for uniqueness in the database before being returned.
	 *
	 * @since TBD
	 *
	 * @return string The unique slug.
	 */
	public function generate_unique_slug(): string {
		$slug_length = 7;

		// Generate a random alphanumeric slug.
		do {
			// Generate random bytes and convert them to an alphanumeric string.
			$random_string = substr( base_convert( bin2hex( random_bytes( 4 ) ), 16, 36 ), 0, $slug_length );
		} while ( ! $this->is_slug_unique( $random_string ) );

		return $random_string;
	}

	/**
	 * Checks whether a slug is unique in the database.
	 *
	 * @since TBD
	 *
	 * @param string $slug The slug to check for uniqueness.
	 *
	 * @return bool True if the slug is unique, false otherwise.
	 */
	protected function is_slug_unique( string $slug ): bool {
		$existing_slug = $this->repository->find_by_slug( $slug, $this->modifier_type );

		return null === $existing_slug;
	}
}
