<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Traits\Is_Ticket;
use WP_Post;

/**
 * Class Flag_Action_Handler
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Flag_Action_Handler extends \TEC\Common\Contracts\Service_Provider {
	use Is_Ticket;

	/**
	 * Flag Actions registered.
	 *
	 * @since 5.1.9
	 *
	 * @var Flag_Action_Interface[]
	 */
	protected $flag_actions = [];

	/**
	 * Which classes we will load for order flag actions by default.
	 *
	 * @since 5.1.9
	 *
	 * @var string[]
	 */
	protected $default_flag_actions = [
		Generate_Attendees::class,
		Increase_Stock::class,
		Decrease_Stock::class,
		Archive_Attendees::class,
		Backfill_Purchaser::class,
		Send_Email::class,
		Send_Email_Purchase_Receipt::class,
		Send_Email_Completed_Order::class,
		End_Duplicated_Pending_Orders::class,
	];

	/**
	 * Gets the flag actions registered.
	 *
	 * @since 5.1.9
	 *
	 * @return Flag_Action_Interface[]
	 */
	public function get_all() {
		return $this->flag_actions;
	}

	/**
	 * Sets up all the Flag Action instances for the Classes registered in $default_flag_actions.
	 *
	 * @since 5.1.9
	 */
	public function register() {
		foreach ( $this->default_flag_actions as $flag_action_class ) {
			// Spawn the new instance.
			$flag_action = new $flag_action_class;

			// Register as a singleton for internal ease of use.
			$this->container->singleton( $flag_action_class, $flag_action );

			// Collect this particular status instance in this class.
			$this->register_flag_action( $flag_action );
		}

		$this->container->singleton( static::class, $this );

		add_filter( 'tec_tickets_commerce_prepare_order_for_email_send_email_completed_order', [ $this, 'prepare_order_for_email' ] );
		add_filter( 'tec_tickets_commerce_prepare_order_for_email_send_email_purchase_receipt', [ $this, 'prepare_order_for_email' ] );
	}

	/**
	 * Prepare the order for email.
	 *
	 * @since 5.18.0
	 *
	 * @param WP_Post $order The order to prepare.
	 *
	 * @return WP_Post
	 */
	public function prepare_order_for_email( WP_Post $order ) {
		if ( empty( $order->items ) || ! is_array( $order->items ) ) {
			return $order;
		}

		$order->items = array_filter( $order->items, fn ( $item ) => $this->is_ticket( $item ) );

		return $order;
	}

	/**
	 * Register a given flag action into the Handler, and hook the handling to WP.
	 *
	 * @since 5.1.9
	 *
	 * @param Flag_Action_Interface $flag_action Which flag action we are registering.
	 */
	public function register_flag_action( Flag_Action_Interface $flag_action ) {
		$this->flag_actions[] = $flag_action;
		$flag_action->hook();
	}
}
