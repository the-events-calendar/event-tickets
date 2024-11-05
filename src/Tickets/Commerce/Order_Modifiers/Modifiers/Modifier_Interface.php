<?php
/**
 * Interface for Order Modifiers (Coupons and Booking Fees).
 *
 * This interface defines the contract that all modifier types (e.g., Coupons, Booking Fees)
 * must adhere to when implementing their specific logic for inserting, updating, and rendering.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use TEC\Common\StellarWP\Models\Contracts\Model;

/**
 * Interface for Order Modifiers (Coupons and Booking Fees).
 *
 * Defines the contract that all Order Modifiers must follow.
 *
 * @since TBD
 */
interface Modifier_Interface {

	/**
	 * Gets the modifier type (e.g., 'coupon', 'fee').
	 *
	 * This method must return the modifier type as a string, identifying whether the modifier
	 * is a coupon, fee, or any other type.
	 *
	 * @since TBD
	 *
	 * @return string The modifier type (e.g., 'coupon', 'fee').
	 */
	public function get_modifier_type(): string;

	/**
	 * Adds a new Order Modifier to the system.
	 *
	 * Inserts a new instance of the Order Modifier (e.g., Coupon or Booking Fee) using the provided data.
	 * This data typically comes from user input, such as form submissions.
	 *
	 * @since TBD
	 *
	 * @param array $data Form data from the request.
	 *
	 * @return Model The newly created modifier instance (e.g., Coupon or Booking Fee).
	 */
	public function insert_modifier( array $data ): Model;

	/**
	 * Updates an existing Order Modifier.
	 *
	 * Updates the Order Modifier (e.g., Coupon or Booking Fee) based on the provided data.
	 * This data typically comes from user input, such as form submissions.
	 *
	 * @since TBD
	 *
	 * @param array $data Form data from the request.
	 *
	 * @return Model The updated modifier instance (e.g., Coupon or Booking Fee).
	 */
	public function update_modifier( array $data ): Model;

	/**
	 * Renders the table for this specific modifier type (e.g., Coupon table, Fee table).
	 *
	 * This method should output or return the HTML for the table that displays
	 * the relevant data for the given modifier type.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return mixed The rendered table content, typically as HTML.
	 */
	public function render_table( array $context ): mixed;

	/**
	 * Renders the edit screen for this specific modifier type.
	 *
	 * This method should output or return the HTML for the edit screen that allows
	 * users to modify the settings or details of the given modifier type.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the edit screen.
	 *
	 * @return void The rendered edit screen content, typically as HTML.
	 */
	public function render_edit( array $context ): void;

	/**
	 * Maps and sanitizes raw form data into model-ready data.
	 *
	 * @since TBD
	 *
	 * @param array $data The raw form data, typically from $_POST.
	 *
	 * @return array The sanitized and mapped data for database insertion or updating.
	 */
	public function map_form_data_to_model( array $data ): array;

	/**
	 * Retrieves an Order Modifier by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The modifier ID.
	 * @return mixed The modifier data if found, or null.
	 */
	public function get_modifier_by_id( int $id ): mixed;

	/**
	 * Finds an order modifier by its slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug The slug to search for.
	 *
	 * @return mixed The order modifier data or null if not found.
	 */
	public function find_by_slug( string $slug ): mixed;

	/**
	 * Prepares the context data for rendering the edit form.
	 *
	 * This method takes the raw context data and modifies it as necessary,
	 * including applying any conversions (e.g., converting amounts from cents to decimals) using the Modifier_Manager.
	 *
	 * @since TBD
	 *
	 * @param array            $context The raw context data.
	 * @param Modifier_Manager $manager The Modifier_Manager to use for shared logic such as amount conversions.
	 *
	 * @return array The prepared context data ready for rendering the form.
	 */
	public function prepare_context( array $context, Modifier_Manager $manager ): array;
}
