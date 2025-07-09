<?php
/**
 * Status trait.
 *
 * @since 5.18.1
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

/**
 * Trait Status
 *
 * @since 5.18.1
 */
trait Status {

	/**
	 * The modifier type for the concrete strategy (e.g., 'coupon', 'fee').
	 *
	 * @since 5.18.1
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * Convert the status to a human-readable format.
	 *
	 * This method converts the internal status values ('active', 'inactive', 'draft')
	 * into human-readable strings ('Active', 'Inactive', 'Draft'). It also provides a
	 * filter to allow for customizing the status labels if necessary.
	 *
	 * If the $status provided is not a valid status, it will be returned as-is.
	 *
	 * @since 5.18.1
	 *
	 * @param string $status The raw status from the database.
	 *
	 * @return string The human-readable status.
	 */
	public function get_status_display( string $status ): string {
		$statuses = $this->get_valid_statuses();

		/**
		 * Filters the human-readable status label for an order modifier.
		 *
		 * This allows developers to modify the status labels (e.g., changing 'Draft' to 'Pending').
		 *
		 * @since 5.18.1
		 *
		 * @param string[] $statuses      The array of default status labels.
		 * @param string   $raw_status    The raw status from the database (e.g., 'active', 'draft').
		 * @param string   $modifier_type The type of the modifier (e.g., 'coupon', 'fee').
		 */
		$statuses = apply_filters( 'tec_tickets_commerce_order_modifier_status_display', $statuses, $status, $this->modifier_type );

		return $statuses[ $status ] ?? $status;
	}

	/**
	 * Get the valid statuses for an order modifier.
	 *
	 * @since 5.18.1
	 *
	 * @return array
	 */
	protected function get_valid_statuses(): array {
		$statuses = [
			'active'   => _x( 'Active', 'Order modifier status label', 'event-tickets' ),
			'inactive' => _x( 'Inactive', 'Order modifier status label', 'event-tickets' ),
			'draft'    => _x( 'Draft', 'Order modifier status label', 'event-tickets' ),
		];

		/**
		 * Filters the valid statuses for an order modifier.
		 *
		 * This allows developers to modify the valid statuses for an order modifier.
		 *
		 * @since 5.18.1
		 *
		 * @param string[] $statuses      The array of default status labels.
		 * @param string   $modifier_type The type of the modifier (e.g., 'coupon', 'fee').
		 */
		return apply_filters( 'tec_tickets_commerce_order_modifier_valid_statuses', $statuses, $this->modifier_type );
	}

	/**
	 * Check if a status is valid for an order modifier.
	 *
	 * @since 5.18.1
	 *
	 * @param string $status The status to check.
	 *
	 * @return bool Whether the status is valid.
	 */
	protected function is_valid_status( string $status ): bool {
		return array_key_exists( $status, $this->get_valid_statuses() );
	}
}
