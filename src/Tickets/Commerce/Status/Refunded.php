<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Refunded.
 *
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Refunded extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	const SLUG = 'refunded';

	/**
	 * {@inheritdoc}
	 */
	protected $flags = [
		'warning',
		'backfill_purchaser',
		'count_refunded',
		'increase_stock',
		'archive_attendees',
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
		return __( 'Refunded', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_final() {
		return true;
	}

	/**
	 * Whether a Status Interface can be changed to another Status Interface.
	 *
	 * @since 5.18.1
	 *
	 * @param self $new_status The new status.
	 *
	 * @return bool Whether the new status can be applied to the current status.
	 */
	public function can_change_to( $new_status ): bool {
		if ( $this->get_wp_slug() === $new_status->get_wp_slug() ) {
			// Refunded can be changed to Refunded to manage multiple refunds.
			return true;
		}

		if ( $this->is_final() ) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_be_updated_to(): array {
		return [];
	}
}
