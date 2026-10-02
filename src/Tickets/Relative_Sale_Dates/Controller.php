<?php
/**
 * Relative Sale Dates controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Registers the Relative Sale Dates feature and holds the switch that turns all of it off.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Controller extends Controller_Contract {
	/**
	 * The name of the constant, and of the environment variable, that disables the feature when truthy.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_RELATIVE_SALE_DATES_DISABLED';

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_pre_save', $this->container->callback( Ticket_Save::class, 'set_ticket_dates' ) );
		remove_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'save_rule' ) );
		remove_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'write_resolved_dates' ), 20 );
	}

	/**
	 * Determines whether the feature is active.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the feature is active.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			return false;
		}

		/**
		 * Filters whether the Relative Sale Dates feature is active.
		 *
		 * Only applies when neither the disabling constant nor the environment variable is set. It is read while the
		 * plugins load, so add it from a plugin or a must-use plugin: one added in a theme's `functions.php` comes too
		 * late and is ignored.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether the feature is active. Default `true`.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_relative_sale_dates_active', true ) );
	}

	/**
	 * Registers the controller.
	 *
	 * `Ticket_Save` is bound as a singleton so the container returns the same callbacks to `unregister()`.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Rule_Store::class );
		$this->container->singleton( Ticket_Save::class );

		add_action( 'tec_tickets_ticket_pre_save', $this->container->callback( Ticket_Save::class, 'set_ticket_dates' ), 10, 3 );
		add_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'save_rule' ), 10, 3 );
		// After the rule is stored, and before Ticket_Actions schedules the sales actions from the ticket dates, at 1000.
		add_action( 'tec_tickets_ticket_upserted', $this->container->callback( Ticket_Save::class, 'write_resolved_dates' ), 20, 2 );
	}
}
