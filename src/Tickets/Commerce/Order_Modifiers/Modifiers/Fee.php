<?php
/**
 * Concrete Strategy for fee Modifiers.
 *
 * Handles the specific logic for fee modifiers, including inserting, updating,
 * rendering, and validating fee data.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Fee_Table;
use Tribe__Tickets__Admin__Views;

/**
 * Concrete Strategy for fee Modifiers.
 *
 * @since 5.18.0
 */
class Fee extends Modifier_Abstract {

	/**
	 * The modifier type for fees.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * Required fields for fees.
	 *
	 * @since 5.18.0
	 * @var array
	 */
	protected array $required_fields = [
		'modifier_type' => 1,
		'sub_type'      => 1,
		'raw_amount'    => 1,
		'slug'          => 1,
		'display_name'  => 1,
		'status'        => 1,
	];

	/**
	 * Retrieves the display name of the modifier in singular form.
	 *
	 * @since 5.18.0
	 *
	 * @return string The display name of the modifier.
	 */
	public function get_singular_name(): string {
		return __( 'Fee', 'event-tickets' );
	}

	/**
	 * Retrieves the display name of the modifier in plural form.
	 *
	 * @since 5.18.0
	 *
	 * @return string The display name of the modifier.
	 */
	public function get_plural_name(): string {
		return __( 'Fees', 'event-tickets' );
	}

	/**
	 * Inserts a new modifier and handles related metadata.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data to insert.
	 *
	 * @return Model The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): Model {
		// Save the modifier.
		$modifier = parent::insert_modifier( $data );

		// Handle metadata (e.g., order_modifier_apply_to).
		$apply_fee_to = tec_get_request_var( 'order_modifier_apply_to', '' );

		// Handle metadata (e.g., order_modifier_apply_to).
		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => $this->get_applied_to_key( $this->modifier_type ),
				'meta_value' => $apply_fee_to,
			]
		);

		// Determine the post ID(s) to apply the fee to based on the 'apply_fee_to' value.
		$apply_to_post_id = null;

		switch ( $apply_fee_to ) {
			case 'venue':
				$apply_to_post_id = tec_get_request_var( 'venue_list', null );
				break;
			case 'organizer':
				$apply_to_post_id = tec_get_request_var( 'organizer_list', null );
				break;
		}

		// Handle the relationship update, passing the relevant data.
		$this->handle_relationship_update( [ $modifier->id ], (array) $apply_to_post_id );

		return $modifier;
	}

	/**
	 * Updates an existing modifier and handles related metadata.
	 *
	 * @since 5.18.0
	 *
	 * @param array $data The data to update.
	 *
	 * @return Model The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): Model {
		// Save the modifier using the parent method.
		$modifier = parent::update_modifier( $data );

		// Check if modifier was successfully updated.
		if ( empty( $modifier ) ) {
			return [];
		}

		// Handle metadata (e.g., order_modifier_apply_to).
		$apply_fee_to = tec_get_request_var( 'order_modifier_apply_to', '' );

		$this->maybe_clear_relationships( $modifier->id, $apply_fee_to );

		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => $this->get_applied_to_key( $this->modifier_type ),
				'meta_value' => $apply_fee_to,
			]
		);

		// Determine the post ID(s) to apply the fee to based on the 'apply_fee_to' value.
		$apply_to_post_ids = [];

		switch ( $apply_fee_to ) {
			case 'venue':
				$apply_to_post_ids = tec_get_request_var( 'venue_list', [] );
				break;
			case 'organizer':
				$apply_to_post_ids = tec_get_request_var( 'organizer_list', [] );
				break;
		}

		// Ensure that $apply_to_post_id is an array for consistency.
		$apply_to_post_ids = (array) $apply_to_post_ids;

		// Handle the relationship update, passing the relevant data.
		$this->handle_relationship_update( [ $modifier->id ], $apply_to_post_ids );

		return $modifier;
	}

	/**
	 * Handles relationships update.
	 *
	 *
	 * @since 5.18.0
	 *
	 * @param array $modifier_ids An array of modifier IDs to update.
	 * @param array $new_post_ids An array of post IDs to associate with the modifier(s).
	 *
	 * @return void
	 */
	public function handle_relationship_update( array $modifier_ids, array $new_post_ids ): void {
		// If no posts to update we should bail.
		if ( count( $new_post_ids ) === 0 ) {
			return; // Bail out if there's no data to process.
		}

		foreach ( $new_post_ids as $new_post_id ) {
			// Ensure the post ID is valid before updating the relationships.
			if ( ! empty( $new_post_id ) ) {
				$this->update_relationships_by_post( $new_post_id, $modifier_ids );
			}
		}
	}

	/**
	 * Handles relationships by post ID.
	 *
	 * This method updates the relationships for a single post, ensuring the
	 * `post_id` is associated with the correct set of `modifier_ids`.
	 *
	 * @since 5.18.0
	 *
	 * @param int   $post_id          The ID of the post to update.
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
	 * Renders the fee table.
	 *
	 * @since 5.18.0
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return void
	 */
	public function render_table( array $context ): void {
		$fee_table = tribe( Fee_Table::class );
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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param array $context The raw model data.
	 *
	 * @return array The context data ready for rendering the form.
	 */
	public function map_context_to_template( array $context ): array {
		$applied_to = $this->meta_repository->find_by_order_modifier_id_and_meta_key(
			$context['modifier_id'],
			'fee_applied_to'
		)->meta_value ?? '';

		$sub_type = $context['sub_type'] ?? '';
		$amount   = array_key_exists( 'raw_amount', $context )
			? $this->get_amount_for_subtype( $sub_type, (float) $context['raw_amount'] )
			: '';

		return [
			'order_modifier_display_name' => $context['display_name'] ?? '',
			'order_modifier_slug'         => $context['slug'] ?? $this->generate_unique_slug(),
			'order_modifier_sub_type'     => $sub_type,
			'order_modifier_amount'       => $amount,
			'order_modifier_status'       => $context['status'] ?? '',
			'order_modifier_fee_limit'    => $context['fee_limit'] ?? '',
			'order_modifier_apply_to'     => $applied_to,
		];
	}

	/**
	 * Retrieves the active posts related to a specific order modifier.
	 *
	 * This method finds the posts that are associated with a given modifier ID.
	 * It uses the order modifiers relationship repository to look up the relationship
	 * between the modifier and the post (such as tickets, venues, or organizers).
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the modifier to find active posts for.
	 *
	 * @return array The list of posts related to the modifier.
	 */
	public function get_active_on( $modifier_id ) {
		return $this->relationship_repository->find_by_modifier_id( $modifier_id );
	}
}
