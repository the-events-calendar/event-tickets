<?php
/**
 * Status: Unsupported
 *
 * @since 5.13.0
 *
 * @package TEC\Tickets\Commerce\Status
 */

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Unsupported.
 *
 * A status that is not supported, but avoids fatals.
 *
 * @since   5.13.0
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Unsupported extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since 5.13.0
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
	 *
	 * @var string[]
	 */
	protected $flags = [];

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	protected $wp_arguments = [
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
	];
}
