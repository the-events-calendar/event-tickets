<?php
/**
 * Concrete Strategy for Coupon Modifiers.
 *
 * Handles the specific logic for Coupon modifiers, including inserting, updating,
 * rendering, and validating coupon data.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Coupon_Table;
use Tribe__Tickets__Admin__Views;

/**
 * Concrete Strategy for Coupon Modifiers.
 *
 * @since TBD
 */
class Coupon extends Modifier_Abstract {

	/**
	 * The modifier type for coupons.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';

	/**
	 * Required fields for Coupons.
	 *
	 * @since TBD
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
	 * Constructor for the Coupon strategy.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct( $this->modifier_type );
		$this->modifier_display_name        = __( 'Coupon', 'event-tickets' );
		$this->modifier_display_name_plural = __( 'Coupons', 'event-tickets' );
	}

	/**
	 * Inserts a new modifier and handles related metadata.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to insert.
	 *
	 * @return Model The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): Model {
		// Save the modifier.
		$modifier = parent::insert_modifier( $data );

		// Handle metadata (e.g., coupons_available).
		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => 'coupons_available',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => tribe_get_request_var( 'order_modifier_coupon_limit', '' ),
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
	 * @return Model The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): Model {
		// Save the modifier.
		$modifier = parent::update_modifier( $data );

		// Handle metadata (e.g., coupons_available).
		$this->handle_meta_data(
			$modifier->id,
			[
				'meta_key'   => 'coupons_available',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => tribe_get_request_var( 'order_modifier_coupon_limit', '' ),
			]
		);

		return $modifier;
	}

	/**
	 * Renders the coupon table.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return void
	 */
	public function render_table( array $context ): void {
		$coupon_table = new Coupon_Table( $this );
		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$admin_views->template(
			'order_modifiers/modifier-table',
			[
				'context'              => $context,
				'order_modifier_table' => $coupon_table,
			]
		);
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
		$context     = $this->map_context_to_template( $context );

		$admin_views->template( 'order_modifiers/coupon-edit', $context );
	}

	/**
	 * Maps context data to the template context.
	 *
	 * This method prepares the context for rendering the coupon edit form.
	 *
	 * @since TBD
	 *
	 * @param array $context The raw model data.
	 *
	 * @return array The context data ready for rendering the form.
	 */
	public function map_context_to_template( array $context ): array {
		$order_modifier_coupon_limit_meta_value = $this->order_modifiers_meta_repository->find_by_order_modifier_id_and_meta_key( $context['modifier_id'], 'coupons_available' )->meta_value ?? '';
		return [
			'order_modifier_display_name'     => $context['display_name'] ?? '',
			'order_modifier_slug'             => $context['slug'] ?? $this->generate_unique_slug(),
			'order_modifier_sub_type'         => $context['sub_type'] ?? '',
			'order_modifier_fee_amount_cents' => $this->convert_from_raw_amount( $context['raw_amount'] ?? 0 ),
			'order_modifier_status'           => $context['status'] ?? '',
			'order_modifier_coupon_limit'     => $order_modifier_coupon_limit_meta_value ?? '',
		];
	}

	/**
	 * Handles relationship updates for Coupon modifiers.
	 *
	 * Coupons do not currently use relationships.
	 *
	 * @since TBD
	 *
	 * @param array $modifier_ids An array of modifier IDs to update.
	 * @param array $new_post_ids An array of new post IDs to be associated with the fee.
	 *
	 * @return void
	 */
	public function handle_relationship_update( array $modifier_ids, array $new_post_ids ): void {
	}
}