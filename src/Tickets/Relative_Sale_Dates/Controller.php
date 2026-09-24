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
		$this->container->get( Ticket_Save::class )->unregister();
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
		 * Only applies when neither the disabling constant nor the environment variable is set.
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
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Rule_Store::class );
		$this->container->register( Ticket_Save::class );
	}
}
