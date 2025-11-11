<?php
/**
 * The Tickets abstraction object, used to add tickets-related properties to the event object crated by the
 * `tribe_get_event` function.
 *
 * @since 4.10.9
 *
 * @package Tribe\Tickets\Events\Views\V2\Models
 */

namespace Tribe\Tickets\Events\Views\V2\Models;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use ReturnTypeWillChange;
use Serializable;
use Tribe\Utils\Lazy_Events;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Tickets as Tickets_Tickets;
use WP_Post;

/**
 * Class Tickets
 *
 * @since 4.10.9
 *
 * @package Tribe\Tickets\Events\Views\V2\Models
 */
class Tickets implements ArrayAccess, Serializable {
	use Lazy_Events;

	/**
	 * The post ID this tickets model is for.
	 *
	 * @since 4.10.9
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * The tickets data.
	 *
	 * @since 4.10.9
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * A flag property indicating whether tickets for the post exists or not.
	 *
	 * @since 4.10.9
	 *
	 * @var bool
	 */
	protected $exists;

	/**
	 * An array of all the tickets for this event.
	 *
	 * @since 4.10.9
	 *
	 * @var array
	 */
	protected $all_tickets;

	/**
	 * A flag indicating whether the data for the model is cached or not.
	 *
	 * @since 5.26.1
	 *
	 * @var true
	 */
	private bool $is_cached = false;

	/**
	 * Tickets constructor.
	 *
	 * @param int $post_id The post ID.
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;

		$this->restore_from_cache();
	}

	/**
	 * Regenerates the caches for the models associated with the post ID.
	 *
	 * @since 5.26.1
	 *
	 * @param int $post_id The post ID. It could be any post type, not just events.
	 *
	 * @return void
	 */
	public static function regenerate_caches( int $post_id ): void {
		$model_cache_key = self::get_cache_key( $post_id );

		// First, clean the kv-cache entries for this post.
		tec_kv_cache()->delete( $model_cache_key );

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			// The clean of the cache happened in the context of the post deletion, we're done.
			return;
		}

		// Avoid caching the query results while we refresh the cache.
		$do_not_cache_results = static function ( $wp_query ): void {
			if ( ! $wp_query instanceof \WP_Query ) {
				return;
			}

			$wp_query->query_vars['cache_results'] = false;
		};

		add_action( 'parse_query', $do_not_cache_results );

		if ( $post->post_type === TEC::POSTTYPE ) {
			// It's an Event: refresh its cache.
			$model = new self( $post->ID );
			$model->exist();
		} else {
			// These are maps from the service slug to the post type, so we keep only the post type.
			$attendee_post_types = array_values( tribe_attendees()->attendee_types() );
			$ticket_types        = array_values( tribe_tickets()->ticket_types() );

			// This is not an event post, but it might be a Ticket or Attendee linked to an Event.
			$ancillary_post_types = array_merge( $attendee_post_types, $ticket_types );

			if ( ! in_array( $post->post_type, $ancillary_post_types, true ) ) {
				remove_action( 'parse_query', $do_not_cache_results );

				// Not a post type we're interested in.
				return;
			}

			$is_ticket = in_array( $post->post_type, $ticket_types, true );

			// These are maps from the service slug to the meta key, so we keep only the meta key.
			$attendee_to_event_keys  = array_values( tribe_attendees()->attendee_to_event_keys() );
			$ticket_to_event_keys    = array_values( tribe_tickets()->ticket_to_event_keys() );
			$all_connected_meta_keys = array_merge( $attendee_to_event_keys, $ticket_to_event_keys );

			/** @var array<array<int>> $connected_event_ids */
			$connected_event_ids = [];
			foreach ( get_post_meta( $post_id ) as $meta_key => $meta_values ) {
				if ( ! in_array( $meta_key, $all_connected_meta_keys ) ) {
					continue;
				}

				$connected_event_ids[] = array_map( 'absint', $meta_values );

				if ( $is_ticket ) {
					// Invalidate some ticket-specific caches.
					wp_cache_delete( $post_id, 'tec_tickets' );
				}
			}
			/** @var array<int> $connected_event_ids */
			$connected_event_ids = array_merge( ...$connected_event_ids );
			$tribe_cache         = tribe_cache();
			$tickets_class       = Tickets_Tickets::class;

			foreach ( $connected_event_ids as $connected_event_id ) {
				// Reset the `Tribe__Tickets__Tickets::get_tickets` method cache to get the last version of them.
				$provider = Tickets_Tickets::get_event_ticket_provider_object( $connected_event_id );

				if ( $provider ) {
					$orm_provider                      = $provider->orm_provider;
					$tickets_cache_key                 = "{$tickets_class}::get_tickets-{$orm_provider}-{$connected_event_id}";
					$tribe_cache[ $tickets_cache_key ] = null;
				}

				$model = new self( $connected_event_id );
				// The call will trigger a priming of the model cache.
				$model->exist();
				$model->prime_cache();
			}
		}

		remove_action( 'parse_query', $do_not_cache_results );
	}

	/**
	 * {@inheritDoc}
	 */
	public function __get( $property ) {
		if ( 'exist' === $property ) {
			return $this->exist();
		}

		return $this->offsetGet( $property );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws InvalidArgumentException When trying to set a property.
	 */
	public function __set( $property, $value ) {
		throw new InvalidArgumentException( "The `Tickets::{$property}` property cannot be set." );
	}

	/**
	 * {@inheritDoc}
	 */
	public function __isset( $property ) {
		return $this->offsetExists( $property );
	}

	/**
	 * Returns the data about the event tickets, if any.
	 *
	 * @since 4.10.9
	 *
	 * @since 5.6.3 Add support for the updated anchor link from new ticket templates.
	 * @since 5.26.7 Fixed issue where empty arrays were being returned when data existed but was empty.
	 *
	 * @return array Ticket data or empty array.
	 */
	public function fetch_data() {
		if ( ! $this->exist() ) {
			return [];
		}

		if ( null !== $this->data && ! empty( $this->data ) ) {
			return $this->data;
		}

		$num_ticket_types_available = 0;
		foreach ( $this->all_tickets as $ticket ) {
			if ( ! tribe_events_ticket_is_on_sale( $ticket ) ) {
				continue;
			}

			++$num_ticket_types_available;
		}

		if ( ! $num_ticket_types_available ) {
			return [];
		}

		// Get an array for ticket and rsvp counts.
		$types = Tickets_Tickets::get_ticket_counts( $this->post_id );

		// If no rsvp or tickets return.
		if ( ! $types ) {
			return [];
		}

		$html        = [];
		$parts       = [];
		$stock_html  = '';
		$sold_out    = '';
		$link_label  = '';
		$link_anchor = '';

		// If we have tickets or RSVP, but everything is Sold Out then display the Sold Out message.
		foreach ( $types as $type => $data ) {

			if ( ! $data['count'] ) {
				continue;
			}

			if ( ! $data['available'] ) {
				if ( 'rsvp' === $type ) {
					$parts[ $type . '_stock' ] = esc_html_x( 'Currently full', 'events rsvp full (v2)', 'event-tickets' );
				} else {
					$parts[ $type . '_stock' ] = esc_html_x( 'Sold Out', 'events stock sold out (v2)', 'event-tickets' );
				}

				// Only re-apply if we don't have a stock yet.
				if ( empty( $html['stock'] ) ) {
					$html['stock'] = $parts[ $type . '_stock' ];
					$sold_out      = $parts[ $type . '_stock' ];
				}
			} else {
				$stock = $data['stock'];
				if ( $data['unlimited'] || ! $data['stock'] ) {
					// If unlimited tickets, tickets with no stock and rsvp, or no tickets and rsvp unlimited - hide the remaining count.
					$stock = false;
				}

				if ( $stock ) {
					/** @var Tribe__Settings_Manager $settings_manager */
					$settings_manager = tribe( 'settings.manager' );

					$threshold = $settings_manager::get_option( 'ticket-display-tickets-left-threshold', 0 );

					/**
					 * Overwrites the threshold to display "# tickets left".
					 *
					 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
					 * @param array $data      Ticket data.
					 * @param int   $event_id  Event ID.
					 *
					 * @since 4.10.1
					 */
					$threshold = absint( apply_filters( 'tribe_display_tickets_left_threshold', $threshold, $data, $this->post_id ) );

					if ( ! $threshold || $stock <= $threshold ) {

						$number = number_format_i18n( $stock );

						$ticket_label_singular = tribe_get_ticket_label_singular_lowercase( 'event-tickets' );
						$ticket_label_plural   = tribe_get_ticket_label_plural_lowercase( 'event-tickets' );

						if ( 'rsvp' === $type ) {
							/* translators: %1$s: Number of stock */
							$text = _n( '%1$s spot left', '%1$s spots left', $stock, 'event-tickets' );
						} else {
							// phpcs:disable -- to suppress WordPress.WP.I18n.MismatchedPlaceholders incorrect warning.
							/* translators: %1$s: Number of stock, %2$s: Ticket label, %3$s: Tickets label */
							$text = _n( '%1$s %2$s left', '%1$s %3$s left', $stock, 'event-tickets' );
							// phpcs:enable
						}

						$stock_html = esc_html( sprintf( $text, $number, $ticket_label_singular, $ticket_label_plural ) );
					}
				}

				$html['stock']             = $stock_html;
				$parts[ $type . '_stock' ] = $stock_html;

				if ( 'rsvp' === $type ) {
					/* Translators: RSVP singular label. */
					$link_label  = esc_html( sprintf( _x( '%s Now', 'list view rsvp now ticket button', 'event-tickets' ), tribe_get_rsvp_label_singular( 'list_view_rsvp_now_button' ) ) );
					$link_anchor = '#rsvp-now';
				} else {
					/* Translators: Tickets plural label. */
					$link_label  = esc_html( sprintf( _x( 'Get %s', 'list view buy now ticket button', 'event-tickets' ), tribe_get_ticket_label_plural( 'list_view_buy_now_button' ) ) );
					$link_anchor = tribe_tickets_new_views_is_enabled() ? '#tribe-tickets__tickets-form' : '#tribe-tickets';
				}
			}
		}

		$this->data['link'] = (object) [
			'anchor' => get_permalink( $this->post_id ) . $link_anchor,
			'label'  => $link_label,
		];

		$this->data['stock'] = (object) [
			'available' => $stock_html,
			'sold_out'  => $sold_out,
		];

		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetExists( $offset ): bool {
		$this->data = $this->fetch_data();

		return isset( $this->data[ $offset ] );
	}

	/**
	 * {@inheritDoc}
	 */
	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$this->data = $this->fetch_data();

		return $this->data[ $offset ] ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetSet( $offset, $value ): void {
		$this->data = $this->fetch_data();

		$this->data[ $offset ] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset( $offset ): void {
		$this->data = $this->fetch_data();

		unset( $this->data[ $offset ] );
	}

	/**
	 * Returns an array representation of the event tickets data.
	 *
	 * @since 4.10.9
	 *
	 * @return array An array representation of the event tickets data.
	 */
	public function to_array() {
		$this->data = $this->fetch_data();

		return json_decode( wp_json_encode( $this->data ), true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function serialize() {
		$data            = $this->fetch_data();
		$data['post_id'] = $this->post_id;

		// Kept for back-compatibility reasons.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		return serialize( $this->__serialize() );
	}

	/**
	 * PHP 8.0+ compatible implementation of the serialization logic.
	 *
	 * @since 5.7.0
	 *
	 * @return array The data to serialize.
	 */
	public function __serialize(): array { // phpcs:ignore StellarWP.NamingConventions.ValidFunctionName.MethodDoubleUnderscore
		$data            = $this->fetch_data();
		$data['post_id'] = $this->post_id;

		return $data;
	}

	/**
	 * PHP 8.0+ compatible implementation of the unserialization logic.
	 *
	 * @since 5.7.0
	 *
	 * @param array $data The data to unserialize.
	 */
	public function __unserialize( array $data ): void { // phpcs:ignore StellarWP.NamingConventions.ValidFunctionName.MethodDoubleUnderscore
		$this->post_id = $data['post_id'] ?? null;
		$this->data    = $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function unserialize( $serialized ) {
		// Kept for back-compatibility reasons.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$data = unserialize( $serialized );
		$this->__unserialize( $data );

		unset( $data['post_id'] );
	}

	/**
	 * Returns whether an event has tickets at all or not.
	 *
	 * @since 4.10.9
	 *
	 * @return bool Whether an event has tickets at all or not.
	 */
	public function exist() {
		if ( null !== $this->exists ) {
			return $this->exists;
		}

		$this->all_tickets = Tickets_Tickets::get_all_event_tickets( $this->post_id );

		$this->exists = ! empty( $this->all_tickets );

		if ( ! $this->is_cached ) {
			$this->prime_cache();
		}

		return $this->exists;
	}

	/**
	 * Returns whether an event has tickets in date range.
	 *
	 * @since 4.12.0
	 *
	 * @return bool Whether an event has tickets in date range
	 */
	public function in_date_range() {
		if ( ! $this->post_id ) {
			return false;
		}

		return tribe_tickets_is_current_time_in_date_window( $this->post_id );
	}

	/**
	 * Returns whether an event has its tickets sold out.
	 *
	 * @since 4.12.0
	 *
	 * @return bool Whether an event has its tickets sold out.
	 */
	public function sold_out() {
		$data = $this->fetch_data();

		return ! empty( $data['stock']->sold_out );
	}

	/**
	 * Primes the model cache from the key-value cache, if possible.
	 *
	 * @since 5.26.1
	 *
	 * @return void
	 */
	private function restore_from_cache(): void {
		$cached = tec_kv_cache()->get( self::get_cache_key( $this->post_id ) );

		if ( $cached === '' ) {
			return;
		}

		$unpacked = tec_json_unpack( $cached, true, [ Ticket_Object::class ] );

		if ( ! ( is_array( $unpacked ) && isset( $unpacked['all_tickets'], $unpacked['data'] ) ) ) {
			return;
		}

		$this->all_tickets = $unpacked['all_tickets'];
		$this->data        = $unpacked['data'];
		$this->is_cached   = true;
	}

	/**
	 * Returns the model cache key used to store it in the key-value cache.
	 *
	 * @since 5.26.1
	 *
	 * @param int $post_id The post ID to provide the cache key for.
	 *
	 * @return string The model cache key used to store it in the key-value cache.
	 */
	public static function get_cache_key( int $post_id ): string {
		return 'tec_tickets_views_v2_model_ticket_' . $post_id;
	}

	/**
	 * Primes the key-value cache for the model.
	 *
	 * @since 5.26.1
	 *
	 * @return void
	 */
	private function prime_cache(): void {
		/*
		* Unset the `provider` property of each Ticket to avoid storing a singleton object.
		* The ticket will recover the module instance using the `provider_class` property.
		*/
		foreach ( $this->all_tickets as $ticket ) {
			// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Faster than Reflection.
			Closure::bind( fn() => $ticket->provider = null, $ticket, Ticket_Object::class )();
		}

		$packed = tec_json_pack(
			[
				'all_tickets' => $this->all_tickets,
				'data'        => $this->fetch_data(),
			],
			[ Ticket_Object::class ]
		);

		tec_kv_cache()->set( self::get_cache_key( $this->post_id ), $packed, DAY_IN_SECONDS );
	}
}
