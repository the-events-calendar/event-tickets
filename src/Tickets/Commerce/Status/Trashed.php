<?php
/**
 * Status: Trashed
 *
 * @since 5.13.0
 *
 * @package TEC\Tickets\Commerce\Status
 */

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Trashed.
 *
 * An order that has been trashed.
 *
 * @since   5.13.0
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Trashed extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since 5.13.0
	 *
	 * @var string
	 */
	const SLUG = 'trash';

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Trashed', 'event-tickets' );
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
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
	];
}
