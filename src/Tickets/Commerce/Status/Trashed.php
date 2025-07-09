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
 * @since 5.13.0
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
	 * Gets the slug of this status in WordPress.
	 *
	 * Since this is a core status, we do need to match.
	 *
	 * @since 5.18.1
	 *
	 * @return string
	 */
	public function get_wp_slug() {
		return static::SLUG;
	}

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
