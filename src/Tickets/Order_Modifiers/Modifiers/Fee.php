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
		$this->handle_relationship_update( [ $modifier->id ], $apply_to_post_ids );

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

		$this->maybe_clear_relationships( $modifier->id, $apply_fee_to );

		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => 'fee_applied_to',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => $apply_fee_to,
			]
		);

		// Determine the post ID(s) to apply the fee to based on the 'apply_fee_to' value.
		$apply_to_post_ids = [];

		switch ( $apply_fee_to ) {
			case 'venue':
				$apply_to_post_ids = tribe_get_request_var( 'venue_list', [] );
				break;
			case 'organizer':
				$apply_to_post_ids = tribe_get_request_var( 'organizer_list', [] );
				break;
		}

		// Ensure that $apply_to_post_id is an array for consistency.
		$apply_to_post_ids = is_array( $apply_to_post_ids ) ? $apply_to_post_ids : [ $apply_to_post_ids ];

		// Handle the relationship update, passing the relevant data.
		$this->handle_relationship_update( [ $modifier->id ], $apply_to_post_ids );

		return $modifier;
	}

	/**
	 * Handles relationships for multiple scenarios based on input.
	 *
	 * This method intelligently determines how to update relationships
	 * based on the number of `modifier_ids` and `post_ids` provided.
	 *
	 * @since TBD
	 *
	 * @param array $modifier_ids An array of modifier IDs to update.
	 * @param array $new_post_ids An array of post IDs to associate with the modifier(s).
	 *
	 * @return void
	 */
	public function handle_relationship_update( array $modifier_ids, array $new_post_ids ): void {
		// Scenario 1: Multiple modifier_ids and a single post_id.
		if ( count( $modifier_ids ) > 1 && count( $new_post_ids ) === 1 ) {
			$this->update_relationships_by_post( $new_post_ids[0], $modifier_ids );
			return;
		}

		// Scenario 2: Single modifier_id and multiple post_ids.
		if ( count( $modifier_ids ) === 1 && count( $new_post_ids ) > 1 ) {
			$this->update_relationships_by_modifier( $modifier_ids[0], $new_post_ids );
			return;
		}

		// Scenario 3: Multiple modifier_ids and multiple post_ids for 1:1 relationships.
		if ( count( $modifier_ids ) === count( $new_post_ids ) ) {
			foreach ( $modifier_ids as $index => $modifier_id ) {
				// Match each modifier_id with the corresponding post_id.
				$this->update_relationships_by_modifier( $modifier_id, [ $new_post_ids[ $index ] ] );
			}
			return;
		}

		// If input is not valid, we should bail out (optional).
		if ( count( $modifier_ids ) === 0 || count( $new_post_ids ) === 0 ) {
			return; // Bail out if there's no data to process.
		}
	}

	/**
	 * Handles relationships by modifier ID.
	 *
	 * This method updates the relationships for a single modifier, ensuring the
	 * `modifier_id` is associated with the correct set of `post_ids`.
	 *
	 * @since TBD
	 *
	 * @param int   $modifier_id The ID of the modifier to update.
	 * @param array $new_post_ids An array of new post IDs to associate with the modifier.
	 *
	 * @return void
	 */
	public function update_relationships_by_modifier( int $modifier_id, array $new_post_ids ): void {

		// Step 1: Delete all existing relationships for this modifier ID.
		$this->delete_relationship_by_modifier( $modifier_id );

		// Step 2: Insert all new relationships from $new_post_ids for the given modifier ID.
		foreach ( $new_post_ids as $new_post_id ) {
			// Ensure the post ID is valid before inserting the relationship.
			if ( ! empty( $new_post_id ) ) {
				$this->add_relationship( $modifier_id, $new_post_id );
			}
		}
	}


	/**
	 * Handles relationships by post ID.
	 *
	 * This method updates the relationships for a single post, ensuring the
	 * `post_id` is associated with the correct set of `modifier_ids`.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id The ID of the post to update.
	 * @param array $new_modifier_ids An array of new modifier IDs to associate with the post.
	 *
	 * @return void
	 */
	public function update_relationships_by_post( int $post_id, array $new_modifier_ids ): void {

		// Step 1: Delete all existing relationships for this post ID.
		$this->delete_relationship_by_post( $post_id );

		// Step 2: Insert all new relationships from $new_modifier_ids for the given post ID.
		foreach ( $new_modifier_ids as $new_modifier_id ) {
			// Ensure the modifier ID is valid before inserting the relationship.
			if ( ! empty( $new_modifier_id ) ) {
				$this->add_relationship( $new_modifier_id, $post_id );
			}
		}
	}

	/**
	 * Maps and sanitizes raw form data into model-ready data.
	 *
	 * @since TBD
	 *
	 * @param array $raw_data The raw form data, typically from $_POST.
	 *
	 * @return array The sanitized and mapped data for database insertion or updating.
	 */
	public function map_form_data_to_model( array $raw_data ): array {
		return [
			'id'               => isset( $raw_data['order_modifier_id'] ) ? absint( $raw_data['order_modifier_id'] ) : 0,
			'modifier_type'    => $this->get_modifier_type(),
			'sub_type'         => sanitize_text_field( $raw_data['order_modifier_sub_type'] ?? '' ),
			'fee_amount_cents' => $this->convert_to_cents( $raw_data['order_modifier_amount'] ?? 0 ),
			'slug'             => sanitize_text_field( $raw_data['order_modifier_slug'] ?? '' ),
			'display_name'     => sanitize_text_field( $raw_data['order_modifier_fee_name'] ?? '' ),
			'status'           => sanitize_text_field( $raw_data['order_modifier_status'] ?? '' ),
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
