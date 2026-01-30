<?php
/**
 * RSVP to Tickets Commerce Migration.
 *
 * Migrates legacy RSVP tickets and attendees to the Tickets Commerce infrastructure.
 *
 * @since TBD
 */

namespace TEC\Tickets\Migrations;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Migrations\Abstracts\Migration_Abstract;
use TEC\Common\StellarWP\Migrations\Enums\Operation;
use TEC\Tickets\Commerce\Attendee as TC_Attendee;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\Commerce\Utils\Currency;
use WP_Post;

/**
 * RSVP to Tickets Commerce Migration.
 *
 * @since TBD
 */
class RSVP_To_Tickets_Commerce extends Migration_Abstract {

	/**
	 * Meta key to track migrated tickets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const MIGRATED_TICKET_META_KEY = '_tec_rsvp_migrated_to_tc';

	/**
	 * Meta key to mark orders created by this migration.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const MIGRATION_ORDER_META_KEY = '_tec_rsvp_migration_created';

	/**
	 * Ticket meta keys to rename during migration (old_key => new_key).
	 *
	 * @since TBD
	 *
	 * @var array<string, string>
	 */
	private const TICKET_META_RENAME_MAP = [
		'_tribe_rsvp_for_event' => '_tec_tickets_commerce_event', // TC_Ticket::$event_relation_meta_key
	];

	/**
	 * Static ticket meta to add during migration (key => value).
	 *
	 * @since TBD
	 *
	 * @var array<string, string|int>
	 */
	private const TICKET_META_ADD = [
		'_type'           => 'tc-rsvp', // Constants::TC_RSVP_TYPE
		'_manage_stock'   => 'yes',
		'_global_stock_mode' => 'own',
		'_stock_status'   => 'instock',
		'_backorders'     => 'no',
		'format'          => 'standard',
		'sticky'          => '',
	];

	/**
	 * Ticket meta keys to delete during rollback.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	private const TICKET_META_DELETE = [
		'_ticket_start_time',
		'_ticket_end_time',
		'ticket_start_date',
		'ticket_start_time',
		'ticket_end_date',
		'ticket_end_time',
		'_type',
		'_manage_stock',
		'_sku',
		'_global_stock_mode',
		'_stock_status',
		'_backorders',
		'format',
		'sticky',
		'show_not_going',
		'_tec_tc_ticket_status_count:created',
		'_tec_tc_ticket_status_count:unknown',
		'_tec_tc_ticket_status_count:pending',
		'_tec_tc_ticket_status_count:completed',
	];

	/**
	 * Attendee meta keys to rename during migration (old_key => new_key).
	 *
	 * @since TBD
	 *
	 * @var array<string, string>
	 */
	private const ATTENDEE_META_RENAME_MAP = [
		'_tribe_rsvp_product'              => '_tec_tickets_commerce_ticket', // TC_Attendee::$ticket_relation_meta_key
		'_tribe_rsvp_event'                => '_tec_tickets_commerce_event', // TC_Attendee::$event_relation_meta_key
		'_tribe_rsvp_security_code'        => '_tec_tickets_commerce_security_code', // TC_Attendee::$security_code_meta_key
		'_tribe_rsvp_attendee_optout'      => '_tec_tickets_commerce_optout', // TC_Attendee::$optout_meta_key
		'_paid_price'                      => '_tec_tickets_commerce_price_paid', // TC_Attendee::$price_paid_meta_key
		'_tribe_rsvp_email'                => '_tec_tickets_commerce_email', // TC_Attendee::$email_meta_key
		'_tribe_rsvp_attendee_ticket_sent' => '_tec_tickets_commerce_attendee_ticket_sent', // TC_Attendee::$ticket_sent_meta_key
		'_tribe_rsvp_status'               => '_tec_tickets_commerce_rsvp_status', // Constants::RSVP_STATUS_META_KEY
	];

	/**
	 * Attendee meta keys to delete during rollback.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	private const ATTENDEE_META_DELETE = [
		'_tec_tickets_commerce_currency',
		'_tec_tickets_commerce_order',
	];

	/**
	 * Get the migration label.
	 *
	 * @since TBD
	 *
	 * @return string The migration label.
	 */
	public function get_label(): string {
		return __( 'RSVP to Tickets Commerce', 'event-tickets' );
	}

	/**
	 * Get the migration description.
	 *
	 * @since TBD
	 *
	 * @return string The migration description.
	 */
	public function get_description(): string {
		return __( 'Migrate your RSVPs to Tickets Commerce.', 'event-tickets' );
	}

	/**
	 * Get the total number of items to process.
	 *
	 * @since TBD
	 *
	 * @param Operation|null $operation The operation to get the total items for.
	 *
	 * @return int The total number of items to process.
	 */
	public function get_total_items( ?Operation $operation = null ): int {
		if ( null === $operation ) {
			$operation = Operation::UP();
		}

		if ( $operation->equals( Operation::DOWN() ) ) {
			return $this->get_migrated_tickets_count();
		}

		return $this->get_unmigrated_tickets_count();
	}

	/**
	 * Get the default batch size.
	 *
	 * @since TBD
	 *
	 * @return int The default batch size.
	 */
	public function get_default_batch_size(): int {
		return 50;
	}

	/**
	 * Whether the migration is applicable.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the migration is applicable.
	 */
	public function is_applicable(): bool {
		return $this->get_total_rsvp_v1_tickets_count() > 0 || $this->get_migrated_tickets_count() > 0;
	}

	/**
	 * Whether the migration has been completed.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the migration has been completed.
	 */
	public function is_up_done(): bool {
		return $this->get_unmigrated_tickets_count() === 0;
	}

	/**
	 * Whether the migration has been rolled back.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the migration has been rolled back.
	 */
	public function is_down_done(): bool {
		return $this->get_migrated_tickets_count() === 0;
	}

	/**
	 * Run the migration.
	 *
	 * @since TBD
	 *
	 * @param int $batch      The batch number.
	 * @param int $batch_size The batch size.
	 *
	 * @return void
	 */
	public function up( int $batch, int $batch_size ): void {
		$tickets = $this->get_unmigrated_tickets( $batch_size );

		foreach ( $tickets as $ticket ) {
			$ticket_id = $ticket->ID;
			$event_id  = get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );

			// Update ticket post type and fields.
			wp_update_post(
				[
					'ID'             => $ticket_id,
					'post_type'      => TC_Ticket::POSTTYPE,
					'menu_order'     => -1,
					'comment_status' => 'open',
					'ping_status'    => 'open',
				]
			);

			// Migrate ticket meta.
			$this->migrate_ticket_meta( $ticket_id, $event_id );

			// Get attendees for this ticket, group by order hash, and migrate.
			$attendees = $this->get_ticket_attendees( $ticket_id );

			if ( ! empty( $attendees ) ) {
				$grouped_attendees = $this->group_attendees_by_order_hash( $attendees );

				foreach ( $grouped_attendees as $order_hash => $order_attendees ) {
					$this->migrate_attendee_group( $order_hash, $order_attendees, $ticket_id, $event_id );
				}
			}

			// Mark ticket as migrated.
			update_post_meta( $ticket_id, self::MIGRATED_TICKET_META_KEY, time() );
		}
	}

	/**
	 * Roll back the migration.
	 *
	 * @since TBD
	 *
	 * @param int $batch      The batch number.
	 * @param int $batch_size The batch size.
	 *
	 * @return void
	 */
	public function down( int $batch, int $batch_size ): void {
		$tickets = $this->get_migrated_tickets( $batch_size );

		foreach ( $tickets as $ticket ) {
			$ticket_id = $ticket->ID;

			// Rollback attendees first, collecting order IDs.
			$attendees = $this->get_migrated_attendees_for_ticket( $ticket_id );
			$order_ids = [];

			foreach ( $attendees as $attendee ) {
				if ( $attendee->post_parent ) {
					$order_ids[ $attendee->post_parent ] = true;
				}
				$this->rollback_attendee( $attendee );
			}

			// Delete migration-created orders.
			foreach ( array_keys( $order_ids ) as $order_id ) {
				$this->maybe_delete_migration_order( $order_id );
			}

			// Rollback ticket post type and fields.
			wp_update_post(
				[
					'ID'             => $ticket_id,
					'post_type'      => 'tribe_rsvp_tickets',
					'menu_order'     => 0,
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				]
			);

			// Rollback ticket meta.
			$this->rollback_ticket_meta( $ticket_id );

			// Remove migration marker.
			delete_post_meta( $ticket_id, self::MIGRATED_TICKET_META_KEY );
		}
	}

	/**
	 * Get the migration tags.
	 *
	 * @since TBD
	 *
	 * @return array<string> The migration tags.
	 */
	public function get_tags(): array {
		return [ 'event-tickets', 'rsvp', 'tickets-commerce' ];
	}

	/**
	 * Get the total count of V1 RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @return int The count.
	 */
	private function get_total_rsvp_v1_tickets_count(): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE post_type = %s',
				DB::prefix( 'posts' ),
				'tribe_rsvp_tickets'
			)
		);
	}

	/**
	 * Get the count of unmigrated V1 RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @return int The count.
	 */
	private function get_unmigrated_tickets_count(): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i p
				LEFT JOIN %i pm ON p.ID = pm.post_id AND pm.meta_key = %s
				WHERE p.post_type = %s AND pm.meta_id IS NULL',
				DB::prefix( 'posts' ),
				DB::prefix( 'postmeta' ),
				self::MIGRATED_TICKET_META_KEY,
				'tribe_rsvp_tickets'
			)
		);
	}

	/**
	 * Get the count of migrated tickets.
	 *
	 * @since TBD
	 *
	 * @return int The count.
	 */
	private function get_migrated_tickets_count(): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i pm
				INNER JOIN %i p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND p.post_type = %s',
				DB::prefix( 'postmeta' ),
				DB::prefix( 'posts' ),
				self::MIGRATED_TICKET_META_KEY,
				TC_Ticket::POSTTYPE
			)
		);
	}

	/**
	 * Get unmigrated V1 RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int $limit The number of tickets to retrieve.
	 *
	 * @return WP_Post[] The tickets.
	 */
	private function get_unmigrated_tickets( int $limit ): array {
		$ticket_ids = DB::get_col(
			DB::prepare(
				'SELECT p.ID FROM %i p
				LEFT JOIN %i pm ON p.ID = pm.post_id AND pm.meta_key = %s
				WHERE p.post_type = %s AND pm.meta_id IS NULL
				LIMIT %d',
				DB::prefix( 'posts' ),
				DB::prefix( 'postmeta' ),
				self::MIGRATED_TICKET_META_KEY,
				'tribe_rsvp_tickets',
				$limit
			)
		);

		if ( empty( $ticket_ids ) ) {
			return [];
		}

		return array_filter( array_map( 'get_post', $ticket_ids ) );
	}

	/**
	 * Get migrated tickets for rollback.
	 *
	 * @since TBD
	 *
	 * @param int $limit The number of tickets to retrieve.
	 *
	 * @return WP_Post[] The tickets.
	 */
	private function get_migrated_tickets( int $limit ): array {
		$ticket_ids = DB::get_col(
			DB::prepare(
				'SELECT pm.post_id FROM %i pm
				INNER JOIN %i p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND p.post_type = %s
				LIMIT %d',
				DB::prefix( 'postmeta' ),
				DB::prefix( 'posts' ),
				self::MIGRATED_TICKET_META_KEY,
				TC_Ticket::POSTTYPE,
				$limit
			)
		);

		if ( empty( $ticket_ids ) ) {
			return [];
		}

		return array_filter( array_map( 'get_post', $ticket_ids ) );
	}

	/**
	 * Migrate ticket meta from V1 to V2 format.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id The ticket ID.
	 * @param string $event_id  The event ID.
	 *
	 * @return void
	 */
	private function migrate_ticket_meta( int $ticket_id, string $event_id ): void {
		// Rename meta keys.
		foreach ( self::TICKET_META_RENAME_MAP as $old_key => $new_key ) {
			$this->rename_meta_key( $ticket_id, $old_key, $new_key );
		}

		// Split datetime fields into date and time.
		$start_datetime = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$end_datetime   = get_post_meta( $ticket_id, '_ticket_end_date', true );

		if ( $start_datetime ) {
			$start_date = date( 'Y-m-d', strtotime( $start_datetime ) );
			$start_time = date( 'H:i:s', strtotime( $start_datetime ) );
			update_post_meta( $ticket_id, '_ticket_start_date', $start_date );
			update_post_meta( $ticket_id, '_ticket_start_time', $start_time );
			update_post_meta( $ticket_id, 'ticket_start_date', $start_date );
			update_post_meta( $ticket_id, 'ticket_start_time', $start_time );
		}

		if ( $end_datetime ) {
			$end_date = date( 'Y-m-d', strtotime( $end_datetime ) );
			$end_time = date( 'H:i:s', strtotime( $end_datetime ) );
			update_post_meta( $ticket_id, '_ticket_end_date', $end_date );
			update_post_meta( $ticket_id, '_ticket_end_time', $end_time );
			update_post_meta( $ticket_id, 'ticket_end_date', $end_date );
			update_post_meta( $ticket_id, 'ticket_end_time', $end_time );
		}

		// Add static meta.
		foreach ( self::TICKET_META_ADD as $key => $value ) {
			update_post_meta( $ticket_id, $key, $value );
		}

		// Add dynamic meta.
		update_post_meta( $ticket_id, '_sku', sprintf( '%d-%s-RSVP', $ticket_id, $event_id ) );

		// Copy show_not_going to non-prefixed version.
		$show_not_going = get_post_meta( $ticket_id, '_tribe_ticket_show_not_going', true );
		if ( $show_not_going ) {
			update_post_meta( $ticket_id, 'show_not_going', $show_not_going );
		}

		// Add status counts based on current sales.
		$total_sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
		update_post_meta( $ticket_id, '_tec_tc_ticket_status_count:created', 0 );
		update_post_meta( $ticket_id, '_tec_tc_ticket_status_count:unknown', 0 );
		update_post_meta( $ticket_id, '_tec_tc_ticket_status_count:pending', 0 );
		update_post_meta( $ticket_id, '_tec_tc_ticket_status_count:completed', $total_sales );
	}

	/**
	 * Get attendees for a specific ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return WP_Post[] The attendees.
	 */
	private function get_ticket_attendees( int $ticket_id ): array {
		$attendee_ids = DB::get_col(
			DB::prepare(
				'SELECT p.ID FROM %i p
				INNER JOIN %i pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND pm.meta_key = %s
				AND pm.meta_value = %s',
				DB::prefix( 'posts' ),
				DB::prefix( 'postmeta' ),
				'tribe_rsvp_attendees',
				'_tribe_rsvp_product',
				$ticket_id
			)
		);

		if ( empty( $attendee_ids ) ) {
			return [];
		}

		return array_filter( array_map( 'get_post', $attendee_ids ) );
	}

	/**
	 * Group attendees by their order hash.
	 *
	 * @since TBD
	 *
	 * @param WP_Post[] $attendees The attendees.
	 *
	 * @return array<string, WP_Post[]> Grouped attendees.
	 */
	private function group_attendees_by_order_hash( array $attendees ): array {
		$grouped = [];

		foreach ( $attendees as $attendee ) {
			$order_hash = get_post_meta( $attendee->ID, '_tribe_rsvp_order', true );

			if ( empty( $order_hash ) ) {
				$order_hash = md5( uniqid( (string) $attendee->ID, true ) );
			}

			if ( ! isset( $grouped[ $order_hash ] ) ) {
				$grouped[ $order_hash ] = [];
			}

			$grouped[ $order_hash ][] = $attendee;
		}

		return $grouped;
	}

	/**
	 * Migrate a group of attendees (same order).
	 *
	 * @since TBD
	 *
	 * @param string    $order_hash The order hash.
	 * @param WP_Post[] $attendees  The attendees in this order.
	 * @param int       $ticket_id  The ticket ID.
	 * @param string    $event_id   The event ID.
	 *
	 * @return void
	 */
	private function migrate_attendee_group( string $order_hash, array $attendees, int $ticket_id, string $event_id ): void {
		if ( empty( $attendees ) ) {
			return;
		}

		// Get purchaser info from first attendee.
		$first_attendee = $attendees[0];
		$full_name      = get_post_meta( $first_attendee->ID, '_tribe_rsvp_full_name', true );
		$email          = get_post_meta( $first_attendee->ID, '_tribe_rsvp_email', true );
		$user_id        = get_post_meta( $first_attendee->ID, '_tribe_tickets_attendee_user_id', true );

		// Create the order.
		$order_id = $this->create_order(
			$order_hash,
			$ticket_id,
			$event_id,
			count( $attendees ),
			$full_name,
			$email,
			$user_id
		);

		if ( ! $order_id ) {
			return;
		}

		// Migrate each attendee.
		foreach ( $attendees as $attendee ) {
			$this->migrate_attendee( $attendee, $order_id, $ticket_id, $event_id );
		}
	}

	/**
	 * Create a Tickets Commerce order for migrated attendees.
	 *
	 * @since TBD
	 *
	 * @param string $order_hash The order hash.
	 * @param int    $ticket_id  The ticket ID.
	 * @param string $event_id   The event ID.
	 * @param int    $quantity   The number of attendees.
	 * @param string $full_name  The purchaser full name.
	 * @param string $email      The purchaser email.
	 * @param int    $user_id    The user ID.
	 *
	 * @return int|false The order ID or false on failure.
	 */
	private function create_order( string $order_hash, int $ticket_id, string $event_id, int $quantity, string $full_name, string $email, $user_id ) {
		$name_parts = explode( ' ', $full_name, 2 );
		$first_name = $name_parts[0] ?? '';
		$last_name  = $name_parts[1] ?? '';

		$gateway_order_id = md5( $order_hash . $email . time() );
		$currency         = Currency::get_currency_code();
		$title            = sprintf( 'TEC-TC-%s-T-%d', substr( $order_hash, 0, 12 ), $ticket_id );

		// Create order post.
		$order_id = wp_insert_post(
			[
				'post_type'      => Order::POSTTYPE,
				'post_status'    => 'tec-tc-completed',
				'post_title'     => $title,
				'post_name'      => sanitize_title( $title ),
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			]
		);

		if ( is_wp_error( $order_id ) || ! $order_id ) {
			return false;
		}

		// Build order items.
		$items = [
			[
				'ticket_id'         => $ticket_id,
				'event_id'          => (int) $event_id,
				'quantity'          => $quantity,
				'price'             => '0',
				'sub_total'         => '0',
				'regular_price'     => '0',
				'regular_sub_total' => '0',
				'type'              => 'tc-rsvp',
				'extra'             => [],
			],
		];

		// Add order meta.
		update_post_meta( $order_id, Order::$total_value_meta_key, '0' );
		update_post_meta( $order_id, Order::$subtotal_value_meta_key, '0' );
		update_post_meta( $order_id, Order::$items_meta_key, $items );
		update_post_meta( $order_id, Order::$gateway_meta_key, 'free' );
		update_post_meta( $order_id, Order::$hash_meta_key, $order_hash );
		update_post_meta( $order_id, Order::$currency_meta_key, $currency );
		update_post_meta( $order_id, Order::$purchaser_user_id_meta_key, $user_id ?: 0 );
		update_post_meta( $order_id, Order::$purchaser_full_name_meta_key, $full_name );
		update_post_meta( $order_id, Order::$purchaser_first_name_meta_key, $first_name );
		update_post_meta( $order_id, Order::$purchaser_last_name_meta_key, $last_name );
		update_post_meta( $order_id, Order::$purchaser_email_meta_key, $email );
		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, $gateway_order_id );
		update_post_meta( $order_id, Order::$events_in_order_meta_key, (int) $event_id );
		update_post_meta( $order_id, Order::$tickets_in_order_meta_key, $ticket_id );

		// Add status log.
		$timestamp = current_time( 'mysql', true );
		update_post_meta( $order_id, Order::$status_log_meta_key_prefix . '_completed', $timestamp );

		// Mark as migration-created order.
		update_post_meta( $order_id, self::MIGRATION_ORDER_META_KEY, time() );

		return $order_id;
	}

	/**
	 * Migrate a single attendee.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $attendee  The attendee post.
	 * @param int     $order_id  The order ID.
	 * @param int     $ticket_id The ticket ID.
	 * @param string  $event_id  The event ID.
	 *
	 * @return void
	 */
	private function migrate_attendee( WP_Post $attendee, int $order_id, int $ticket_id, string $event_id ): void {
		$attendee_id = $attendee->ID;

		// Update post type and parent.
		wp_update_post(
			[
				'ID'          => $attendee_id,
				'post_type'   => TC_Attendee::POSTTYPE,
				'post_parent' => $order_id,
				'post_title'  => '',
				'post_name'   => (string) $attendee_id,
			]
		);

		// Migrate meta keys.
		$this->migrate_attendee_meta( $attendee_id, $ticket_id, $event_id, $order_id );
	}

	/**
	 * Migrate attendee meta from V1 to V2 format.
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id The attendee ID.
	 * @param int    $ticket_id   The ticket ID.
	 * @param string $event_id    The event ID.
	 * @param int    $order_id    The order ID.
	 *
	 * @return void
	 */
	private function migrate_attendee_meta( int $attendee_id, int $ticket_id, string $event_id, int $order_id ): void {
		// Rename meta keys.
		foreach ( self::ATTENDEE_META_RENAME_MAP as $old_key => $new_key ) {
			$this->rename_meta_key( $attendee_id, $old_key, $new_key );
		}

		// Add new meta (dynamic values).
		update_post_meta( $attendee_id, '_tec_tickets_commerce_currency', Currency::get_currency_code() );
		update_post_meta( $attendee_id, '_tec_tickets_commerce_order', $order_id );

		// Ensure the ticket and event relations are set correctly.
		update_post_meta( $attendee_id, '_tec_tickets_commerce_ticket', $ticket_id );
		update_post_meta( $attendee_id, '_tec_tickets_commerce_event', (int) $event_id );

		// The _tribe_tickets_meta (AR fields) is preserved as-is.
	}

	/**
	 * Get migrated attendees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return WP_Post[] The attendees.
	 */
	private function get_migrated_attendees_for_ticket( int $ticket_id ): array {
		$attendee_ids = DB::get_col(
			DB::prepare(
				'SELECT p.ID FROM %i p
				INNER JOIN %i pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND pm.meta_key = %s
				AND pm.meta_value = %s',
				DB::prefix( 'posts' ),
				DB::prefix( 'postmeta' ),
				TC_Attendee::POSTTYPE,
				'_tec_tickets_commerce_ticket',
				$ticket_id
			)
		);

		if ( empty( $attendee_ids ) ) {
			return [];
		}

		return array_filter( array_map( 'get_post', $attendee_ids ) );
	}

	/**
	 * Rollback a single attendee.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $attendee The attendee post.
	 *
	 * @return void
	 */
	private function rollback_attendee( WP_Post $attendee ): void {
		$attendee_id = $attendee->ID;
		$order_id    = $attendee->post_parent;

		// Restore original post title format.
		$order_hash = '';
		$full_name  = get_post_meta( $attendee_id, '_tec_tickets_commerce_email', true );

		if ( $order_id ) {
			$order_hash = get_post_meta( $order_id, Order::$hash_meta_key, true );
			$full_name  = get_post_meta( $order_id, Order::$purchaser_full_name_meta_key, true );
		}

		// Update post type and remove parent.
		wp_update_post(
			[
				'ID'          => $attendee_id,
				'post_type'   => 'tribe_rsvp_attendees',
				'post_parent' => 0,
				'post_title'  => $order_hash . ' | ' . $full_name,
			]
		);

		// Rollback attendee meta.
		$this->rollback_attendee_meta( $attendee_id );
	}

	/**
	 * Rollback ticket meta to V1 format.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	private function rollback_ticket_meta( int $ticket_id ): void {
		// Restore renamed meta keys.
		foreach ( self::TICKET_META_RENAME_MAP as $old_key => $new_key ) {
			$this->rename_meta_key( $ticket_id, $new_key, $old_key );
		}

		// Restore datetime fields (merge date + time back into single field).
		$start_date = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$start_time = get_post_meta( $ticket_id, '_ticket_start_time', true );
		$end_date   = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$end_time   = get_post_meta( $ticket_id, '_ticket_end_time', true );

		if ( $start_date && $start_time ) {
			update_post_meta( $ticket_id, '_ticket_start_date', $start_date . ' ' . $start_time );
		}

		if ( $end_date && $end_time ) {
			update_post_meta( $ticket_id, '_ticket_end_date', $end_date . ' ' . $end_time );
		}

		// Remove V2 specific meta.
		foreach ( self::TICKET_META_DELETE as $key ) {
			delete_post_meta( $ticket_id, $key );
		}
	}

	/**
	 * Rollback attendee meta to V1 format.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee ID.
	 *
	 * @return void
	 */
	private function rollback_attendee_meta( int $attendee_id ): void {
		// Restore renamed meta keys.
		foreach ( self::ATTENDEE_META_RENAME_MAP as $old_key => $new_key ) {
			$this->rename_meta_key( $attendee_id, $new_key, $old_key );
		}

		// Remove V2 specific meta.
		foreach ( self::ATTENDEE_META_DELETE as $key ) {
			delete_post_meta( $attendee_id, $key );
		}
	}

	/**
	 * Delete a migration-created order if it has no remaining attendees.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	private function maybe_delete_migration_order( int $order_id ): void {
		// Check if this was a migration-created order.
		$is_migration_order = get_post_meta( $order_id, self::MIGRATION_ORDER_META_KEY, true );

		if ( ! $is_migration_order ) {
			return;
		}

		// Check if there are still attendees pointing to this order.
		$remaining_attendees = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE post_parent = %d AND post_type = %s',
				DB::prefix( 'posts' ),
				$order_id,
				TC_Attendee::POSTTYPE
			)
		);

		if ( (int) $remaining_attendees === 0 ) {
			wp_delete_post( $order_id, true );
		}
	}

	/**
	 * Rename a meta key.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID.
	 * @param string $old_key The old meta key.
	 * @param string $new_key The new meta key.
	 *
	 * @return void
	 */
	private function rename_meta_key( int $post_id, string $old_key, string $new_key ): void {
		$value = get_post_meta( $post_id, $old_key, true );

		if ( '' !== $value && false !== $value ) {
			update_post_meta( $post_id, $new_key, $value );
			delete_post_meta( $post_id, $old_key );
		}
	}
}
