<?php
/**
 * Tells which ticket providers the relative sales window applies to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Answers whether a provider, or a ticket's provider, takes the relative sales window.
 *
 * Tickets Commerce does; another plugin adds its own provider through the filter. The sale price window stays Tickets
 * Commerce's: only its tickets have the sale price fields it is resolved into.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Providers {
	/**
	 * Gets the classes of the providers whose tickets take the relative sales window.
	 *
	 * @since TBD
	 *
	 * @return string[] The provider classes.
	 */
	public function get_classes(): array {
		/**
		 * Filters the classes of the ticket providers whose tickets take the relative sales window.
		 *
		 * A provider added here saves the sales window rule with its tickets, and has its tickets resolved again when
		 * their event moves. Its tickets are found for an event through `tec_tickets_relative_sale_dates_event_ticket_ids`,
		 * and their dates are written in the format `tec_tickets_relative_sale_dates_ticket_date_format` names.
		 *
		 * @since TBD
		 *
		 * @param string[] $classes The provider classes. Default Tickets Commerce's.
		 */
		$classes = apply_filters( 'tec_tickets_relative_sale_dates_ticket_providers', [ Module::class ] );

		return is_array( $classes ) ? array_values( array_filter( $classes, 'is_string' ) ) : [ Module::class ];
	}

	/**
	 * Returns whether a provider's tickets take the relative sales window.
	 *
	 * @since TBD
	 *
	 * @param string $provider_class The provider class.
	 *
	 * @return bool Whether the provider is one the sales window applies to.
	 */
	public function accepts_provider( string $provider_class ): bool {
		return in_array( $provider_class, $this->get_classes(), true );
	}

	/**
	 * Returns whether a saved ticket takes the relative sales window, by its provider.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return bool Whether the ticket's provider is active and one the sales window applies to.
	 */
	public function accepts_ticket( int $ticket_id ): bool {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		return $provider instanceof Tickets && $this->accepts_provider( $provider->class_name );
	}

	/**
	 * Gets the active providers whose tickets take the relative sales window.
	 *
	 * @since TBD
	 *
	 * @return Tickets[] The provider instances, keyed by their class.
	 */
	public function get_active(): array {
		$active = [];

		foreach ( array_intersect( $this->get_classes(), array_keys( Tickets::modules() ) ) as $provider_class ) {
			$provider = Tickets::get_ticket_provider_instance( $provider_class );

			// Tickets Commerce stays a ticket module once loaded, and can be switched off later in the request.
			if ( $provider instanceof Tickets && ( ! $provider instanceof Module || tec_tickets_commerce_is_enabled() ) ) {
				$active[ $provider_class ] = $provider;
			}
		}

		return $active;
	}

	/**
	 * Returns whether any provider whose tickets take the relative sales window is active.
	 *
	 * @since TBD
	 *
	 * @return bool Whether such a provider is active.
	 */
	public function has_active_provider(): bool {
		return [] !== $this->get_active();
	}
}
