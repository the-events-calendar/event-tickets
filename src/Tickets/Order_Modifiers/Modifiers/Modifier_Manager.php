<?php
/**
 * Modifier Manager for handling operations and rendering related to Order Modifiers.
 *
 * This class serves as a context that interacts with different modifier strategies (such as Coupons or Booking Fees).
 * It handles the saving (insert/update) of modifiers and delegates rendering tasks to the appropriate strategy.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Order_Modifiers\Modifiers;

/**
 * Context class that interacts with the strategy.
 *
 * The Modifier_Manager class manages the insertion, updating, and validation
 * of order modifiers (such as Coupons and Booking Fees) by delegating
 * these operations to the strategy provided (e.g., Coupon strategy).
 *
 * @since TBD
 */
class Modifier_Manager {

	/**
	 * The modifier strategy being used (e.g., Coupon, Booking_Fee).
	 *
	 * @since TBD
	 *
	 * @var Modifier_Strategy_Interface
	 */
	protected Modifier_Strategy_Interface $strategy;

	/**
	 * Constructor to initialize the strategy.
	 *
	 * Sets the modifier strategy that will handle the insertion, updating,
	 * and validation of order modifiers.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Strategy_Interface $strategy The modifier strategy to use.
	 */
	public function __construct( Modifier_Strategy_Interface $strategy ) {
		if ( ! $strategy instanceof Modifier_Strategy_Interface ) {
			throw new \InvalidArgumentException( 'Invalid strategy provided.' );
		}
		$this->strategy = $strategy;
	}

	/**
	 * Saves a modifier (insert or update) based on the provided data.
	 *
	 * Validates the data before proceeding. If an ID is present in the data, the
	 * modifier will be updated; otherwise, a new modifier will be inserted.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to save the modifier.
	 *
	 * @return mixed The result of the insert or update operation, or an empty array if validation fails or no changes were made.
	 */
	public function save_modifier( array $data ): mixed {
		$data['modifier_type'] = $this->strategy->get_modifier_type();

		// Validate data before proceeding.
		if ( ! $this->strategy->validate_data( $data ) ) {
			// Optionally log the validation failure.
			// @todo redscar - decide how to handle this.
			error_log( 'Validation failed for ' . $this->strategy->get_modifier_type() );
			return [];
		}

		// Check if it's an update or an insert.
		if ( isset( $data['id'] ) && is_numeric( $data['id'] ) && (int) $data['id'] > 0 ) {
			return $this->strategy->update_modifier( $data );
		}

		return $this->strategy->insert_modifier( $data );
	}

	/**
	 * Fetches a modifier based on its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The modifier ID.
	 *
	 * @return mixed The modifier data if found, or null.
	 */
	public function find_modifier_by_id( int $id ): mixed {
		return $this->strategy->get_modifier_by_id( $id );
	}

	/**
	 * Renders the table for the current modifier strategy.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return mixed The rendered table content, typically as HTML.
	 */
	public function render_table( array $context ): mixed {
		return $this->strategy->render_table( $context );
	}

	/**
	 * Renders the edit screen for the current modifier strategy.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the edit screen.
	 *
	 * @return mixed The rendered edit screen content.
	 */
	public function render_edit_screen( array $context ): mixed {
		return $this->strategy->render_edit( $context );
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
		return absint( $amount * 100 );
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
		$existing_slug = $this->strategy->find_by_slug( $slug );

		return null === $existing_slug;
	}
}
