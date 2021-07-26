<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Not_Completed
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Status
 */
class Not_Completed extends \Tribe__Tickets__Status__Abstract {

	//If a Pending payment is never completed it becomes Abandoned after a week.
	public $name          = 'Not Completed';
	public $provider_name = 'not-completed';
	public $post_type     = \TEC\Tickets\Commerce\Order::POSTTYPE;

	public $count_incomplete = true;
	public $incomplete       = true;
	public $warning          = true;

	//post status fields for tpp
	public $public                    = true;
	public $exclude_from_search       = false;
	public $show_in_admin_all_list    = true;
	public $show_in_admin_status_list = true;

}