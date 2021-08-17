<?php
namespace TEC\Tickets\Commerce\Status;

/**
 * Class Created.
 *
 * This is the first Status any order will have.
 *
 * Used for handling the Orders that were Created in the Tickets Commerce System but never got to Pending.
 * Normally the change to Pending will depend on the Gateway.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Created extends Status_Abstract {
	/**
	 * Slug for this Status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SLUG = 'created';

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Created', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected $flags = [
		'incomplete',
		'trigger_option',
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