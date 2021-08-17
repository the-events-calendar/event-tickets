<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Denied.
 *
 * Used for handling Orders where Pending payment but never completed it, becoming Abandoned after a week..
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Not_Completed extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SLUG = 'not-completed';

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Not Completed', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected $flags = [
		'incomplete',
		'warning',
		'count_incomplete',
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
}