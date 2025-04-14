<?php
/**
 * Concrete Strategy for Coupon Modifiers.
 *
 * Handles the specific logic for Coupon modifiers, including inserting, updating,
 * rendering, and validating coupon data.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use InvalidArgumentException;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Coupon_Table;
use TEC\Tickets\Commerce\Values\Float_Value;
use Tribe__Tickets__Admin__Views;

/**
 * Concrete Strategy for Coupon Modifiers.
 *
 * @since 5.18.0
 */
class Coupon extends Modifier_Abstract {

	/**
	 * The modifier type for coupons.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';

	/**
	 * Required fields for Coupons.
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
		return __( 'Coupon', 'event-tickets' );
	}

	/**
	 * Retrieves the display name of the modifier in plural form.
	 *
	 * @since 5.18.0
	 *
	 * @return string The display name of the modifier.
	 */
	public function get_plural_name(): string {
		return __( 'Coupons', 'event-tickets' );
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
		$modifier = parent::insert_modifier( $data );
		$this->set_usage_limit( $modifier->id, tec_get_request_var( 'order_modifier_coupon_limit', '' ) );

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
		$modifier = parent::update_modifier( $data );
		$this->set_usage_limit( $modifier->id, tec_get_request_var( 'order_modifier_coupon_limit', '' ) );

		return $modifier;
	}

	/**
	 * Renders the coupon table.
	 *
	 * @since 5.18.0
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return void
	 */
	public function render_table( array $context ): void {
		$coupon_table = tribe( Coupon_Table::class );
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

		$admin_views->template( 'order_modifiers/coupon-edit', $context );
	}

	/**
	 * Maps context data to the template context.
	 *
	 * This method prepares the context for rendering the coupon edit form.
	 *
	 * @since 5.18.0
	 *
	 * @param array $context The raw model data.
	 *
	 * @return array The context data ready for rendering the form.
	 */
	public function map_context_to_template( array $context ): array {
		$limit_value = $this->meta_repository->find_by_order_modifier_id_and_meta_key(
			$context['modifier_id'],
			'coupons_available'
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
			'order_modifier_coupon_limit' => $limit_value ?? '',
		];
	}

	/**
	 * Handles relationship updates for Coupon modifiers.
	 *
	 * Coupons do not currently use relationships.
	 *
	 * @since 5.18.0
	 *
	 * @param array $modifier_ids An array of modifier IDs to update.
	 * @param array $new_post_ids An array of new post IDs to be associated with the fee.
	 *
	 * @return void
	 */
	public function handle_relationship_update( array $modifier_ids, array $new_post_ids ): void {
	}

	/**
	 * Set the usage limit for a coupon.
	 *
	 * @since 5.21.0
	 *
	 * @param int        $modifier_id The modifier ID.
	 * @param string|int $limit       The limit to set. Pass an empty string or 0 for no limit.
	 *
	 * @return Model
	 */
	public function set_usage_limit( int $modifier_id, $limit ) {
		// Allow passing zero to set an empty limit.
		if ( 0 === (int) $limit ) {
			$limit = '';
		}

		return $this->handle_meta_data(
			$modifier_id,
			[
				'meta_key'   => 'coupons_available',
				'meta_value' => $limit,
			]
		);
	}

	/**
	 * Maps and sanitizes raw form data into model-ready data.
	 *
	 * @since 5.21.0
	 *
	 * @param array $raw_data The raw form data, typically from $_POST.
	 *
	 * @return array The sanitized and mapped data for database insertion or updating.
	 * @throws InvalidArgumentException If the percentage is greater than 100.
	 */
	public function map_form_data_to_model( array $raw_data ): array {
		// If the subtype is a percentage, validate the amount is <= 100.
		if ( 'percent' === $raw_data['order_modifier_sub_type'] ) {
			$this->validate_percentage( (float) $raw_data['order_modifier_amount'] );
		}

		return parent::map_form_data_to_model( $raw_data );
	}

	/**
	 * Validates that the percentage is less than or equal to 100.
	 *
	 * @since 5.21.0
	 *
	 * @param float $value The percentage value to validate.
	 *
	 * @return void
	 * @throws InvalidArgumentException If the percentage is greater than 100.
	 */
	protected function validate_percentage( float $value ) {
		if ( Float_Value::from_number( $value )->get() > 100 ) {
			throw new InvalidArgumentException(
				esc_html__( 'Percentage must be less than or equal to 100.', 'event-tickets' )
			);
		}
	}
}
