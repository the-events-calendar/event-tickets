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
	 * @since TBD
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
	 * Sets up all the Flag Action instances for the classes in `$shared_flag_actions` and
	 * `$ticket_only_flag_actions`.
	 *
	 * @since 5.1.9
	 * @since TBD Merges the shared and ticket-only flag action lists, registers the order email
	 *            senders, and resolves each flag action via the container instead of `new`.
	 *
	 * @note Flag action classes are merged shared-first, ticket-only-second. That order carries no
	 *       runtime meaning: each flag action listens on its own distinct
	 *       `tec_tickets_commerce_order_status_flag_{$flag}` hook, so registration order between
	 *       different flags never affects which handlers fire.
	 */
	public function register() {
		$flag_actions = array_merge(
			$this->shared_flag_actions,
			$this->ticket_only_flag_actions
		);

		$this->register_order_email_senders();
		$this->register_flag_action_bindings( $flag_actions );

		foreach ( $flag_actions as $flag_action_class ) {
			$this->register_flag_action( $this->container->make( $flag_action_class ) );
		}

		$this->container->singleton( static::class, $this );
	}

	/**
	 * Registers singleton bindings for all default flag action classes.
	 *
	 * @since TBD
	 *
	 * @param string[] $flag_actions The flag action classes to bind as singletons.
	 */
	protected function register_flag_action_bindings( array $flag_actions ): void {
		foreach ( $flag_actions as $flag_action_class ) {
			$this->container->singleton( $flag_action_class );
		}
	}

	/**
	 * Registers order email senders and the registry used by the `send_email` flag action.
	 *
	 * @since TBD
	 * @since TBD Resolves `Order_Email_Sender_Registry`'s `$senders` lazily via `->when()->needs()->give()`
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

		$this->container->when( Order_Email_Sender_Registry::class )
			->needs( '$senders' )
			->give(
				function () {
					/**
					 * Filters the order email senders used when the `send_email` flag action fires.
					 *
					 * Prefer registering senders on the container tag
					 * `Order_Email_Sender_Registry::CONTAINER_TAG` via `tribe()->tag()`.
					 *
					 * @since TBD
					 *
					 * @param Order_Email_Sender_Interface[] $senders Registered senders.
					 */
					return (array) apply_filters(
						'tec_tickets_commerce_order_email_senders',
						$this->container->tagged( Order_Email_Sender_Registry::CONTAINER_TAG )
					);
				}
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
