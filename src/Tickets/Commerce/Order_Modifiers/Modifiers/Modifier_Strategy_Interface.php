<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use TEC\Common\StellarWP\Models\Contracts\Model;

/**
 * Strategy Interface for Order Modifiers.
 *
 * Defines the methods that concrete strategies (such as Coupon or Booking Fee)
 * must implement for inserting, updating, and validating order modifiers.
 *
 * @since 5.18.0
 */
interface Modifier_Strategy_Interface {

	/**
	 * Gets the modifier type (e.g., 'coupon', 'fee').
	 *
	 * This method ensures that each strategy explicitly defines
	 * its modifier type, allowing the system to identify and handle
	 * different modifier types correctly.
	 *
	 * @since 5.18.0
	 *
	 * @return string The modifier type (e.g., 'coupon', 'fee').
	 */
	public function get_modifier_type(): string;

	/**
	 * Inserts a new Order Modifier into the system.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data for the order modifier to insert.
	 *
	 * @return Model The result of the insertion, typically the inserted order modifier or an empty array on failure.
	 */
	public function insert_modifier( array $data ): Model;

	/**
	 * Updates an existing Order Modifier in the system.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data for the order modifier to update.
	 *
	 * @return Model The result of the update, typically the updated order modifier or an empty array on failure.
	 */
	public function update_modifier( array $data ): Model;

	/**
	 * Validates the provided data for the order modifier.
	 *
	 * This method ensures that the data contains all required fields and
	 * that the values are valid before insertion or update.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( array $data ): bool;

	/**
	 * Retrieves the page slug for the current modifier context.
	 *
	 * This method provides the slug associated with the page where the modifier is being managed.
	 * It is used in cases where the slug is required for rendering or processing actions
	 * related to the specific modifier.
	 *
	 * @since 5.18.0
	 *
	 * @return string The page slug, or empty string if not applicable.
	 */
	public function get_page_slug();

	/**
	 * Retrieves the display name for the modifier.
	 *
	 * This method returns the display name for the modifier, which is used in various
	 * contexts to identify the modifier to the user.
	 *
	 * @since 5.18.0
	 *
	 * @param bool $plural Whether to return the plural form of the display name.
	 *
	 * @return string The display name for the modifier.
	 */
	public function get_modifier_display_name( bool $plural = false ): string;
}
