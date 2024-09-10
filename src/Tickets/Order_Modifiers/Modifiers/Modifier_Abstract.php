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

use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;
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
	 * Fields required by this modifier.
	 *
	 * @since TBD
	 * @var array
	 */
	protected array $required_fields = [];

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
	 * Inserts a new Modifier.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to insert.
	 *
	 * @return mixed The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): mixed {
		// Ensure the modifier_type is set to the expected one.
		$data['modifier_type'] = $this->modifier_type;

		// Validate data before proceeding.
		if ( ! $this->validate_data( $data ) ) {
			return [];
		}

		// Use the repository to insert the data into the `order_modifiers` table.
		return $this->repository->insert( new Order_Modifier( $data ) );
	}

	/**
	 * Updates an existing Modifier.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to update.
	 *
	 * @return mixed The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): mixed {
		// Ensure the modifier_type is set to the expected one.
		$data['modifier_type'] = $this->modifier_type;

		// Validate data before proceeding.
		if ( ! $this->validate_data( $data ) ) {
			return [];
		}

		// Use the repository to update the data in the `order_modifiers` table.
		return $this->repository->update( new Order_Modifier( $data ) );
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
	 * Maps and sanitizes raw form data into model-ready data.
	 *
	 * @since TBD
	 *
	 * @param array $data The raw form data, typically from $_POST.
	 *
	 * @return array The sanitized and mapped data for database insertion or updating.
	 */
	abstract public function map_form_data_to_model( array $data ): array;


	/**
	 * Maps context data to the template context.
	 *
	 * This method prepares the context for rendering the edit form.
	 *
	 * @since TBD
	 *
	 * @param array $context The raw model data.
	 *
	 * @return array The context data ready for rendering the form.
	 */
	abstract public function map_context_to_template( array $context ): array;

	/**
	 * Validates the required fields for the modifier.
	 *
	 * This base logic checks if all required fields are present and not empty.
	 * Specific strategies can define additional validation logic.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( array $data ): bool {
		foreach ( $this->required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return false;
			}
		}

		// @todo redscar - We should implement some more "complex" validation.
		return true;
	}

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
