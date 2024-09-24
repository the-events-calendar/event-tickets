<?php
/**
 * Concrete Strategy for fee Modifiers.
 *
 * Handles the specific logic for fee modifiers, including inserting, updating,
 * rendering, and validating fee data.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Modifiers;
 */

namespace TEC\Tickets\Order_Modifiers\Modifiers;

use TEC\Tickets\Order_Modifiers\Table_Views\Fee_Table;

/**
 * Concrete Strategy for fee Modifiers.
 *
 * @since TBD
 */
class Fee extends Modifier_Abstract {

	/**
	 * The modifier type for fees.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * Required fields for fees.
	 *
	 * @since TBD
	 * @var array
	 */
	protected array $required_fields
		= [
			'modifier_type',
			'sub_type',
			'fee_amount_cents',
			'slug',
			'display_name',
			'status',
		];

	/**
	 * Constructor for the fee strategy.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct( $this->modifier_type );
		$this->modifier_display_name        = __( 'Fee', 'event-tickets' );
		$this->modifier_display_name_plural = __( 'Fees', 'event-tickets' );
	}

	/**
	 * Inserts a new modifier and handles related metadata.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to insert.
	 *
	 * @return mixed The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): mixed {
		// Save the modifier.
		$modifier = parent::insert_modifier( $data );

		// Handle metadata (e.g., order_modifier_apply_to).
		$apply_fee_to = tribe_get_request_var( 'order_modifier_apply_to', '' );

		// Handle metadata (e.g., order_modifier_apply_to).
		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => 'fee_applied_to',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => $apply_fee_to,
			]
		);

		// Determine the post ID(s) to apply the fee to based on the 'apply_fee_to' value.
		$apply_to_post_id = null;

		switch ( $apply_fee_to ) {
			case 'venue':
				$apply_to_post_id = tribe_get_request_var( 'venue_list', null );
				break;
			case 'organizer':
				$apply_to_post_id = tribe_get_request_var( 'organizer_list', null );
				break;
		}

		// Ensure that $apply_to_post_id is an array for consistency.
		$apply_to_post_ids = $apply_to_post_id ? [ $apply_to_post_id ] : [];

		// Handle the relationship update, passing the relevant data.
		$this->handle_relationship_update( $modifier->id, $apply_to_post_ids );

		return $modifier;
	}

	/**
	 * Updates an existing modifier and handles related metadata.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to update.
	 *
	 * @return mixed The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): mixed {
		// Save the modifier using the parent method.
		$modifier = parent::update_modifier( $data );

		// Check if modifier was successfully updated.
		if ( empty( $modifier ) ) {
			return [];
		}

		// Handle metadata (e.g., order_modifier_apply_to).
		$apply_fee_to = tribe_get_request_var( 'order_modifier_apply_to', '' );

		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => 'fee_applied_to',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => $apply_fee_to,
			]
		);

		// Determine the post ID(s) to apply the fee to based on the 'apply_fee_to' value.
		$apply_to_post_id = null;

		switch ( $apply_fee_to ) {
			case 'venue':
				$apply_to_post_id = tribe_get_request_var( 'venue_list', null );
				break;
			case 'organizer':
				$apply_to_post_id = tribe_get_request_var( 'organizer_list', null );
				break;
		}

		// Ensure that $apply_to_post_id is an array for consistency.
		$apply_to_post_ids = $apply_to_post_id ? [ $apply_to_post_id ] : [];

		// Handle the relationship update, passing the relevant data.
		$this->handle_relationship_update( $modifier->id, $apply_to_post_ids );

		return $modifier;
	}

	/**
	 * Handles relationship updates for Fee modifiers.
	 *
	 * This method compares the new set of post IDs with the existing relationships for
	 * the given fee modifier. It inserts new relationships if they don't exist and deletes
	 * old relationships that are no longer valid.
	 *
	 * @since TBD
	 *
	 * @param int   $modifier_id The ID of the fee modifier.
	 * @param array $new_post_ids An array of new post IDs to be associated with the fee.
	 *
	 * @return void
	 */
	protected function handle_relationship_update( int $modifier_id, array $new_post_ids ): void {
		// Retrieve the existing relationships from the repository.
		$existing_relationships = $this->get_active_on( $modifier_id );

		// Insert new relationships that don't exist in the current relationships.
		foreach ( $new_post_ids as $new_post_id ) {
			if ( ! $this->order_modifiers_relationship_repository->find_by_modifier_and_post_type( $modifier_id, $new_post_id ) ) {
				$this->add_relationship( $modifier_id, $new_post_id );
			}
		}

		// Delete old relationships that no longer match the new set.
		foreach ( $existing_relationships as $existing_relationship ) {
			if ( ! in_array( $existing_relationship->post_id, $new_post_ids ) ) {
				$this->delete_relationship( $modifier_id, $existing_relationship->post_id );
			}
		}
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
	public function map_form_data_to_model( array $data ): array {
		return [
			'id'               => isset( $data['order_modifier_id'] ) ? absint( $data['order_modifier_id'] ) : 0,
			'modifier_type'    => $this->get_modifier_type(),
			'sub_type'         => sanitize_text_field( $data['order_modifier_sub_type'] ?? '' ),
			'fee_amount_cents' => $this->convert_to_cents( $data['order_modifier_amount'] ?? 0 ),
			'slug'             => sanitize_text_field( $data['order_modifier_slug'] ?? '' ),
			'display_name'     => sanitize_text_field( $data['order_modifier_fee_name'] ?? '' ),
			'status'           => sanitize_text_field( $data['order_modifier_status'] ?? '' ),
		];
	}

	/**
	 * Renders the fee table.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return void
	 */
	public function render_table( array $context ): void {
		$fee_table = new fee_Table( $this );
		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$admin_views->template(
			'order_modifiers/modifier-table',
			[
				'context'              => $context,
				'order_modifier_table' => $fee_table,
			]
		);
	}

	/**
	 * Renders the fee edit screen.
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
		$context     = $this->map_context_to_template( $context );

		$admin_views->template( 'order_modifiers/fee-edit', $context );
	}

	/**
	 * Maps context data to the template context.
	 *
	 * This method prepares the context for rendering the fee edit form.
	 *
	 * @since TBD
	 *
	 * @param array $context The raw model data.
	 *
	 * @return array The context data ready for rendering the form.
	 */
	public function map_context_to_template( array $context ): array {
		$order_modifier_fee_applied_to = $this->order_modifiers_meta_repository->find_by_order_modifier_id_and_meta_key( $context['modifier_id'], 'fee_applied_to' )->meta_value ?? '';
		return [
			'order_modifier_display_name'     => $context['display_name'] ?? '',
			'order_modifier_slug'             => $context['slug'] ?? $this->generate_unique_slug(),
			'order_modifier_sub_type'         => $context['sub_type'] ?? '',
			'order_modifier_fee_amount_cents' => $this->convert_from_cents( $context['fee_amount_cents'] ?? 0 ),
			'order_modifier_status'           => $context['status'] ?? '',
			'order_modifier_fee_limit'        => $context['fee_limit'] ?? '',
			'order_modifier_apply_to'         => $order_modifier_fee_applied_to,
		];
	}

	/**
	 * Retrieves the active posts related to a specific order modifier.
	 *
	 * This method finds the posts that are associated with a given modifier ID.
	 * It uses the order modifiers relationship repository to look up the relationship
	 * between the modifier and the post (such as tickets, venues, or organizers).
	 *
	 * @since TBD
	 *
	 * @param int $modifier_id The ID of the modifier to find active posts for.
	 *
	 * @return array The list of posts related to the modifier.
	 */
	public function get_active_on( $modifier_id ) {
		return $this->order_modifiers_relationship_repository->find_by_modifier_id( $modifier_id );
	}

}
