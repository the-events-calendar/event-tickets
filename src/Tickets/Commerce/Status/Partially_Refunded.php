<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Partially_Refunded.
 *
 * Used when an order has been partially refunded. Attendees and stock are preserved
 * until the order is fully refunded.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Partially_Refunded extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * We cannot use partially-refunded because then the status slug
	 * will be `tec-tc-partially-refunded`. Which is more than 20 characters,
	 * which is the maximum length of a post_status column in the database.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SLUG = 'part-refunded';

	/**
	 * {@inheritdoc}
	 *
	 * Intentionally omits archive_attendees and increase_stock so partial money
	 * refunds do not trash attendees or restock tickets.
	 */
	protected $flags = [
		'warning',
		'backfill_purchaser',
		'count_refunded',
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
	public function get_name() {
		return __( 'Partially Refunded', 'event-tickets' );
	}

	/**
	 * Whether a Status Interface can be changed to another Status Interface.
	 *
	 * @since TBD
	 *
	 * @param self $new_status The new status.
	 *
	 * @return bool Whether the new status can be applied to the current status.
	 */
	public function can_change_to( $new_status ): bool {
		if ( $this->get_wp_slug() === $new_status->get_wp_slug() ) {
			// Allow stacked partial refunds to store additional gateway payloads.
			return true;
		}

		return parent::can_change_to( $new_status );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_be_updated_to(): array {
		return [
			tribe( Refunded::class ),
		];
	}
}
