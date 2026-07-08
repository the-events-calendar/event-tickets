<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Emails\Order_Email_Sender_Registry;
use TEC\Tickets\Commerce\Emails\RSVP_Email_Sender;
use TEC\Tickets\Commerce\Emails\Ticket_Email_Sender;
use TEC\Tickets\Commerce\Traits\Is_Ticket;

/**
 * Class Flag_Action_Handler
 *
 * @since 5.1.9
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
	 * Flag actions that apply to all Tickets Commerce order types.
	 *
	 * @since 5.1.9
	 *
	 * @var string[]
	 */
	protected $shared_flag_actions = [
		Validate_Stock_Availability::class,
		Generate_Attendees::class,
		Increase_Stock::class,
		Decrease_Stock::class,
		Archive_Attendees::class,
		Backfill_Purchaser::class,
		Send_Email::class,
		End_Duplicated_Pending_Orders::class,
	];

	/**
	 * Flag actions that apply only to standard ticket orders.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $ticket_only_flag_actions = [
		Send_Email_Purchase_Receipt::class,
		Send_Email_Completed_Order::class,
	];

	/**
	 * Which classes we will load for order flag actions by default.
	 *
	 * @since 5.1.9
	 *
	 * @var string[]
	 */
	protected $default_flag_actions = [];

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
		$this->default_flag_actions = array_merge(
			$this->shared_flag_actions,
			$this->ticket_only_flag_actions
		);

		$this->register_order_email_senders();
		$this->register_flag_action_bindings();

		foreach ( $this->default_flag_actions as $flag_action_class ) {
			$this->register_flag_action( $this->container->make( $flag_action_class ) );
		}

		$this->container->singleton( static::class, $this );
	}

	/**
	 * Registers singleton bindings for all default flag action classes.
	 *
	 * @since TBD
	 */
	protected function register_flag_action_bindings(): void {
		foreach ( $this->default_flag_actions as $flag_action_class ) {
			$this->container->singleton( $flag_action_class );
		}
	}

	/**
	 * Registers order email senders and the registry used by the `send_email` flag action.
	 *
	 * @since TBD
	 */
	protected function register_order_email_senders(): void {
		$this->container->singleton( RSVP_Email_Sender::class );
		$this->container->singleton( Ticket_Email_Sender::class );

		$this->container->tag(
			[
				RSVP_Email_Sender::class,
				Ticket_Email_Sender::class,
			],
			Order_Email_Sender_Registry::CONTAINER_TAG
		);

		$this->container->singleton( Order_Email_Sender_Registry::class );
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
