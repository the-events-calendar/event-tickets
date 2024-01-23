<?php
/**
 * Handles the caching of Ticket objects.
 *
 * @since   5.6.4
 *
 * @package TEC\Tickets;
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Tickets\Commerce\Attendee as Commerce_Attendee;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Commerce__PayPal__Order as PayPal_Order;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket;
use WP_Post;

/**
 * Class Ticket_Cache_Controller.
 *
 * @since   5.6.4
 *
 * @package TEC\Tickets;
 */
class Ticket_Cache_Controller extends Controller {
	/**
	 * The action to fire when the provider is registered.
	 *
	 * @since 5.6.4
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_cache_controller_registered';

	/**
	 * Hooks the Cache Controller to the appropriate actions.
	 *
	 * @since 5.6.4
	 *
	 * @return void The Cache Controller is hooked to the appropriate actions.
	 */
	protected function do_register(): void {
		// Flush the ticket cache when a ticket is saved.
		add_action( 'event_tickets_after_save_ticket', [ $this, 'clean_ticket_cache_on_save' ], 10, 2 );
		// Flush the cache when a Ticket post cache is programmatically cleared.
		add_action( 'clean_post_cache', [ $this, 'clean_ticket_cache' ], 10, 2 );
		// This will cover both creation and update of related posts like Attendees and Orders.
		add_action( 'save_post', [ $this, 'clean_ticket_cache_from_related_post' ], 10, 2 );
		// Use the `before` hook to be able to access the metadata of Attendees and Orders.
		add_action( 'before_delete_post', [ $this, 'clean_ticket_cache_from_related_post' ], 10, 2 );
		// Clean the cache when a Ticket meta changes in any way: this is on the cautious side.
		add_action( 'add_post_meta', [ $this, 'clean_ticket_cache_on_meta_update' ], 10, 1 );
		add_action( 'update_post_meta', [ $this, 'clean_ticket_cache_on_meta_update_delete' ], 10, 2 );
		add_action( 'delete_post_meta', [ $this, 'clean_ticket_cache_on_meta_update_delete' ], 10, 2 );
	}

	/**
	 * Unregister the cache controller.
	 *
	 * @since 5.6.4
	 *
	 * @return void The cache controller is unregistered.
	 */
	public function unregister(): void {
		remove_action( 'event_tickets_after_save_ticket', [ $this, 'clean_ticket_cache_on_save' ], 10, 2 );
		remove_action( 'clean_post_cache', [ $this, 'clean_ticket_cache' ], 10, 2 );
		remove_action( 'save_post', [ $this, 'clean_ticket_cache_from_related_post' ], 10, 2 );
		remove_action( 'before_delete_post', [ $this, 'clean_ticket_cache_from_related_post' ], 10, 2 );
		remove_action( 'add_post_meta', [ $this, 'clean_ticket_cache_on_meta_update' ], 10, 1 );
		remove_action( 'update_post_meta', [ $this, 'clean_ticket_cache_on_meta_update_delete' ], 10, 2 );
		remove_action( 'delete_post_meta', [ $this, 'clean_ticket_cache_on_meta_update_delete' ], 10, 2 );
	}

	/**
	 * Clean the ticket cache when a ticket is saved.
	 *
	 * The ticket cache will be rehydrated on the next request for the Ticket.
	 *
	 * @since 5.6.4
	 *
	 * @param int    $post_id The ID of the Post the ticket is attached to, unused by this method.
	 * @param Ticket $ticket  The Ticket object that was saved.
	 *
	 * @return void
	 */
	public function clean_ticket_cache_on_save( $post_id, $ticket ): void {
		if ( ! $ticket instanceof Ticket ) {
			return;
		}

		wp_cache_delete( (int) $ticket->ID, 'tec_tickets' );
	}

	/**
	 * Clean the ticket cache when the post cache is cleaned.
	 *
	 * @since 5.6.4
	 *
	 * @param int $post_id The ID of the Ticket post.
	 *
	 * @return void
	 */
	public function clean_ticket_cache( $post_id ): void {
		if ( ! is_numeric( $post_id ) ) {
			return;
		}

		// Delete caches associated with the Ticket Object not stored in WordPress post cache.
		$class = \Tribe__Tickets__Ticket_Object::class;
		foreach (
			[
				$class . '::is_in_stock-' . $post_id,
				$class . '::inventory-' . $post_id,
				$class . '::available-' . $post_id,
				$class . '::capacity-' . $post_id,
			] as $cache_key
		) {
			tribe_cache()->delete( $cache_key, \Tribe__Cache_Listener::TRIGGER_SAVE_POST );
		}

		// Checking the post type would require more time (due to filtering) than trying to delete a non-existing key.
		wp_cache_delete( (int) $post_id, 'tec_tickets' );
	}

	/**
	 * Fetches the ticket IDs from a PayPal order.
	 *
	 * @since 5.6.4
	 *
	 * @param int $order_id The ID of the PayPal order.
	 *
	 * @return array<int> The IDs of the tickets in the order.
	 */
	public function get_ticket_ids_from_paypal_order( int $order_id ): array {
		$items = get_post_meta( $order_id, PayPal_Order::$meta_prefix . 'items', true );

		if ( empty( $items ) || ! is_array( $items ) ) {
			return [];
		}

		return array_column( $items, 'ticket_id' );
	}

	/**
	 * Clean the ticket cache when a related post is created, updated or deleted.
	 *
	 * @since 5.6.4
	 *
	 * @param int     $post_id The ID of the post being deleted.
	 * @param WP_Post $post    The post object being deleted.
	 *
	 * @return void The ticket cache is cleaned if the post is related to a ticket.
	 */
	public function clean_ticket_cache_from_related_post( $post_id, $post ): void {
		if ( ! is_int( $post_id ) && $post instanceof WP_Post ) {
			return;
		}

		$post_type = $post->post_type;

		$ticket_related_post_types = [
			// A Commerce Attendee is created, updated or deleted.
			Commerce_Attendee::POSTTYPE => Commerce_Attendee::$ticket_relation_meta_key,
			// A PayPal attendee is created, updated or deleted.
			PayPal::ATTENDEE_OBJECT     => PayPal::ATTENDEE_PRODUCT_KEY,
			// An RSVP attendee is created, updated or deleted.
			RSVP::ATTENDEE_OBJECT       => RSVP::ATTENDEE_PRODUCT_KEY,
			// A PayPal order is created, updated or deleted.
			PayPal::ORDER_OBJECT        => [ $this, 'get_ticket_ids_from_paypal_order' ],
			// A Commerce Order is created, updated or deleted.
			Commerce_Order::POSTTYPE    => Commerce_Order::$tickets_in_order_meta_key,
		];

		/**
		 * Filter the map from post types to the meta key used to store the related ticket IDs for the
		 * purpose of cache invalidation or the callable used to retrieve the ticket IDs.
		 *
		 * @since 5.6.4
		 *
		 * @param array<string,string|callable> $ticket_related_post_types The map from post types to the meta key used
		 *                                                                 to store the related ticket IDs. If the value
		 *                                                                 is a callable, it will be called with the
		 *                                                                 post ID as the only argument and should
		 *                                                                 return an array of ticket IDs.
		 */
		$ticket_related_post_types = apply_filters(
			'tec_tickets_ticket_cache_related_post_types',
			$ticket_related_post_types
		);

		if ( ! array_key_exists( $post_type, $ticket_related_post_types ) ) {
			return;
		}

		$relationship_meta_key = $ticket_related_post_types[ $post_type ];

		if ( is_callable( $relationship_meta_key ) ) {
			$ticket_ids = $relationship_meta_key( $post_id );
		} else {
			// In the case of Orders, we'll get an array of ticket IDs.
			$ticket_ids = get_post_meta( $post_id, $relationship_meta_key, false );
		}

		foreach ( $ticket_ids as $ticket_id ) {
			$this->clean_ticket_cache( $ticket_id );
		}
	}

	/**
	 * Clean the ticket cache when one of its meta fields is added, updated or deleted.
	 *
	 * @since 5.6.4
	 *
	 * @param int $object_id The ID of the object the meta data is attached to.
	 *
	 * @return void The ticket cache is cleaned if the meta data is related to a ticket.
	 */
	public function clean_ticket_cache_on_meta_update( $object_id ): void {
		if ( ! is_int( $object_id ) ) {
			return;
		}

		$post_type = get_post_type( $object_id );

		$ticket_post_types = [
			Commerce_Ticket::POSTTYPE,
			'tribe_tpp_tickets', // PayPal: hard-coded to avoid having to instantiate the class.
			'tribe_rsvp_tickets' // RSVP: hard-coded to avoid having to instantiate the class.
		];

		/**
		 * Filter the list of post types that are considered to be tickets for the purpose of cache
		 * invalidation.
		 *
		 * @since 5.6.4
		 *
		 * @param array<string> $ticket_post_types The list of post types that are considered to be tickets.
		 */
		$ticket_post_types = apply_filters( 'tec_tickets_ticket_cache_post_types', $ticket_post_types );

		if ( in_array( $post_type, $ticket_post_types, true ) ) {
			$this->clean_ticket_cache( $object_id );
		}
	}

	/**
	 * Clean the ticket cache when one of its meta fields is deleted or updated.
	 *
	 * @since 5.6.4
	 *
	 * @param int|array<int> $meta_ids  The ID(s) of the meta data being deleted; unused by this method.
	 * @param int            $object_id The ID of the object the meta data is attached to.
	 *
	 * @return void The ticket cache is cleaned if the meta data is related to a ticket.
	 */
	public function clean_ticket_cache_on_meta_update_delete( $meta_ids, $object_id ): void {
		if ( ! is_int( $object_id ) ) {
			return;
		}

		$this->clean_ticket_cache_on_meta_update( $object_id );
	}
}