<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Reversed
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Reversed extends \Tribe__Tickets__Status__Abstract {

	public $name          = 'Reversed';
	public $provider_name = 'reversed';
	public $post_type     = \TEC\Tickets\Commerce\Order::POSTTYPE;

	public $warning        = true;
	public $count_refunded = true;

	//post status fields for tpp
	public $public                    = true;
	public $exclude_from_search       = false;
	public $show_in_admin_all_list    = true;
	public $show_in_admin_status_list = true;

}