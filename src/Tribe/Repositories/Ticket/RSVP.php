<?php
/**
 * RSVP Ticket Repository.
 *
 * Provides ORM/Repository methods for RSVP ticket data access and manipulation.
 *
 * @since 4.10.6
 *
 * @package Tribe\Tickets\Repositories\Ticket
 */

use Tribe__Utils__Array as Arr;

/**
 * The ORM/Repository class for RSVP tickets.
 *
 * Class name follows TEC naming convention with double underscores.
 *
 * @since 4.10.6
 */
// phpcs:ignore StellarWP.Classes.ValidClassName.NotSnakeCase, Squiz.Commenting.ClassComment.Missing
class Tribe__Tickets__Repositories__Ticket__RSVP extends Tribe__Tickets__Ticket_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

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
				'description'       => 'post_excerpt',
			]
		);
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
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $delta     The change in sales (positive = increase, negative = decrease).
	 *
	 * @return int|false New sales count or false on failure.
	 */
	public function adjust_sales( $ticket_id, $delta ) {
		global $wpdb;

		// Check if ticket exists first.
		$ticket = get_post( $ticket_id );
		if ( ! $ticket ) {
			return false;
		}

		// Get current sales to calculate actual change.
		$old_sales    = (int) get_post_meta( $ticket_id, 'total_sales', true );
		$new_sales    = max( 0, $old_sales + $delta );
		$actual_delta = $new_sales - $old_sales;

		// Atomic UPDATE for sales - prevents race conditions.
		$sales_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d)
				 WHERE post_id = %d AND meta_key = 'total_sales'",
				$delta,
				$ticket_id
			)
		);

		// Atomic UPDATE for stock - inverse of actual sales change.
		// Use actual_delta instead of delta to handle clamping correctly.
		$stock_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) - %d)
				 WHERE post_id = %d AND meta_key = '_stock'",
				$actual_delta,
				$ticket_id
			)
		);

		if ( false === $sales_result || false === $stock_result ) {
			return false;
		}

		// Clear cache.
		wp_cache_delete( $ticket_id, 'post_meta' );

		// Get new sales count.
		return (int) get_post_meta( $ticket_id, 'total_sales', true );
	}

	/**
	 * Get a single field value without loading full ticket object.
	 *
	 * Useful for quick lookups when you only need one field value.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $field     Field name (alias-aware, e.g., 'price', 'event_id').
	 *
	 * @return mixed Field value or null if not found.
	 */
	public function get_field( $ticket_id, $field ) {
		// Get the meta key for this field.
		$meta_key = Arr::get( $this->update_fields_aliases, $field, null );

		// If field is not aliased, try using the field name directly as meta key.
		if ( null === $meta_key ) {
			$meta_key = $field;
		}

		$value = get_post_meta( $ticket_id, $meta_key, true );

		// Return null if meta doesn't exist (empty string or false).
		return ( '' === $value || false === $value ) ? null : $value;
	}

	/**
	 * Get the event ID for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id Ticket ID.
	 *
	 * @return int|false Event ID or false if not found.
	 */
	public function get_event_id( $ticket_id ) {
		$event_id = $this->get_field( $ticket_id, 'event_id' );

		return $event_id ? (int) $event_id : false;
	}

	/**
	 * Duplicate a ticket with optional field overrides.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id Ticket ID to duplicate.
	 * @param array $overrides Optional field overrides (e.g., ['name' => 'New Name']).
	 *
	 * @return int|false New ticket ID or false on failure.
	 */
	public function duplicate( $ticket_id, array $overrides = [] ) {
		$original = $this->by( 'id', $ticket_id )->first();

		if ( ! $original ) {
			return false;
		}

		// Convert to array and merge overrides.
		$ticket_data = $this->ticket_to_array( $original );
		$ticket_data = array_merge( $ticket_data, $overrides );

		// Remove ID and post_type - ID so create() doesn't try to update, post_type is set via create_args.
		unset( $ticket_data['ID'], $ticket_data['post_type'] );

		// Create a fresh repository instance to avoid state pollution from the by() call.
		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$new_ticket = $repository->set_args( $ticket_data )->create();

		// Return ticket ID (int) on success, false on failure.
		return $new_ticket instanceof \WP_Post ? $new_ticket->ID : false;
	}

	/**
	 * Convert a ticket post object to an array.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $ticket The ticket post object.
	 *
	 * @return array Ticket data as array.
	 */
	protected function ticket_to_array( $ticket ) {
		$data = [
			'ID'          => $ticket->ID,
			'post_type'   => $ticket->post_type,
			'title'       => $ticket->post_title,
			'description' => $ticket->post_excerpt,
			'content'     => $ticket->post_content,
			'menu_order'  => $ticket->menu_order,
			'status'      => $ticket->post_status,
		];

		// Fields that should NOT be copied when duplicating.
		$skip_fields = [ 'sales' ];

		// Add all meta fields using aliases.
		foreach ( $this->update_fields_aliases as $alias => $meta_key ) {
			if ( in_array( $alias, $skip_fields, true ) ) {
				continue;
			}
			$value = get_post_meta( $ticket->ID, $meta_key, true );
			// Only include non-empty values.
			if ( '' !== $value && false !== $value ) {
				$data[ $alias ] = $value;
			}
		}

		return $data;
	}
}
