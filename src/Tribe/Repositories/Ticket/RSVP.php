<?php
/**
 * RSVP Ticket Repository.
 *
 * Handles ORM operations for RSVP tickets.
 *
 * @since 4.10.6
 *
 * @package Tribe\Tickets\Repositories\Ticket
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

use TEC\Tickets\Repositories\Traits\Get_Field;
use Tribe__Cache_Listener as Cache_Listener;

/**
 * The ORM/Repository class for RSVP tickets.
 *
 * @since 4.10.6
 */
class Tribe__Tickets__Repositories__Ticket__RSVP extends Tribe__Tickets__Ticket_Repository {

	use Get_Field;

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		$post_type = ( $this->ticket_types() )['rsvp'] ?? 'tribe_rsvp_tickets';

		$this->create_args['post_type'] = $post_type;

		$this->default_args = [
			'post_type' => $post_type,
			'orderby'   => [ 'date', 'ID' ],
		];

		// Add RSVP-specific field aliases.
		$this->update_fields_aliases = array_merge(
			$this->update_fields_aliases,
			[
				'event_id'          => '_tribe_rsvp_for_event',
				'price'             => '_price',
				'stock'             => '_stock',
				'sales'             => 'total_sales',
				'manage_stock'      => '_manage_stock',
				'start_date'        => '_ticket_start_date',
				'end_date'          => '_ticket_end_date',
				'show_description'  => '_tribe_ticket_show_description',
				'show_not_going'    => '_tribe_rsvp_show_not_going',
				'capacity'          => '_tribe_ticket_capacity',
				'global_stock_mode' => '_global_stock_mode',
				'global_stock_cap'  => '_global_stock_cap',
			]
		);

		$this->add_schema_entry( 'attendee_id', [ $this, 'filter_by_attendee_id' ] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_types() {
		$types = parent::ticket_types();

		$types = [
			'rsvp' => $types['rsvp'],
		];

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_to_event_keys() {
		$keys = parent::ticket_to_event_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * Atomically adjust ticket sales and stock.
	 *
	 * This method performs atomic read-modify-write operations to prevent
	 * race conditions during concurrent ticket purchases.
	 *
	 * @since 5.28.0
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $delta     The change in sales (positive = increase, negative = decrease).
	 *
	 * @return int|false New sales count or false on failure.
	 */
	public function adjust_sales( int $ticket_id, int $delta ) {
		global $wpdb;

		// Check if ticket exists first.
		if ( ! get_post( $ticket_id ) ) {
			return false;
		}

		$ticket_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );

		// Ensure meta keys exist so subsequent atomic SQL updates can modify them. If keys don't exist,
		// the UPDATE query will silently fail, breaking the atomicity guarantee.
		if ( ! metadata_exists( 'post', $ticket_id, 'total_sales' ) ) {
			add_post_meta( $ticket_id, 'total_sales', 0, true );
		}
		if ( ! metadata_exists( 'post', $ticket_id, '_stock' ) ) {
			add_post_meta( $ticket_id, '_stock', 0, true );
		}

		/*
		 * Use atomic SQL with GREATEST/LEAST functions to prevent race conditions during concurrent
		 * ticket purchases. Without atomic operations, two simultaneous requests could read the same
		 * value, modify it, and write back incorrect totals. GREATEST(0, ...) ensures sales never go
		 * negative even when processing cancellations concurrently.
		 */
		$sales_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d)
				 WHERE post_id = %d AND meta_key = 'total_sales'",
				$delta,
				$ticket_id
			)
		);

		/*
		 * Stock calculation uses LEAST to cap at capacity, preventing stock from exceeding ticket
		 * limits when processing cancellations. Combined with GREATEST(0, ...), this ensures stock
		 * stays within valid bounds [0, capacity] even under concurrent modifications.
		 */
		$stock_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				 SET meta_value = LEAST(GREATEST(0, CAST(meta_value AS SIGNED) - %d), %d)
				 WHERE post_id = %d AND meta_key = '_stock'",
				$delta,
				$ticket_capacity,
				$ticket_id
			)
		);

		if ( false === $sales_result || false === $stock_result ) {
			return false;
		}

		/*
		 * Clear post meta cache so subsequent get_post_meta() calls fetch fresh data from the database.
		 * Without this, the same request could read stale cached values after the atomic SQL update.
		 */
		wp_cache_delete( $ticket_id, 'post_meta' );

		/*
		 * Trigger save_post to invalidate dependent caches like attendance totals, REST API responses,
		 * and any other data derived from ticket sales/stock values. This ensures consistency across
		 * the entire system, not just for this immediate request.
		 */
		Cache_Listener::instance()->save_post( $ticket_id, get_post( $ticket_id ) );

		// Return the new sales count to confirm the atomic operation succeeded and provide updated state.
		return (int) get_post_meta( $ticket_id, 'total_sales', true );
	}

	/**
	 * Get the event ID for a ticket.
	 *
	 * @since 5.28.0
	 *
	 * @param int $ticket_id Ticket ID.
	 *
	 * @return int|false Event ID or false if not found.
	 */
	public function get_event_id( int $ticket_id ) {
		$event_id = $this->get_field( $ticket_id, 'event_id' );

		return $event_id ? (int) $event_id : false;
	}

	/**
	 * Duplicate a ticket with optional field overrides.
	 *
	 * Uses repository methods for proper creation and meta handling.
	 *
	 * @since 5.28.0
	 *
	 * @param int   $ticket_id Ticket ID to duplicate.
	 * @param array $overrides Optional field overrides (e.g., ['title' => 'New Name']).
	 *
	 * @return int|false New ticket ID or false on failure.
	 */
	public function duplicate( int $ticket_id, array $overrides = [] ) {
		$ticket = $this->by( 'id', $ticket_id )->first();

		if ( ! $ticket ) {
			return false;
		}

		$all_meta = get_post_meta( $ticket_id );
		$aliases  = $this->get_update_fields_aliases();

		$ticket_data = [
			'title'      => $ticket->post_title,
			'excerpt'    => $ticket->post_excerpt,
			'content'    => $ticket->post_content,
			'status'     => 'publish',
			'author'     => get_current_user_id(),
			'menu_order' => $ticket->menu_order,
		];

		foreach ( $aliases as $alias => $meta_key ) {
			$value = $all_meta[ $meta_key ][0] ?? '';
			if ( '' !== $value ) {
				$ticket_data[ $alias ] = maybe_unserialize( $value );
			}
		}

		$ticket_data = array_merge( $ticket_data, $overrides );

		unset( $ticket_data['ID'], $ticket_data['id'] );

		$new_ticket = $this->set_args( $ticket_data )->create();

		return $new_ticket instanceof \WP_Post ? $new_ticket->ID : false;
	}

	/**
	 * Delete meta field from a ticket.
	 *
	 * Provides repository-level meta deletion using WordPress meta API.
	 * Uses field aliases from the repository for consistency.
	 *
	 * @since 5.28.0
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $field     Field name (can be alias or meta key).
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_meta( $ticket_id, $field ) {
		// Resolve field alias to actual meta key.
		$meta_key = Tribe__Utils__Array::get( $this->update_fields_aliases, $field, $field );

		// Use WordPress meta API for deletion.
		return delete_post_meta( $ticket_id, $meta_key );
	}

	/**
	 * Filters tickets by attendee ID.
	 *
	 * @since 5.19.0
	 *
	 * @param int $value The attendee ID.
	 *
	 * @return void
	 */
	public function filter_by_attendee_id( int $value ): void {
		$ticket_id = get_post_meta( $value, \Tribe__Tickets__RSVP::ATTENDEE_PRODUCT_KEY, true );

		if ( ! $ticket_id ) {
			$this->void_query( true );
			return;
		}

		$this->by( 'id', $ticket_id );
	}
}
