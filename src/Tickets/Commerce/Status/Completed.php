<?php
namespace TEC\Tickets\Commerce\Status;

/**
 * Class Completed.
 *
 * This is the status we use to mark a given order as paid and delivered in our Tickets Commerce system.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Completed extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	const SLUG = 'completed';

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Completed', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * Do not change the order arbitrarily. Flag actions are triggered in the order represented in this array.
	 */
	protected $flags = [
		'complete',
		'backfill_purchaser',
		'attendee_dispatch',
		'end_duplicated_pending_orders',
		'stock_reduced',
		'send_email',
		'send_email_completed_order',
		'send_email_purchase_receipt',
		'count_attendee',
		'count_completed',
		'count_sales',
		'increase_sales',
	];

	/**
	 * {@inheritdoc}
	 */
	protected $wp_arguments = [
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
	];

	/**
	 * {@inheritdoc}
	 */
	public function can_be_updated_to(): array {
		return [
			tribe( Voided::class ),
			tribe( Refunded::class ),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function required_previous_status(): array {
		return [
			tribe( Pending::class ),
		];
	}
}
