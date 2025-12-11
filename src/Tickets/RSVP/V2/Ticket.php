<?php
/**
 * Handles RSVP V2 ticket operations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\RSVP\V2\Traits\Is_RSVP;
use Tribe__Tickets__Global_Stock as Global_Stock;
use WP_Error;

/**
 * Class Ticket.
 *
 * Handles creation, update, deletion and stock management for RSVP tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Ticket {

	use Is_RSVP;

	/**
	 * Meta key that holds the "not going" option visibility status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SHOW_NOT_GOING_META_KEY = '_tribe_ticket_show_not_going';

	/**
	 * Creates a new RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id The post ID to attach the ticket to.
	 * @param array $args    The ticket arguments.
	 *                       - name: (required) The ticket name.
	 *                       - description: (optional) The ticket description.
	 *                       - capacity: (optional) The ticket capacity. -1 for unlimited.
	 *                       - show_not_going: (optional) Whether to show the "not going" option. Default false.
	 *                       - start_date: (optional) When the RSVP opens.
	 *                       - end_date: (optional) When the RSVP closes.
	 *
	 * @return int|WP_Error The ticket ID on success, WP_Error on failure.
	 */
	public function create( int $post_id, array $args ) {
		if ( empty( $args['name'] ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_missing_name',
				__( 'RSVP ticket name is required.', 'event-tickets' )
			);
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_post',
				__( 'Invalid post ID.', 'event-tickets' )
			);
		}

		$ticket_args = [
			'post_status'  => 'publish',
			'post_type'    => TC_Ticket::POSTTYPE,
			'post_author'  => get_current_user_id(),
			'post_title'   => sanitize_text_field( $args['name'] ),
			'post_excerpt' => isset( $args['description'] ) ? wp_kses_post( $args['description'] ) : '',
			'menu_order'   => isset( $args['menu_order'] ) ? absint( $args['menu_order'] ) : 0,
		];

		$ticket_id = wp_insert_post( $ticket_args );

		if ( is_wp_error( $ticket_id ) ) {
			return $ticket_id;
		}

		// Set the ticket type (both pinged column and meta).
		$this->set_ticket_type( $ticket_id );

		// Relate event <--> ticket.
		add_post_meta( $ticket_id, TC_Ticket::$event_relation_meta_key, $post_id );

		// Set price to 0 (RSVPs are free).
		update_post_meta( $ticket_id, '_price', '0' );

		// Handle capacity.
		$this->set_capacity( $ticket_id, $post_id, $args );

		// Handle "show not going" option.
		$show_not_going = isset( $args['show_not_going'] ) && $args['show_not_going'];
		update_post_meta( $ticket_id, self::SHOW_NOT_GOING_META_KEY, $show_not_going ? 'yes' : 'no' );

		// Handle dates.
		$this->set_dates( $ticket_id, $args );

		/**
		 * Fires after an RSVP V2 ticket is created.
		 *
		 * @since TBD
		 *
		 * @param int   $ticket_id The ticket ID.
		 * @param int   $post_id   The post ID.
		 * @param array $args      The ticket arguments.
		 */
		do_action( 'tec_tickets_rsvp_v2_ticket_created', $ticket_id, $post_id, $args );

		return $ticket_id;
	}

	/**
	 * Updates an existing RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The ticket ID to update.
	 * @param array $args      The ticket arguments to update.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function update( int $ticket_id, array $args ): bool {
		if ( ! $this->is_rsvp_ticket( $ticket_id ) ) {
			return false;
		}

		$post_args = [];

		if ( isset( $args['name'] ) ) {
			$post_args['post_title'] = sanitize_text_field( $args['name'] );
		}

		if ( isset( $args['description'] ) ) {
			$post_args['post_excerpt'] = wp_kses_post( $args['description'] );
		}

		if ( isset( $args['menu_order'] ) ) {
			$post_args['menu_order'] = absint( $args['menu_order'] );
		}

		if ( ! empty( $post_args ) ) {
			$post_args['ID'] = $ticket_id;
			$result          = wp_update_post( $post_args );

			if ( is_wp_error( $result ) || ! $result ) {
				return false;
			}
		}

		// Handle "show not going" option.
		if ( isset( $args['show_not_going'] ) ) {
			update_post_meta( $ticket_id, self::SHOW_NOT_GOING_META_KEY, $args['show_not_going'] ? 'yes' : 'no' );
		}

		// Handle capacity update.
		if ( isset( $args['capacity'] ) ) {
			$post_id = get_post_meta( $ticket_id, TC_Ticket::$event_relation_meta_key, true );
			$this->set_capacity( $ticket_id, (int) $post_id, $args );
		}

		// Handle dates.
		$this->set_dates( $ticket_id, $args );

		/**
		 * Fires after an RSVP V2 ticket is updated.
		 *
		 * @since TBD
		 *
		 * @param int   $ticket_id The ticket ID.
		 * @param array $args      The ticket arguments.
		 */
		do_action( 'tec_tickets_rsvp_v2_ticket_updated', $ticket_id, $args );

		return true;
	}

	/**
	 * Deletes (trashes) an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID to delete.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete( int $ticket_id ): bool {
		if ( ! $this->is_rsvp_ticket( $ticket_id ) ) {
			return false;
		}

		$post_id = get_post_meta( $ticket_id, TC_Ticket::$event_relation_meta_key, true );

		// Trash the ticket (don't permanently delete).
		$result = wp_trash_post( $ticket_id );

		if ( ! $result ) {
			return false;
		}

		/**
		 * Fires after an RSVP V2 ticket is deleted.
		 *
		 * @since TBD
		 *
		 * @param int $ticket_id The ticket ID.
		 * @param int $post_id   The post ID.
		 */
		do_action( 'tec_tickets_rsvp_v2_ticket_deleted', $ticket_id, (int) $post_id );

		/**
		 * Fires after an RSVP ticket is deleted.
		 *
		 * V1 backwards compatibility hook.
		 *
		 * @since TBD
		 *
		 * @param int $ticket_id  The ticket ID.
		 * @param int $event_id   The event/post ID.
		 * @param int $product_id The product ID (same as ticket_id in V2).
		 */
		do_action( 'tickets_rsvp_ticket_deleted', $ticket_id, (int) $post_id, $ticket_id );

		return true;
	}

	/**
	 * Gets the available capacity for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int The available capacity. -1 for unlimited.
	 */
	public function get_available( int $ticket_id ): int {
		if ( ! $this->is_rsvp_ticket( $ticket_id ) ) {
			return 0;
		}

		$stock        = (int) get_post_meta( $ticket_id, '_stock', true );
		$manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );

		// Unlimited capacity.
		if ( 'no' === $manage_stock || '' === $manage_stock ) {
			return -1;
		}

		return max( 0, $stock );
	}

	/**
	 * Updates the stock for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id The ticket ID.
	 * @param int    $quantity  The quantity to adjust by.
	 * @param string $operation The operation: 'increase' or 'decrease'.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function update_stock( int $ticket_id, int $quantity, string $operation ): bool {
		if ( ! $this->is_rsvp_ticket( $ticket_id ) ) {
			return false;
		}

		$current_stock = (int) get_post_meta( $ticket_id, '_stock', true );

		switch ( $operation ) {
			case 'increase':
				$new_stock = $current_stock + $quantity;
				break;

			case 'decrease':
				$new_stock = max( 0, $current_stock - $quantity );
				break;

			default:
				return false;
		}

		update_post_meta( $ticket_id, '_stock', $new_stock );

		// Update stock status.
		$status = $new_stock > 0 ? 'instock' : 'outofstock';
		update_post_meta( $ticket_id, '_stock_status', $status );

		// Update sales count.
		if ( 'decrease' === $operation ) {
			$sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
			update_post_meta( $ticket_id, 'total_sales', $sales + $quantity );
		} elseif ( 'increase' === $operation ) {
			$sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
			update_post_meta( $ticket_id, 'total_sales', max( 0, $sales - $quantity ) );
		}

		return true;
	}

	/**
	 * Checks if a ticket has capacity available.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return bool True if capacity is available, false otherwise.
	 */
	public function has_capacity( int $ticket_id ): bool {
		$available = $this->get_available( $ticket_id );

		// -1 means unlimited.
		if ( -1 === $available ) {
			return true;
		}

		return $available > 0;
	}

	/**
	 * Sets the ticket type discriminator.
	 *
	 * Sets both the pinged column (for efficient queries) and _type meta.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	public function set_ticket_type( int $ticket_id ): void {
		global $wpdb;

		// Set the pinged column to 'tc-rsvp' for efficient database queries.
		$wpdb->update(
			$wpdb->posts,
			[ 'pinged' => Meta::TC_RSVP_TYPE ],
			[ 'ID' => $ticket_id ],
			[ '%s' ],
			[ '%d' ]
		);

		// Also set the _type meta for redundancy.
		update_post_meta( $ticket_id, Meta::TYPE_META_KEY, Meta::TC_RSVP_TYPE );

		// Clear the post cache.
		clean_post_cache( $ticket_id );
	}

	/**
	 * Filters RSVP tickets out of a TC ticket list.
	 *
	 * @since TBD
	 *
	 * @param array $tickets The tickets array.
	 *
	 * @return array The filtered tickets array.
	 */
	public function filter_out_rsvp_tickets( array $tickets ): array {
		return array_filter(
			$tickets,
			function ( $ticket ) {
				$ticket_id = is_object( $ticket ) ? $ticket->ID : ( is_array( $ticket ) ? $ticket['ID'] : $ticket );
				return ! $this->is_rsvp_ticket( (int) $ticket_id );
			}
		);
	}

	/**
	 * Adds the ticket post type to the cache listener.
	 *
	 * @since TBD
	 *
	 * @param array $post_types The post types.
	 *
	 * @return array The post types with ticket post type added.
	 */
	public function add_post_type_to_cache( array $post_types ): array {
		$post_types[] = TC_Ticket::POSTTYPE;

		return array_unique( $post_types );
	}

	/**
	 * Gets all RSVP ticket IDs for a post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int[] Array of ticket IDs.
	 */
	public function get_tickets_for_post( int $post_id ): array {
		$tickets = get_posts(
			[
				'post_type'      => TC_Ticket::POSTTYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => '_tec_tickets_commerce_event',
						'value' => $post_id,
					],
				],
			] 
		);

		// Filter to only RSVP tickets.
		return array_filter( $tickets, [ $this, 'is_rsvp_ticket' ] );
	}

	/**
	 * Sets the capacity for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The ticket ID.
	 * @param int   $post_id   The post ID.
	 * @param array $args      The ticket arguments.
	 *
	 * @return void
	 */
	protected function set_capacity( int $ticket_id, int $post_id, array $args ): void {
		$capacity = isset( $args['capacity'] ) ? (int) $args['capacity'] : -1;

		if ( -1 === $capacity ) {
			// Unlimited capacity.
			update_post_meta( $ticket_id, '_manage_stock', 'no' );
			delete_post_meta( $ticket_id, '_stock' );
			delete_post_meta( $ticket_id, '_stock_status' );
			update_post_meta( $ticket_id, Global_Stock::TICKET_STOCK_MODE, Global_Stock::OWN_STOCK_MODE );
		} else {
			// Limited capacity.
			update_post_meta( $ticket_id, '_manage_stock', 'yes' );
			update_post_meta( $ticket_id, '_stock', $capacity );
			update_post_meta( $ticket_id, '_stock_status', $capacity > 0 ? 'instock' : 'outofstock' );
			update_post_meta( $ticket_id, Global_Stock::TICKET_STOCK_MODE, Global_Stock::OWN_STOCK_MODE );
			update_post_meta( $ticket_id, '_backorders', 'no' );

			// Set capacity meta.
			/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
			$tickets_handler = tribe( 'tickets.handler' );
			update_post_meta( $ticket_id, $tickets_handler->key_capacity, $capacity );
		}
	}

	/**
	 * Sets the dates for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The ticket ID.
	 * @param array $args      The ticket arguments.
	 *
	 * @return void
	 */
	protected function set_dates( int $ticket_id, array $args ): void {
		$date_keys = [
			'start_date' => TC_Ticket::START_DATE_META_KEY,
			'start_time' => TC_Ticket::START_TIME_META_KEY,
			'end_date'   => TC_Ticket::END_DATE_META_KEY,
			'end_time'   => TC_Ticket::END_TIME_META_KEY,
		];

		foreach ( $date_keys as $arg_key => $meta_key ) {
			if ( isset( $args[ $arg_key ] ) ) {
				if ( empty( $args[ $arg_key ] ) ) {
					delete_post_meta( $ticket_id, $meta_key );
				} else {
					update_post_meta( $ticket_id, $meta_key, sanitize_text_field( $args[ $arg_key ] ) );
				}
			}
		}
	}
}
