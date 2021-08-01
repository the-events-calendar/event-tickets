<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Voided.
 *
 * Normally when an Order is Voided means the the Authorization for payment failed. Which means this order needs to be
 * ignored and refunded, since it's a status that cannot be reversed into complete or anything else.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Voided extends \Tribe__Tickets__Status__Abstract {

	public $name          = 'Voided';
	public $provider_name = 'voided';
	public $post_type     = \TEC\Tickets\Commerce\Order::POSTTYPE;

	public $warning        = true;
	public $count_refunded = true;

	//post status fields for tpp
	public $public                    = true;
	public $exclude_from_search       = false;
	public $show_in_admin_all_list    = true;
	public $show_in_admin_status_list = true;

}