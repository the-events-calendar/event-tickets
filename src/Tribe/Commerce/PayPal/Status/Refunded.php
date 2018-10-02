<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Status__Refunded
 *
 * @since tbd
 *
 */


class Tribe__Tickets__Commerce__PayPal__Status__Refunded extends Tribe__Tickets__Status__Abstract {

	public $name          = 'Refunded';
	public $provider_name = 'refunded';
	public $post_type     = 'tribe_tpp_orders';

	public $warning        = true;
	public $count_refunded = true;

}