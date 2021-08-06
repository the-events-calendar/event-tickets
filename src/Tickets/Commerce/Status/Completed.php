<?php
namespace TEC\Tickets\Commerce\Status;

/**
 * Class Denied.
 *
 * This is the status we use to mark a given order as paid and delivered in our Tickets Commerce system.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Completed extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since TBD
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
	 */
	protected $flags = [
		'complete',
		'trigger_option',
		'attendee_generation',
		'attendee_dispatch',
		'stock_reduced',
		'count_attendee',
		'count_completed',
		'count_sales',
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