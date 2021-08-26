<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Increase_Stock
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Increase_Stock extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'increase_stock',
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
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		$i = true;
	}
}