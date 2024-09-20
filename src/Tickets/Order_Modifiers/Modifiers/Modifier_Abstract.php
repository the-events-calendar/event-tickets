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

use InvalidArgumentException;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Order_Modifiers\Modifier_Settings;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifiers_Repository;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers_Meta as Order_Modifiers_Meta_Repository;

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
	 * The repository for interacting with the order modifiers meta table.
	 *
	 * @since TBD
	 * @var Order_Modifiers_Meta_Repository Repository
	 */
	protected Order_Modifiers_Meta_Repository $order_modifiers_meta_repository;

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
		$this->modifier_type                   = $modifier_type;
		$this->repository                      = new Order_Modifiers_Repository();
		$this->order_modifiers_meta_repository = new Order_Modifiers_Meta_Repository();
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
	 * Gets all modifiers by the type.
	 *
	 * @return array
	 */
	public function get_all_modifiers(): mixed {
		return $this->repository->find_by_type( $this->modifier_type );
	}

	/**
	 * Finds a modifier by its slug.
	 *
	 * @since TBD
	 *
	 * @param array $search Parameters to search Order Modifiers by.
	 *
	 * @return array The modifier data.
	 */
	public function find_by_search( array $search ): array {
		return $this->repository->search_modifiers( $search );
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
	 * Displays the formatted amount based on the type.
	 *
	 * Depending on whether the modifier is a percentage, flat fee, or any future type,
	 * it will format the value accordingly. For percentages, it appends the '%' symbol,
	 * and for flat fees, it formats the value as currency.
	 *
	 * @since TBD
	 *
	 * @param int    $value The raw amount value (e.g., in cents for flat fees).
	 * @param string $type  The type of the fee ('percent' for percentage-based, 'flat' for fixed value).
	 *
	 * @return string The formatted amount, either as a percentage, currency, or future types.
	 */
	public function display_amount_field( int $value, string $type = 'flat' ) {
		switch ( $type ) {
			case 'percent':
				// Return the value as a percentage with the '%' symbol.
				return $this->display_percentage( $value );

			case 'flat':
			default:
				// Return the value as a flat fee (currency) for 'flat' or unknown types.
				return $this->display_flat_fee( $value );
		}
	}

	/**
	 * Formats the given value as a percentage.
	 *
	 * Converts the value from cents and appends a '%' symbol. If the percentage is a whole number, it drops the
	 * decimal places (e.g., '23%' instead of '23.00%').
	 *
	 * @since TBD
	 *
	 * @param int $value The raw percentage value in cents.
	 *
	 * @return string The formatted percentage value.
	 */
	protected function display_percentage( $value ) {
		$value = $this->convert_from_cents( $value );

		// If the value is a whole number, format it without decimals.
		if ( intval( $value ) == $value ) {
			$value = intval( $value ); // Cast to int to remove the '.00'.
		}

		return $value . '%';
	}

	/**
	 * Formats the given value as currency.
	 *
	 * Uses the Value class to convert the raw value (in cents) into a properly formatted currency amount.
	 *
	 * @since TBD
	 *
	 * @param int $value The raw value in cents.
	 *
	 * @return string The formatted currency value (e.g., '10.00' for 1000 cents).
	 */
	protected function display_flat_fee( $value ) {
		$value = $this->convert_from_cents( $value );
		return Value::create( $value )->get_currency();
	}

	/**
	 * Generates a unique alphanumeric slug of 7 characters with random upper and lowercase characters.
	 *
	 * The slug will be checked for uniqueness in the database before being returned.
	 *
	 * @since TBD
	 *
	 * @return string The unique slug.
	 * @throws Exception if random_bytes fails.
	 */
	public function generate_unique_slug(): string {
		$slug_length = 7;

		// Generate a random alphanumeric slug.
		do {
			// Generate random bytes and convert them to an alphanumeric string.
			$random_string = substr( base_convert( bin2hex( random_bytes( 4 ) ), 16, 36 ), 0, $slug_length );

			// Randomly change the case of each character in the string.
			$random_string = $this->randomize_string_case( $random_string );
		} while ( ! $this->is_slug_unique( $random_string ) );

		return $random_string;
	}

	/**
	 * Randomizes the case of each character in a string, alternating between upper and lowercase.
	 *
	 * @since TBD
	 *
	 * @param string $input The input string.
	 *
	 * @return string The string with randomized character cases.
	 */
	protected function randomize_string_case( string $input ): string {
		$characters = str_split( $input );
		foreach ( $characters as &$char ) {
			if ( random_int( 0, 1 ) ) {
				$char = strtoupper( $char );
			} else {
				$char = strtolower( $char );
			}
		}

		return implode( '', $characters );
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

	/**
	 * Convert the status to a human-readable format.
	 *
	 * This method converts the internal status values ('active', 'inactive', 'draft')
	 * into human-readable strings ('Active', 'Inactive', 'Draft').
	 * It also provides a filter to allow for customizing the status labels if necessary.
	 *
	 * @since TBD
	 *
	 * @param string $status The raw status from the database.
	 *
	 * @return string The human-readable status.
	 */
	public function get_status_display( string $status ): string {
		// Default conversion.
		$statuses = [
			'active'   => __( 'Active', 'event-tickets' ),
			'inactive' => __( 'Inactive', 'event-tickets' ),
			'draft'    => __( 'Draft', 'event-tickets' ),
		];

		/**
		 * Filters the conversion of order modifier status values to human-readable formats.
		 *
		 * This filter allows modifying the labels for the default status values:
		 * 'active', 'inactive', and 'draft'. For example, 'draft' could be changed to 'In Progress'.
		 *
		 * @since TBD
		 *
		 * @param array $statuses An array of status conversions where the key is the raw status and the value is the display version.
		 */
		$statuses = apply_filters( 'tec_modifier_status_conversion', $statuses );

		return $statuses[ $status ] ?? ucfirst( $status );
	}

	/**
	 * Retrieves the page slug for the current modifier context.
	 *
	 * This method provides the slug associated with the page where the modifier is being managed.
	 * It is used in cases where the slug is required for rendering or processing actions
	 * related to the specific modifier.
	 *
	 * @since TBD
	 *
	 * @return string The page slug, or empty if not applicable.
	 */
	public function get_page_slug() {
		// @todo redscar - Does this logic make sense? Should we alter this?
		$modifier_settings = new Modifier_Settings();
		return $modifier_settings->get_page_slug();
	}

	/**
	 * Handles metadata for a given modifier, either updating or inserting it as necessary.
	 *
	 * This method simplifies metadata handling by centralizing the logic for
	 * creating/updating meta data. It passes default values which can be overwritten by the passed $args.
	 * A 'meta_key' is mandatory; if it is missing, an exception will be thrown.
	 *
	 * @since TBD
	 *
	 * @param int   $modifier_id The ID of the modifier.
	 * @param array $args The metadata arguments. Expects 'meta_key', 'meta_value', and can override 'priority'.
	 *
	 * @return mixed
	 *
	 * @throws InvalidArgumentException If 'meta_key' is not provided.
	 */
	protected function handle_meta_data( int $modifier_id, array $args = [] ): mixed {
		// Default structure for the metadata.

		$defaults = [
			'order_modifier_id' => $modifier_id,
			'meta_key'          => '',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'        => '',
			'priority'          => 0,
		];

		// Merge defaults with the passed arguments.
		$meta_data = array_merge( $defaults, $args );

		// Ensure that a 'meta_key' is provided.
		if ( empty( $meta_data['meta_key'] ) ) {
			throw new \InvalidArgumentException( __( 'Meta key is required to insert or update meta data.', 'event-tickets' ) );
		}

		// Upsert the metadata using the repository.
		return $this->order_modifiers_meta_repository->upsert_meta( new Order_Modifier_Meta( $meta_data ) );
	}
}
