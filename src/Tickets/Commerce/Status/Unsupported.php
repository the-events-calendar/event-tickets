<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Unsupported.
 *
 * A status that is not supported, but avoids fatals.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Unsupported extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SLUG = 'unknown';

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Not Supported', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected $flags = [];

	/**
	 * {@inheritdoc}
	 */
	protected $wp_arguments = [
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
	];
}
