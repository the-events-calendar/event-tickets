<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Undefined
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Undefined extends \Tribe__Tickets__Status__Abstract {

	public $name          = 'Undefined';
	public $provider_name = 'undefined';
	public $post_type     = \TEC\Tickets\Commerce\Order::POSTTYPE;

	public $count_incomplete = true;
	public $incomplete       = true;
	public $warning          = true;

}