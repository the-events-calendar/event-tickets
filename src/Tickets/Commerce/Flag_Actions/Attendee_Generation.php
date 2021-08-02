<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Attendee_Generation
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Attendee_Generation extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'attendee_generation',
	];

	/**
	 * {@inheritDoc}
	 */
	protected $post_types = [
		Order::POSTTYPE
	];

	/**
	 * {@inheritDoc}
	 */
	protected $priority = 10;

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, $post ) {
		$i = true;
	}
}