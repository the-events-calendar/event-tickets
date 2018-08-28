<?php


/**
 * Class Tribe__Tickets__Status__Abstract
 *
 * @since TBD
 *
 */
abstract class Tribe__Tickets__Status__Abstract {

	public $name                = '';
	public $provider_name       = '';
	public $post_type           = '';
	public $incomplete          = false;
	public $warning             = false;
	public $trigger_option      = false;
	public $attendee_generation = false;
	public $attendee_dispatch   = false;
	public $stock_reduced       = false;
	public $count_attendee      = false;
	public $count_sales         = false;
	public $count_completed     = false;
	public $count_canceled      = false;
	public $count_refunded      = false;

}