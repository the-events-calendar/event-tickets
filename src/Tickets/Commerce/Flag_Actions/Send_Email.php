<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Emails\Order_Email_Sender_Registry;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Send_Email, normally triggered when an order is complete.
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Send_Email extends Flag_Action_Abstract {
	/**
	 * Registry that resolves the correct email sender for the order type.
	 *
	 * @since TBD
	 *
	 * @var Order_Email_Sender_Registry
	 */
	private Order_Email_Sender_Registry $email_sender_registry;

	/**
	 * Send_Email constructor.
	 *
	 * @since TBD
	 *
	 * @param Order_Email_Sender_Registry $email_sender_registry Registry of order email senders.
	 */
	public function __construct( Order_Email_Sender_Registry $email_sender_registry ) {
		$this->email_sender_registry = $email_sender_registry;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $flags = [
		'send_email',
	];

	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $order_contexts = [
		Order_Context::TICKET,
		Order_Context::RSVP_V2,
	];

	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $order ) {
		$this->email_sender_registry->send( $order );
	}
}
