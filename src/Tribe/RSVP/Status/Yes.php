<?php


/**
 * Class Tribe__Tickets__RSVP__Status__Yes
 *
 * @since tbd
 *
 */
class Tribe__Tickets__RSVP__Status__Yes extends Tribe__Tickets__Status__Abstract {

	//Order fulfilled and complete – requires no further action
	public $name          = 'Yes';
	public $provider_name = 'yes';
	public $post_type     = 'tribe_rsvp_attendees';

	public $trigger_option      = true;
	public $attendee_generation = true;
	public $attendee_dispatch   = true;
	public $stock_reduced       = true;
	public $count_attendee      = true;
	public $count_sales         = true;
	public $count_completed     = true;


}