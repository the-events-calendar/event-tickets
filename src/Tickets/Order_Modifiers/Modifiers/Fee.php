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

		// Handle metadata (e.g., coupons_available).
		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => 'fee_applied_to',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => tribe_get_request_var( 'order_modifier_apply_to', '' ),
			]
		);

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
		// Save the modifier.
		$modifier = parent::update_modifier( $data );

		// Handle metadata (e.g., coupons_available).
		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => 'fee_applied_to',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => tribe_get_request_var( 'order_modifier_apply_to', '' ),
			]
		);

		return $modifier;
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

	public function get_active_on( $modifier_id ) {
		return $this->order_modifiers_relationship_repository->find_by_modifier_id( $modifier_id );
	}
}
