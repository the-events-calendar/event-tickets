<?php
/**
 * Heals the ticket provider of events wrongly stored as Tickets Commerce, or storing none.
 *
 * @since 5.30.3
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
 * tickets from any other provider. An event storing no provider uses the site default instead, which
 * hides them the same way once another commerce provider is active. This restores the provider that
 * owns the tickets.
 *
 * @since 5.30.3
 * @since 5.31.2 Heals events storing no provider.
 *
 * @package TEC\Tickets\Commerce
 */
final class Event_Provider_Heal {

	/**
	 * Returns the provider an event stored as Tickets Commerce, or storing none, should use, storing it when it changes.
	 *
	 * Only the providers other than the stored one, or the site default when none is stored, can own tickets
	 * it hides, so only those are looked up. Tickets Commerce wins when it owns tickets: an event storing no
	 * provider resolved to it before 5.30.3. Otherwise the event heals when one other active provider owns
	 * all its tickets, RSVPs aside.
	 *
	 * @since 5.30.3
	 * @since 5.31.2 Added the `$stored_provider` parameter, so events storing no provider heal too; looks up only
	 *            the providers that can hide tickets, and keeps the result in the object cache.
	 *
	 * @param int    $post_id         The ID of the post to heal.
	 * @param string $stored_provider The provider stored for the post: Tickets Commerce, or empty when none is.
	 *
	 * @return string The provider class to use for the post.
	 */
	public function heal( int $post_id, string $stored_provider = Module::class ): string {
		$fallback     = $stored_provider ?: Tickets::get_default_module();
		$paid_modules = array_diff( array_keys( Tickets::modules() ), [ 'Tribe__Tickets__RSVP' ] );
		$candidates   = array_diff( $paid_modules, [ $fallback ] );

		if ( ! $candidates ) {
			return $fallback;
		}

		/*
		 * Only a kept fallback is cached: a healed provider is stored, so it is never checked again.
		 * The object cache, not a transient, so a frontend request never writes options on a site without one.
		 * The active modules are in the key so activating or deactivating one runs the check again.
		 */
		$cache_key = 'tec_tickets_event_provider_heal_' . $post_id . '_' . md5( $stored_provider . '|' . implode( ',', $paid_modules ) );
		$cache     = tribe_cache();

		if ( $cache->get( $cache_key, Cache_Listener::TRIGGER_SAVE_POST ) ) {
			return $fallback;
		}

		$owners = array_values( array_filter( $candidates, fn( string $module ): bool => $this->owns_tickets( $module, $post_id ) ) );

		if ( in_array( Module::class, $owners, true ) ) {
			$provider = Module::class;
		} elseif ( 1 === count( $owners ) && ! ( in_array( $fallback, $paid_modules, true ) && $this->owns_tickets( $fallback, $post_id ) ) ) {
			$provider = $owners[0];
		} else {
			$cache->set( $cache_key, true, DAY_IN_SECONDS, Cache_Listener::TRIGGER_SAVE_POST );

			return $fallback;
		}

		// Slashed, so the namespace separators of a class such as Tickets Commerce's survive the unslash.
		update_post_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field, wp_slash( $provider ) );

		return $provider;
	}

	/**
	 * Returns whether a provider owns tickets on a post.
	 *
	 * With RSVP v2 active, Tickets Commerce ticket queries leave out RSVPs.
	 *
	 * @since 5.31.2
	 *
	 * @param string $module  The provider class.
	 * @param int    $post_id The ID of the post.
	 *
	 * @return bool Whether the provider is active and owns tickets on the post.
	 */
	private function owns_tickets( string $module, int $post_id ): bool {
		$provider = Tickets::get_ticket_provider_instance( $module );

		return $provider && 0 < tribe_tickets( $provider->orm_provider )->by( 'event', $post_id )->found();
	}
}
