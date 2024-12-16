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
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use Exception;
use InvalidArgumentException;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Exceptions\Not_Found_Exception;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Relationships;
use TEC\Tickets\Commerce\Order_Modifiers\Modifier_Admin_Handler;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifiers_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta as Order_Modifiers_Meta_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Order_Modifier_Relationship_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Currency_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Positive_Integer_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value;

/**
 * Class Modifier_Abstract
 *
 * Provides a base class for order modifier strategies like Coupon and Fee.
 *
 * @since 5.18.0
 */
abstract class Modifier_Abstract implements Modifier_Strategy_Interface {
	use Valid_Types;

	/**
	 * The modifier type for the concrete strategy (e.g., 'coupon', 'fee').
	 *
	 * @since 5.18.0
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * The repository for interacting with the order modifiers table.
	 *
	 * @since 5.18.0
	 * @var Order_Modifiers_Repository
	 */
	protected Order_Modifiers_Repository $repository;

	/**
	 * The repository for interacting with the order modifiers meta table.
	 *
	 * @since 5.18.0
	 * @var Order_Modifiers_Meta_Repository Repository
	 */
	protected Order_Modifiers_Meta_Repository $order_modifiers_meta_repository;

	/**
	 * The repository for interacting with the order modifier relationship table.
	 *
	 * @since 5.18.0
	 * @var Order_Modifier_Relationship_Repository Repository
	 */
	protected Order_Modifier_Relationship_Repository $order_modifiers_relationship_repository;

	/**
	 * Fields required by this modifier.
	 * The required field should be the key name.
	 *
	 * @since 5.18.0
	 * @var array
	 */
	protected array $required_fields = [];

	/**
	 * Constructor to set up the repository and modifier type.
	 *
	 * @since 5.18.0
	 */
	public function __construct() {
		$this->repository                              = new Order_Modifiers_Repository( $this->modifier_type );
		$this->order_modifiers_meta_repository         = new Order_Modifiers_Meta_Repository();
		$this->order_modifiers_relationship_repository = new Order_Modifier_Relationship_Repository();
	}

	/**
	 * Gets the modifier type.
	 *
	 * @since 5.18.0
	 * @return string The modifier type.
	 */
	public function get_modifier_type(): string {
		return $this->modifier_type;
	}

	/**
	 * Inserts a new Modifier.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data to insert.
	 *
	 * @return Model The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): Model {
		// Ensure the modifier_type is set to the expected one.
		$data['modifier_type'] = $this->modifier_type;

		$this->validate_data( $data );
		return $this->repository->insert( new Order_Modifier( $data ) );
	}

	/**
	 * Updates an existing Modifier.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data to update.
	 *
	 * @return Model The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): Model {
		// Ensure the modifier_type is set to the expected one.
		$data['modifier_type'] = $this->modifier_type;

		$this->validate_data( $data );
		return $this->repository->update( new Order_Modifier( $data ) );
	}

	/**
	 * Retrieves modifier data by ID.
	 *
	 * @since 5.18.0
	 *
	 * @param int    $modifier_id The modifier ID.
	 *
	 *
	 * @return array|null The modifier data or null if not found.
	 */
	public function get_modifier_by_id( int $modifier_id ): ?array {
		$modifier_data = $this->repository->find_by_id( $modifier_id );
		return $modifier_data ? $modifier_data->to_array() : null;
	}

	/**
	 * Finds a modifier by its display name.
	 *
	 * @since 5.18.0
	 *
	 * @param array $search Parameters to search Order Modifiers by.
	 *
	 * @return array The modifier data.
	 */
	public function find_by_search( array $search ): array {
		return $this->repository->search_modifiers( $search );
	}

	/**
	 * Validates the required fields for the modifier.
	 *
	 * This base logic checks if all required fields are present, and not empty.
	 * Specific strategies can define additional validation logic.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 * @throws InvalidArgumentException If there are any validation errors.
	 */
	public function validate_data( array $data ): bool {
		$errors = [];

		// Check for missing fields.
		$missing_fields = array_diff_key( $this->required_fields, $data );
		if ( ! empty( $missing_fields ) ) {
			$errors[] = sprintf(
			/* translators: %s: List of missing fields. */
				__( 'The following required fields are missing: %s', 'event-tickets' ),
				implode( ', ', array_keys( $missing_fields ) )
			);
		}

		// Validate required fields are not empty.
		foreach ( $this->required_fields as $field => $required ) {
			if ( $required && ( ! isset( $data[ $field ] ) || ( is_string( $data[ $field ] ) && trim( $data[ $field ] ) === '' ) ) ) {
				$errors[] = sprintf(
				/* translators: %s: Field name. */
					__( 'The field "%s" is required and cannot be empty.', 'event-tickets' ),
					$field
				);
			}
		}

		// Validate the sub_type field, if present.
		if ( ! empty( $data['sub_type'] ) && ! $this->is_valid_subtype( $data['sub_type'] ) ) {
			$errors[] = sprintf(
			/* translators: %s: Invalid sub-type value. */
				__( 'The provided sub-type "%s" is invalid. Please use a valid sub-type.', 'event-tickets' ),
				$data['sub_type']
			);
		}

		// Validate the status field, if present.
		if ( ! empty( $data['status'] ) && ! $this->is_valid_status( $data['status'] ) ) {
			$errors[] = sprintf(
			/* translators: %s: Invalid status value. */
				__( 'The provided status "%s" is invalid. Please use a valid status.', 'event-tickets' ),
				$data['status']
			);
		}

		// Throw exception if there are errors.
		if ( ! empty( $errors ) ) {
			throw new InvalidArgumentException(
				sprintf(
				/* translators: %s: Validation error messages. */
					__( 'Validation failed: %s', 'event-tickets' ),
					implode( '; ', $errors )
				)
			);
		}

		return true;
	}

	/**
	 * Converts an amount in cents to a formatted decimal string.
	 *
	 * This method is used to convert an integer amount in cents (e.g., 2300) into a string with two decimal points (e.g., 23.00).
	 *
	 * @since 5.18.0
	 *
	 * @param int $raw_amount The amount in cents.
	 *
	 * @return string The formatted decimal string representing the amount.
	 */
	public function convert_from_raw_amount( int $raw_amount ): string {
		$amount       = $raw_amount / 100;
		$amount_value = Value::create( $amount );

		return number_format( $amount_value->get_decimal(), 2, '.', '' );
	}

	/**
	 * Displays the formatted amount based on the type.
	 *
	 * Depending on whether the modifier is a percentage, flat fee, or any future type,
	 * it will format the value accordingly. For percentages, it appends the '%' symbol,
	 * and for flat fees, it formats the value as currency.
	 *
	 * @since 5.18.0
	 *
	 * @param float  $value The raw amount value (e.g., in cents for flat fees).
	 * @param string $type  The type of the fee ('percent' for percentage-based, 'flat' for fixed value).
	 *
	 * @return string The formatted amount, either as a percentage, currency, or future types.
	 */
	public function display_amount_field( float $value, string $type = 'flat' ): string {
		switch ( $type ) {
			case 'percent':
				$formatted_amount = ( new Percent_Value( $value ) )->get_as_string();
				break;

			case 'flat':
			default:
				$precision_value  = ( new Precision_Value( $value ) );
				$formatted_amount = ( new Currency_Value( $precision_value ) )->get();
				break;
		}

		/**
		 * Filters the displayed amount for the order modifier.
		 *
		 * This allows other developers to modify how amounts (whether percentages or flat fees)
		 * are displayed. For example, a developer could add a custom suffix or change the formatting.
		 *
		 * @since 5.18.0
		 *
		 * @param string $formatted_amount The formatted amount string (e.g., '10%', '$10.00').
		 * @param float  $value            The raw float value.
		 * @param string $type             The type of the amount (e.g., 'percent', 'flat').
		 */
		return apply_filters( 'tec_tickets_commerce_order_modifier_display_amount', $formatted_amount, $value, $type );
	}

	/**
	 * Generates a unique alphanumeric slug of 7 characters with random upper and lowercase characters.
	 *
	 * The slug will be checked for uniqueness in the database before being returned.
	 *
	 * @since 5.18.0
	 *
	 * @return string The unique slug.
	 * @throws Exception If random_bytes fails.
	 */
	public function generate_unique_slug(): string {
		$slug_length = 7;

		// Generate a random alphanumeric slug.
		do {
			// Generate random bytes and convert them to an alphanumeric string.
			$random_string = substr( base_convert( bin2hex( random_bytes( 4 ) ), 16, 36 ), 0, $slug_length );

			// Randomly change the case of each character in the string.
			$random_string = $this->randomize_string_case( $random_string );

			/**
			 * Filters the generated unique slug for the order modifier.
			 *
			 * This allows developers to modify the way slugs are generated or impose additional
			 * uniqueness checks before a slug is considered valid.
			 *
			 * @since 5.18.0
			 *
			 * @param string $slug The generated slug.
			 * @param string $modifier_type The type of modifier (e.g., 'coupon', 'fee').
			 */
			$random_string = apply_filters( 'tec_tickets_commerce_order_modifier_generate_slug', $random_string, $this->modifier_type );

		} while ( ! $this->is_slug_unique( $random_string ) );

		return $random_string;
	}

	/**
	 * Randomizes the case of each character in a string, alternating between upper and lowercase.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param string $slug The slug to check for uniqueness.
	 *
	 * @return bool True if the slug is unique, false otherwise.
	 */
	protected function is_slug_unique( string $slug ): bool {
		try {
			$this->repository->find_by_slug( $slug );
			return false;
		} catch ( Not_Found_Exception $e ) {
			// Slug does not exist, so it is unique.
			return true;
		}
	}

	/**
	 * Convert the status to a human-readable format.
	 *
	 * This method converts the internal status values ('active', 'inactive', 'draft')
	 * into human-readable strings ('Active', 'Inactive', 'Draft').
	 * It also provides a filter to allow for customizing the status labels if necessary.
	 *
	 * @since 5.18.0
	 *
	 * @param string $status The raw status from the database.
	 *
	 * @return string The human-readable status.
	 */
	public function get_status_display( string $status ): string {
		// Default conversion.
		$statuses = [
			'active'   => _x( 'Active', 'Order modifier status', 'event-tickets' ),
			'inactive' => _x( 'Inactive', 'Order modifier status', 'event-tickets' ),
			'draft'    => _x( 'Draft', 'Order modifier status', 'event-tickets' ),
		];

		/**
		 * Filters the human-readable status label for an order modifier.
		 *
		 * This allows developers to modify the status labels (e.g., changing 'Draft' to 'Pending').
		 *
		 * @since 5.18.0
		 *
		 * @param string[] $statuses The array of default status labels.
		 * @param string $raw_status The raw status from the database (e.g., 'active', 'draft').
		 * @param string $modifier_type The type of the modifier (e.g., 'coupon', 'fee').
		 */
		$statuses = apply_filters( 'tec_tickets_commerce_order_modifier_status_display', $statuses, $status, $this->modifier_type );

		return $statuses[ $status ] ?? $status;
	}

	/**
	 * Retrieves the page slug for the current modifier context.
	 *
	 * This method provides the slug associated with the page where the modifier is being managed.
	 * It is used in cases where the slug is required for rendering or processing actions
	 * related to the specific modifier.
	 *
	 * @since 5.18.0
	 *
	 * @return string The page slug, or empty if not applicable.
	 */
	public function get_page_slug() {
		return Modifier_Admin_Handler::get_page_slug();
	}

	/**
	 * Handles metadata for a given modifier, either updating or inserting it as necessary.
	 *
	 * This method simplifies metadata handling by centralizing the logic for
	 * creating/updating meta data. It passes default values which can be overwritten by the passed $args.
	 * A 'meta_key' is mandatory; if it is missing, an exception will be thrown.
	 *
	 * @since 5.18.0
	 *
	 * @param int   $modifier_id The ID of the modifier.
	 * @param array $args The metadata arguments. Expects 'meta_key', 'meta_value', and can override 'priority'.
	 *
	 * @return Model
	 *
	 * @throws InvalidArgumentException If 'meta_key' is not provided.
	 */
	protected function handle_meta_data( int $modifier_id, array $args = [] ): Model {
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


	/**
	 * Adds a new relationship between a modifier and a post.
	 *
	 * This method inserts a new relationship into the database, linking the modifier to
	 * the provided post ID with the specified post type.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the modifier.
	 * @param int $post_id The ID of the post being linked to the modifier.
	 *
	 * @return void
	 */
	protected function add_relationship( int $modifier_id, int $post_id ): void {
		$data = [
			'modifier_id' => $modifier_id,
			'post_id'     => $post_id,
			'post_type'   => get_post_type( $post_id ),
		];
		$this->order_modifiers_relationship_repository->insert( new Order_Modifier_Relationships( $data ) );
	}

	/**
	 * Deletes all relationships for a given modifier.
	 *
	 * This method removes all relationships associated with the specified modifier ID
	 * from the database, unlinking the modifier from any posts it was related to.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the modifier whose relationships will be deleted.
	 *
	 * @return void
	 */
	public function delete_relationship_by_modifier( int $modifier_id ): void {
		$this->order_modifiers_relationship_repository->clear_relationships_by_modifier_id( $modifier_id );
	}

	/**
	 * Deletes all relationships associated with a given post.
	 *
	 * This method clears all records in the relationships table for the provided post
	 * by calling the repository method to delete relationships based on the `post_id`.
	 *
	 * @since 5.18.0
	 *
	 * @param int $post_id The ID of the post for which relationships should be deleted.
	 *
	 * @return void
	 */
	public function delete_relationship_by_post( int $post_id ): void {
		$data = [
			'post_id'   => $post_id,
			'post_type' => get_post_type( $post_id ),
		];
		$this->order_modifiers_relationship_repository->clear_relationships_by_post_id( new Order_Modifier_Relationships( $data ) );
	}

	/**
	 * Retrieves the display name of the modifier in singular or plural form.
	 *
	 * This method returns the human-readable display name of the modifier,
	 * which can be used for rendering or displaying the modifier name in UI elements.
	 * The method allows fetching either the singular or plural form.
	 *
	 * @since 5.18.0
	 *
	 * @param bool $plural Whether to return the plural form. Defaults to false (singular).
	 *
	 * @return string The display name of the modifier.
	 */
	public function get_modifier_display_name( bool $plural = false ): string {
		return $plural ? $this->get_plural_name() : $this->get_singular_name();
	}

	/**
	 * Retrieves the singular name of the modifier.
	 *
	 * This method should be implemented by concrete classes to provide the singular name
	 * of the modifier (e.g., 'Coupon', 'Fee').
	 *
	 * @since 5.18.0
	 *
	 * @return string The singular name of the modifier.
	 */
	abstract protected function get_singular_name(): string;

	/**
	 * Retrieves the plural name of the modifier.
	 *
	 * This method should be implemented by concrete classes to provide the plural name
	 * of the modifier (e.g., 'Coupons', 'Fees').
	 *
	 * @since 5.18.0
	 *
	 * @return string The plural name of the modifier.
	 */
	abstract protected function get_plural_name(): string;

	/**
	 * Clears relationships if the apply_type has changed.
	 *
	 * This method compares the current apply_type stored in the database with the newly provided
	 * apply_type. If they are different, it clears all existing relationships for the modifier.
	 *
	 * @param int    $modifier_id  The ID of the fee modifier.
	 * @param string $new_apply_type The new apply_type (e.g., 'venue', 'organizer'). Based off of the meta key `fee_applied_to`.
	 *
	 * @return void
	 */
	public function maybe_clear_relationships( int $modifier_id, string $new_apply_type ): void {
		// Retrieve the current apply_type from the metadata.
		$current_apply_type = $this->order_modifiers_meta_repository->find_by_order_modifier_id_and_meta_key( $modifier_id, 'fee_applied_to' )->meta_value ?? null;

		// If the apply_type has changed, clear all relationships.
		if ( $current_apply_type !== $new_apply_type ) {
			// Clear the relationships for this modifier.
			$this->order_modifiers_relationship_repository->clear_relationships_by_modifier_id( $modifier_id );
		}
	}

	/**
	 * Deletes a modifier and its associated data.
	 *
	 * This method deletes the modifier from the repository and also attempts to remove any associated meta data,
	 * relationships, and other related information. The existence of meta and relationships is optional.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the modifier to delete.
	 *
	 * @return bool True if the deletion of the modifier was successful, false otherwise.
	 */
	public function delete_modifier( int $modifier_id ): bool {
		// Check if the modifier exists before attempting to delete it.
		try {
			$modifier = $this->repository->find_by_id( $modifier_id );
		} catch ( Exception $e ) {
			// Return false if the modifier does not exist.
			return false;
		}

		// Clear relationships associated with the modifier.
		$this->delete_relationship_by_modifier( $modifier_id );

		// Delete associated meta data.
		$this->order_modifiers_meta_repository->delete( new Order_Modifier_Meta( [ 'id' => $modifier_id ] ) );

		// Delete the modifier itself (mandatory).
		$delete_modifier = $this->repository->delete(
			new Order_Modifier(
				[
					'id'            => $modifier_id,
					'modifier_type' => $this->modifier_type,
				]
			)
		);

		// Check if the modifier deletion was successful.
		if ( $delete_modifier ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the meta value for a specific order modifier and meta key.
	 *
	 * This method fetches the meta data associated with the given order modifier ID and meta key.
	 * It queries the `order_modifiers_meta` table to find the relevant meta data for the modifier.
	 *
	 * @since 5.18.0
	 *
	 * @param int    $order_modifier_id The ID of the order modifier to retrieve meta for.
	 * @param string $meta_key          The meta key to look up (e.g., 'fee_applied_to').
	 *
	 * @return mixed|null The meta data found, or null if no matching record is found.
	 */
	public function get_order_modifier_meta_by_key( int $order_modifier_id, string $meta_key ) {
		return $this->order_modifiers_meta_repository->find_by_order_modifier_id_and_meta_key( $order_modifier_id, $meta_key );
	}

	/**
	 * Maps and sanitizes raw form data into model-ready data.
	 *
	 * @since 5.18.0
	 *
	 * @param array $raw_data The raw form data, typically from $_POST.
	 *
	 * @return array The sanitized and mapped data for database insertion or updating.
	 */
	public function map_form_data_to_model( array $raw_data ): array {
		return [
			'id'            => Positive_Integer_Value::from_number( $raw_data['order_modifier_id'] ?? 0 )->get(),
			'modifier_type' => $this->get_modifier_type(),
			'sub_type'      => sanitize_text_field( $raw_data['order_modifier_sub_type'] ?? '' ),
			'raw_amount'    => Float_Value::from_number( $raw_data['order_modifier_amount'] ?? 0 )->get(),
			'slug'          => sanitize_text_field( $raw_data['order_modifier_slug'] ?? '' ),
			'display_name'  => sanitize_text_field( $raw_data['order_modifier_display_name'] ?? '' ),
			'status'        => sanitize_text_field( $raw_data['order_modifier_status'] ?? '' ),
		];
	}

	/**
	 * Maps context data to the template context.
	 *
	 * This method prepares the context for rendering the edit form.
	 *
	 * @since 5.18.0
	 *
	 * @param array $context The raw model data.
	 *
	 * @return array The context data ready for rendering the form.
	 */
	abstract public function map_context_to_template( array $context ): array;

	/**
	 * Abstract method for handling relationship updates.
	 *
	 * This method must be implemented in child classes to handle the specific logic for
	 * updating relationships between modifiers and posts, depending on the modifier type.
	 *
	 * @since 5.18.0
	 *
	 * @param array $modifier_ids An array of modifier IDs to update.
	 * @param array $new_post_ids An array of new post IDs to be associated with the fee.
	 *
	 * @return void
	 */
	abstract public function handle_relationship_update( array $modifier_ids, array $new_post_ids ): void;
}
