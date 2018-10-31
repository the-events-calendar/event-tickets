<?php


/**
 * Class Tribe__Tickets__Commerce__PayPal__Status__Undefined
 *
 * @since tbd
 *
 */
class Tribe__Tickets__Commerce__PayPal__Status__Undefined extends Tribe__Tickets__Status__Abstract {

	public $name          = 'Undefined';
	public $provider_name = 'undefined';
	public $post_type     = 'tribe_tpp_orders';

	public $incomplete     = true;
	public $warning        = true;

}