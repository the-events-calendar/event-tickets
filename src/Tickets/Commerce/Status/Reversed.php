<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Refunded.
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Reversed extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	const SLUG = 'reversed';

	/**
	 * {@inheritdoc}
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
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
	];

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Reversed', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_final() {
		return true;
	}
}
