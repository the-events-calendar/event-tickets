<?php
/**
 * Concrete Strategy for Coupon Modifiers.
 *
 * Handles the specific logic for Coupon modifiers, including inserting, updating,
 * rendering, and validating coupon data.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Modifiers;
 */

namespace TEC\Tickets\Order_Modifiers\Modifiers;

use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifiers_Repository;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;

/**
 * Concrete Strategy for Coupon Modifiers.
 *
 * @since TBD
 */
class Coupon implements Modifier_Strategy_Interface {

	/**
	 * The modifier type for coupons.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';

	/**
	 * Gets the modifier type for coupons.
	 *
	 * @since TBD
	 *
	 * @return string The modifier type ('coupon').
	 */
	public function get_modifier_type(): string {
		return $this->modifier_type;
	}

	/**
	 * Inserts a new Coupon Modifier.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to insert.
	 *
	 * @return mixed The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): mixed {
		// Ensure the modifier_type is set to 'coupon'.
		$data['modifier_type'] = $this->modifier_type;

		// Validate data before proceeding.
		if ( ! $this->validate_data( $data ) ) {
			return [];
		}

		// Use the repository to insert the data into the `order_modifiers` table.
		$repository = new Order_Modifiers_Repository();
		return $repository->insert( new Order_Modifier( $data ) );
	}

	/**
	 * Updates an existing Coupon Modifier.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to update.
	 *
	 * @return mixed The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): mixed {
		// Ensure the modifier_type is set to 'coupon'.
		$data['modifier_type'] = $this->modifier_type;

		// Validate data before proceeding.
		if ( ! $this->validate_data( $data ) ) {
			return [];
		}

		// Use the repository to update the data in the `order_modifiers` table.
		$repository = new Order_Modifiers_Repository();
		return $repository->update( new Order_Modifier( $data ) );
	}

	/**
	 * Validates the required fields for Coupons.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( array $data ): bool {
		$required_fields = [
			'modifier_type',
			'sub_type',
			'fee_amount_cents',
			'slug',
			'display_name',
			'status',
		];

		// Ensure all required fields are present and not empty.
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return false;
			}
		}

		// @todo redscar - We need to add data validation for each type.

		return true;
	}

	/**
	 * Sanitizes and maps the raw form data for a coupon.
	 *
	 * @since TBD
	 *
	 * @param array $data The raw form data.
	 *
	 * @return array The sanitized and mapped data.
	 */
	public function sanitize_data( array $data ): array {
		return [
			'id'          => isset( $data['order_modifier_id'] ) ? absint( $data['order_modifier_id'] ) : 0,
			'modifier_type'    => $this->get_modifier_type(), // Always set to 'coupon'.
			'sub_type'         => isset( $data['order_modifier_sub_type'] ) ? sanitize_text_field( $data['order_modifier_sub_type'] ) : '',
			'fee_amount_cents' => isset( $data['order_modifier_amount'] ) ? absint( $data['order_modifier_amount'] ) * 100 : 0,
			'slug'             => isset( $data['order_modifier_slug'] ) ? sanitize_text_field( $data['order_modifier_slug'] ) : '',
			'display_name'     => isset( $data['order_modifier_coupon_name'] ) ? sanitize_text_field( $data['order_modifier_coupon_name'] ) : '',
			'status'           => isset( $data['order_modifier_status'] ) ? sanitize_text_field( $data['order_modifier_status'] ) : '',
			// @todo - Need to get the meta data to insert next.
			//'coupon_limit'     => isset( $data['order_modifier_coupon_limit'] ) ? absint( $data['order_modifier_coupon_limit'] ) : 0,
		];
	}

	/**
	 * Retrieves the modifier data by ID.
	 *
	 * @since TBD
	 *
	 * @param int $modifier_id The ID of the modifier to retrieve.
	 * @return array|null The modifier data or null if not found.
	 */
	public function get_modifier_by_id( int $modifier_id ) {
		$repository = new Order_Modifiers_Repository();

		// Fetch the modifier from the repository by ID.
		$modifier_data = $repository->find_by_id( $modifier_id );

		return $modifier_data ? $modifier_data->to_array() : []; // Return null if no coupon is found with the provided ID.
	}

	/**
	 * Renders the coupon table.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return string The rendered coupon table content.
	 */
	public function render_table( array $context ): string {
		// Example logic for rendering the coupon table.
		return 'Rendered Coupons Table';
	}

	/**
	 * Prepares the context for rendering the coupon form.
	 *
	 * This method maps the internal coupon data to the fields required by the edit form.
	 * It converts amounts from cents to a formatted string using the provided Modifier_Manager.
	 *
	 * @since TBD
	 *
	 * @param array            $context The raw context data.
	 * @param Modifier_Manager $manager The Modifier_Manager to use for any reusable logic, such as fee conversions.
	 *
	 * @return array The prepared context data ready for rendering the form.
	 */
	public function prepare_context( array $context, Modifier_Manager $manager ): array {
		return [
			'order_modifier_display_name'     => $context['display_name'] ?? '',
			'order_modifier_slug'             => $context['slug'] ?? $manager->generate_unique_slug(),
			'order_modifier_sub_type'         => $context['sub_type'] ?? '',
			'order_modifier_fee_amount_cents' => isset( $context['fee_amount_cents'] )
				? $manager->convert_from_cents( $context['fee_amount_cents'] )
				: '',
			'order_modifier_status'           => $context['status'] ?? '',
			'order_modifier_coupon_limit'     => $context['coupon_limit'] ?? '',
		];
	}

	/**
	 * Renders the coupon edit screen.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the edit screen.
	 *
	 * @return void
	 */
	public function render_edit( array $context ): void {
		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$manager = new Modifier_Manager( $this ); // Assuming Coupon is passed as a strategy.
		$context = $this->prepare_context( $context, $manager );

		$admin_views->template( 'order_modifiers/coupon_edit', $context );
	}

	/**
	 * Finds a coupon modifier by its slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug The slug to search for.
	 *
	 * @return mixed The coupon modifier data or null if not found.
	 */
	public function find_by_slug( string $slug ): mixed {
		$repository = new Order_Modifiers_Repository();
		return $repository->find_by_slug( $slug, $this->modifier_type );
	}
}
