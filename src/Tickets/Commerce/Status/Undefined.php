<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Undefined.
 *
 * Orders that landed on Undefined are just broken in some way that we cannot define.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Undefined extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SLUG = 'undefined';

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Undefined', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected $flags = [
		'count_incomplete',
		'incomplete',
		'warning',
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