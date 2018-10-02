<?php


/**
 * Class Tribe__Tickets__Commerce__PayPal__Statuses__Not_Completed
 *
 * @since tbd
 *
 */
class Tribe__Tickets__Commerce__PayPal__Status__Not_Completed extends Tribe__Tickets__Status__Abstract {

	//If a Pending payment is never completed it becomes Abandoned after a week.
	public $name          = 'Not Completed';
	public $provider_name = 'not-completed';
	public $post_type     = 'tribe_tpp_orders';

	public $incomplete     = true;
	public $warning        = true;
	public $count_canceled = true;

}