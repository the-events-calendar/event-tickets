<?php
/**
 * Heals the ticket provider of events wrongly stored as Tickets Commerce.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use Tribe__Cache_Listener as Cache_Listener;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Event_Provider_Heal.
 *
 * Saving an RSVP v2 used to store Tickets Commerce as the event's provider, hiding the event's
 * tickets from any other provider. This restores the provider that owns the tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
final class Event_Provider_Heal {

	/**
	 * Returns the provider an event stored as Tickets Commerce should use, storing it when it changes.
	 *
	 * The event heals when it has no Tickets Commerce tickets, RSVPs aside, and its tickets all belong
	 * to one other active provider. Otherwise it keeps Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post stored as Tickets Commerce.
	 *
	 * @return string The provider class to use for the post.
	 */
	public function heal( int $post_id ): string {
		$other_paid_modules = array_diff( array_keys( Tickets::modules() ), [ Module::class, 'Tribe__Tickets__RSVP' ] );

		if ( empty( $other_paid_modules ) ) {
			return Module::class;
		}

		// Only a kept Tickets Commerce is cached: a healed provider is stored, so it is never checked again.
		// The active modules are in the key so activating or deactivating one runs the check again.
		$cache_key = 'tec_tickets_event_provider_heal_' . $post_id . '_' . md5( implode( ',', $other_paid_modules ) );
		$cache     = tribe_cache();

		if ( $cache->get( $cache_key, Cache_Listener::TRIGGER_SAVE_POST ) ) {
			return Module::class;
		}

		// With RSVP v2 active, Tickets Commerce ticket queries leave out RSVPs.
		$ticket_providers = array_diff( Tickets::get_active_providers_for_post( $post_id ), [ 'Tribe__Tickets__RSVP' ] );

		if ( 1 === count( $ticket_providers ) && ! in_array( Module::class, $ticket_providers, true ) ) {
			$provider = reset( $ticket_providers );
			update_post_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field, $provider );

			return $provider;
		}

		$cache->set( $cache_key, true, 0, Cache_Listener::TRIGGER_SAVE_POST );

		return Module::class;
	}
}
