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
use TEC\Tickets\RSVP\Controller;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use Tribe__Cache_Listener as Cache_Listener;
use Tribe__Tickets__RSVP as RSVP;
use TEC\Common\StellarWP\Migrations\Utilities\Logger;
use WP_Post;
use Exception;

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
	 * Meta key to store original ticket meta values before overwriting.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const ORIGINAL_TICKET_META_KEY = '_tec_rsvp_original_ticket_meta';

	/**
	 * Meta key to store original attendee post_name before overwriting.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const ORIGINAL_POST_NAME_META_KEY = '_tec_rsvp_original_post_name';

	/**
	 * Meta key to store original attendee post_title before overwriting.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const ORIGINAL_POST_TITLE_META_KEY = '_tec_rsvp_original_post_title';

	/**
	 * Get ticket meta keys to rename during migration.
	 *
	 * @since TBD
	 *
	 * @return array<string, string> old_key => new_key pairs.
	 */
	private function get_ticket_meta_rename_map(): array {
		return [
			'_tribe_rsvp_for_event' => TC_Ticket::$event_relation_meta_key,
		];
	}

	/**
	 * Get the static ticket meta to add during migration.
	 *
	 * @since TBD
	 *
	 * @return array<string, string|int> Meta key => value pairs.
	 */
	private function get_ticket_meta_to_add(): array {
		return [
			TC_Ticket::$type_meta_key                => RSVP_V2_Constants::TC_RSVP_TYPE,
			TC_Ticket::$should_manage_stock_meta_key => 'yes',
			TC_Ticket::$stock_mode_meta_key          => 'own',
			TC_Ticket::$stock_status_meta_key        => 'instock',
			TC_Ticket::$allow_backorders_meta_key    => 'no',
			'_price'                                 => 0,
			'_tribe_ticket_show_description'         => 'yes',
			'format'                                 => 'standard',
			'sticky'                                 => '',
		];
	}

	/**
	 * Get ticket meta keys to delete during rollback.
	 *
	 * @since TBD
	 *
	 * @return array<string> Meta keys to delete.
	 */
	private function get_ticket_meta_to_delete(): array {
		return [
			TC_Ticket::START_TIME_META_KEY,
			TC_Ticket::END_TIME_META_KEY,
			'ticket_start_date',
			'ticket_start_time',
			'ticket_end_date',
			'ticket_end_time',
			TC_Ticket::$type_meta_key,
			TC_Ticket::$should_manage_stock_meta_key,
			TC_Ticket::$sku_meta_key,
			TC_Ticket::$stock_meta_key,
			TC_Ticket::$stock_mode_meta_key,
			TC_Ticket::$stock_status_meta_key,
			TC_Ticket::$allow_backorders_meta_key,
			'format',
			'sticky',
			'show_not_going',
			TC_Ticket::$status_count_meta_key_prefix . ':created',
			TC_Ticket::$status_count_meta_key_prefix . ':unknown',
			TC_Ticket::$status_count_meta_key_prefix . ':pending',
			TC_Ticket::$status_count_meta_key_prefix . ':completed',
		];
	}

	/**
	 * Get attendee meta keys to rename during migration.
	 *
	 * @since TBD
	 *
	 * @return array<string, string> old_key => new_key pairs.
	 */
	private function get_attendee_meta_rename_map(): array {
		return [
			RSVP::ATTENDEE_PRODUCT_KEY  => TC_Attendee::$ticket_relation_meta_key,
			RSVP::ATTENDEE_EVENT_KEY    => TC_Attendee::$event_relation_meta_key,
			'_tribe_rsvp_security_code' => TC_Attendee::$security_code_meta_key,
			RSVP::ATTENDEE_OPTOUT_KEY   => TC_Attendee::$optout_meta_key,
			'_paid_price'               => TC_Attendee::$price_paid_meta_key,
			'_tribe_rsvp_email'         => TC_Attendee::$email_meta_key,
			RSVP::ATTENDEE_TICKET_SENT  => TC_Attendee::$ticket_sent_meta_key,
			RSVP::ATTENDEE_RSVP_KEY     => RSVP_V2_Constants::RSVP_STATUS_META_KEY,
		];
	}

	/**
	 * Get attendee meta keys to delete during rollback.
	 *
	 * @since TBD
	 *
	 * @return array<string> Meta keys to delete.
	 */
	private function get_attendee_meta_to_delete(): array {
		return [
			TC_Attendee::$currency_meta_key,
			TC_Attendee::$order_relation_meta_key,
			self::ORIGINAL_POST_NAME_META_KEY,
			self::ORIGINAL_POST_TITLE_META_KEY,
			'_wp_old_slug',
		];
	}

	/**
	 * Get the migration label.
	 *
	 * @since TBD
	 *
	 * @return string The migration label.
	 */
	public function get_label(): string {
		return __( 'RSVP Upgrade', 'event-tickets' );
	}

	/**
	 * Get the migration description.
	 *
	 * @since TBD
	 *
	 * @return string The migration description.
	 */
	public function get_description(): string {
		return __( 'Migrate your RSVPs to the new data system', 'event-tickets' );
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
		return $this->get_migrated_tickets_count() === 0 && $this->get_skipped_tickets_count() === 0;
	}

	/**
	 * Runs before each batch of the migration.
	 *
	 * Disables RSVP while the migration is running.
	 *
	 * @since TBD
	 *
	 * @param int $batch      The batch number.
	 * @param int $batch_size The batch size.
	 *
	 * @return void
	 */
	public function before_up( int $batch, int $batch_size ): void {
		// Only set on the first batch: the option persists across batches and does not need to be set again.
		if ( 1 !== $batch ) {
			return;
		}

		tribe_update_option( Controller::VERSION_OPTION_KEY, Controller::DISABLED );
	}

	/**
	 * Runs after each batch of the migration.
	 *
	 * Sets the RSVP version to v2 when migration completes.
	 *
	 * @since TBD
	 *
	 * @param int  $batch        The batch number.
	 * @param int  $batch_size   The batch size.
	 * @param bool $is_completed Whether the migration has been completed.
	 *
	 * @return void
	 */
	public function after_up( int $batch, int $batch_size, bool $is_completed ): void {
		// Only set on the last batch: intermediate batches keep the DISABLED value set in before_up.
		if ( ! $is_completed ) {
			return;
		}

		tribe_update_option( Controller::VERSION_OPTION_KEY, Controller::VERSION_2 );
	}

	/**
	 * Runs before each batch of the rollback.
	 *
	 * Disables RSVP while the rollback is running.
	 *
	 * @since TBD
	 *
	 * @param int $batch      The batch number.
	 * @param int $batch_size The batch size.
	 *
	 * @return void
	 */
	public function before_down( int $batch, int $batch_size ): void {
		// Only set on the first batch: the option persists across batches and does not need to be set again.
		if ( 1 !== $batch ) {
			return;
		}

		tribe_update_option( Controller::VERSION_OPTION_KEY, Controller::DISABLED );
	}

	/**
	 * Runs after each batch of the rollback.
	 *
	 * Sets the RSVP version to v1 when rollback completes.
	 *
	 * @since TBD
	 *
	 * @param int  $batch        The batch number.
	 * @param int  $batch_size   The batch size.
	 * @param bool $is_completed Whether the rollback has been completed.
	 *
	 * @return void
	 */
	public function after_down( int $batch, int $batch_size, bool $is_completed ): void {
		// Only set on the last batch: intermediate batches keep the DISABLED value set in before_down.
		if ( ! $is_completed ) {
			return;
		}

		tribe_update_option( Controller::VERSION_OPTION_KEY, Controller::VERSION_1 );
	}

	/**
	 * Run the migration.
	 *
	 * @since TBD
	 *
	 * @param int $batch      The batch number.
	 * @param int $batch_size The batch size.
	 *
	 * @throws Exception If a ticket post type update fails.
	 *
	 * @return void
	 */
	public function up( int $batch, int $batch_size ): void {
		$tickets = $this->get_unmigrated_tickets( $batch_size );

		$logger = tribe( Logger::class );

		foreach ( $tickets as $ticket ) {
			$ticket_id = $ticket->ID;
			$event_id  = (int) get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );

			// Skip tickets with no event relation.
			if ( 0 === $event_id ) {
				$logger->warning( 'RSVP Migration: Ticket has no event relation', [ 'ticket_id' => $ticket_id ] );
				update_post_meta( $ticket_id, self::MIGRATED_TICKET_META_KEY, -1 );
				continue;
			}

			// Update ticket post type and fields.
			$result = wp_update_post(
				[
					'ID'             => $ticket_id,
					'post_type'      => TC_Ticket::POSTTYPE,
					'menu_order'     => -1,
					'comment_status' => 'open',
					'ping_status'    => 'open',
				]
			);

			if ( is_wp_error( $result ) || 0 === $result ) {
				$logger->error(
					'RSVP Migration: Failed to update ticket post type',
					[
						'ticket_id' => $ticket_id,
						'error'     => is_wp_error( $result ) ? $result->get_error_message() : 'Unknown error',
					]
				);
				throw new Exception( is_wp_error( $result ) ? $result->get_error_message() : 'Unknown error' );
			}

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

		// Clean up skipped ticket markers (tickets that were skipped during migration and kept their original post type).
		DB::query(
			DB::prepare(
				'DELETE pm FROM %i pm
				INNER JOIN %i p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND pm.meta_value = %s
				AND p.post_type = %s',
				DB::prefix( 'postmeta' ),
				DB::prefix( 'posts' ),
				self::MIGRATED_TICKET_META_KEY,
				'-1',
				'tribe_rsvp_tickets'
			)
		);
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
	 * Get the count of skipped tickets that still have migration markers to clean up.
	 *
	 * @since TBD
	 *
	 * @return int The count.
	 */
	private function get_skipped_tickets_count(): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i pm
				INNER JOIN %i p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = %s',
				DB::prefix( 'postmeta' ),
				DB::prefix( 'posts' ),
				self::MIGRATED_TICKET_META_KEY,
				'-1',
				'tribe_rsvp_tickets'
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

		// Prime post caches in a single query to avoid N+1.
		_prime_post_caches( $ticket_ids );

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

		// Prime post caches in a single query to avoid N+1.
		_prime_post_caches( $ticket_ids );

		return array_filter( array_map( 'get_post', $ticket_ids ) );
	}

	/**
	 * Migrate ticket meta from V1 to V2 format.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $event_id  The event ID.
	 *
	 * @return void
	 */
	private function migrate_ticket_meta( int $ticket_id, int $event_id ): void {
		// Save original values of meta keys that will be overwritten during migration.
		// This allows the rollback to restore them instead of blindly deleting.
		$overwrite_keys = array_merge(
			array_keys( $this->get_ticket_meta_to_add() ),
			[ TC_Ticket::$stock_meta_key ]
		);

		$original_meta = [];
		foreach ( $overwrite_keys as $key ) {
			if ( metadata_exists( 'post', $ticket_id, $key ) ) {
				$original_meta[ $key ] = get_post_meta( $ticket_id, $key, true );
			}
		}

		if ( ! empty( $original_meta ) ) {
			update_post_meta( $ticket_id, self::ORIGINAL_TICKET_META_KEY, $original_meta );
		}

		// Rename meta keys.
		foreach ( $this->get_ticket_meta_rename_map() as $old_key => $new_key ) {
			$this->rename_meta_key( $ticket_id, $old_key, $new_key );
		}

		// Split datetime fields into date and time.
		$start_datetime = get_post_meta( $ticket_id, TC_Ticket::START_DATE_META_KEY, true );
		$end_datetime   = get_post_meta( $ticket_id, TC_Ticket::END_DATE_META_KEY, true );

		if ( $start_datetime ) {
			$start_date = gmdate( 'Y-m-d', strtotime( $start_datetime ) );
			$start_time = gmdate( 'H:i:s', strtotime( $start_datetime ) );
			update_post_meta( $ticket_id, TC_Ticket::START_DATE_META_KEY, $start_date );
			update_post_meta( $ticket_id, TC_Ticket::START_TIME_META_KEY, $start_time );
			update_post_meta( $ticket_id, 'ticket_start_date', $start_date );
			update_post_meta( $ticket_id, 'ticket_start_time', $start_time );
		}

		if ( $end_datetime ) {
			$end_date = gmdate( 'Y-m-d', strtotime( $end_datetime ) );
			$end_time = gmdate( 'H:i:s', strtotime( $end_datetime ) );
			update_post_meta( $ticket_id, TC_Ticket::END_DATE_META_KEY, $end_date );
			update_post_meta( $ticket_id, TC_Ticket::END_TIME_META_KEY, $end_time );
			update_post_meta( $ticket_id, 'ticket_end_date', $end_date );
			update_post_meta( $ticket_id, 'ticket_end_time', $end_time );
		}

		// Add static meta.
		foreach ( $this->get_ticket_meta_to_add() as $key => $value ) {
			update_post_meta( $ticket_id, $key, $value );
		}

		// Add dynamic meta.
		update_post_meta( $ticket_id, TC_Ticket::$sku_meta_key, sprintf( '%d-%d-RSVP', $ticket_id, $event_id ) );

		// Copy show_not_going to non-prefixed version.
		$show_not_going = get_post_meta( $ticket_id, RSVP_V2_Constants::SHOW_NOT_GOING_META_KEY, true );
		if ( $show_not_going ) {
			update_post_meta( $ticket_id, 'show_not_going', $show_not_going );
		}

		// Add status counts based on current sales.
		$total_sales = (int) get_post_meta( $ticket_id, TC_Ticket::$sales_meta_key, true );
		update_post_meta( $ticket_id, TC_Ticket::$status_count_meta_key_prefix . ':created', 0 );
		update_post_meta( $ticket_id, TC_Ticket::$status_count_meta_key_prefix . ':unknown', 0 );
		update_post_meta( $ticket_id, TC_Ticket::$status_count_meta_key_prefix . ':pending', 0 );
		update_post_meta( $ticket_id, TC_Ticket::$status_count_meta_key_prefix . ':completed', $total_sales );

		$capacity = (int) get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );

		update_post_meta( $ticket_id, TC_Ticket::$stock_meta_key, max( 0, $capacity - $total_sales ) );
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
				RSVP::ATTENDEE_OBJECT,
				RSVP::ATTENDEE_PRODUCT_KEY,
				$ticket_id
			)
		);

		if ( empty( $attendee_ids ) ) {
			return [];
		}

		// Prime post caches in a single query to avoid N+1.
		_prime_post_caches( $attendee_ids );

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
	 * @param int       $event_id   The event ID.
	 *
	 * @return void
	 */
	private function migrate_attendee_group( string $order_hash, array $attendees, int $ticket_id, int $event_id ): void {
		if ( empty( $attendees ) ) {
			return;
		}

		// Get purchaser info from first attendee.
		$first_attendee = $attendees[0];
		$full_name      = get_post_meta( $first_attendee->ID, '_tribe_rsvp_full_name', true );
		$email          = get_post_meta( $first_attendee->ID, '_tribe_rsvp_email', true );
		$user_id        = (int) get_post_meta( $first_attendee->ID, TC_Attendee::$user_relation_meta_key, true );

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
	 * @param int    $event_id   The event ID.
	 * @param int    $quantity   The number of attendees.
	 * @param string $full_name  The purchaser full name.
	 * @param string $email      The purchaser email.
	 * @param int    $user_id    The user ID.
	 *
	 * @return int|false The order ID or false on failure.
	 */
	private function create_order( string $order_hash, int $ticket_id, int $event_id, int $quantity, string $full_name, string $email, int $user_id ) {
		$name_parts = explode( ' ', $full_name, 2 );
		$first_name = $name_parts[0] ?? '';
		$last_name  = $name_parts[1] ?? '';

		$gateway_order_id = md5( $order_hash . $email . $ticket_id );
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
				'event_id'          => $event_id,
				'quantity'          => $quantity,
				'price'             => '0',
				'sub_total'         => '0',
				'regular_price'     => '0',
				'regular_sub_total' => '0',
				'type'              => RSVP_V2_Constants::TC_RSVP_TYPE,
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
		update_post_meta( $order_id, Order::$purchaser_user_id_meta_key, $user_id );
		update_post_meta( $order_id, Order::$purchaser_full_name_meta_key, $full_name );
		update_post_meta( $order_id, Order::$purchaser_first_name_meta_key, $first_name );
		update_post_meta( $order_id, Order::$purchaser_last_name_meta_key, $last_name );
		update_post_meta( $order_id, Order::$purchaser_email_meta_key, $email );
		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, $gateway_order_id );
		update_post_meta( $order_id, Order::$events_in_order_meta_key, $event_id );
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
	 * @param int     $event_id  The event ID.
	 *
	 * @return bool Whether the migration was successful.
	 */
	private function migrate_attendee( WP_Post $attendee, int $order_id, int $ticket_id, int $event_id ): bool {
		$attendee_id = $attendee->ID;

		// Save original post fields for rollback.
		update_post_meta( $attendee_id, self::ORIGINAL_POST_NAME_META_KEY, $attendee->post_name );
		update_post_meta( $attendee_id, self::ORIGINAL_POST_TITLE_META_KEY, $attendee->post_title );

		// Update post type and parent.
		$result = wp_update_post(
			[
				'ID'          => $attendee_id,
				'post_type'   => TC_Attendee::POSTTYPE,
				'post_parent' => $order_id,
				'post_title'  => '',
				'post_name'   => (string) $attendee_id,
			]
		);

		if ( is_wp_error( $result ) || 0 === $result ) {
			$logger = tribe( Logger::class );
			$logger->error(
				'RSVP Migration: Failed to update attendee post type',
				[
					'attendee_id' => $attendee_id,
					'error'       => is_wp_error( $result ) ? $result->get_error_message() : 'Unknown error',
				]
			);
			return false;
		}

		// Migrate meta keys.
		$this->migrate_attendee_meta( $attendee_id, $ticket_id, $event_id, $order_id );

		return true;
	}

	/**
	 * Migrate attendee meta from V1 to V2 format.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee ID.
	 * @param int $ticket_id   The ticket ID.
	 * @param int $event_id    The event ID.
	 * @param int $order_id    The order ID.
	 *
	 * @return void
	 */
	private function migrate_attendee_meta( int $attendee_id, int $ticket_id, int $event_id, int $order_id ): void {
		// Rename meta keys.
		foreach ( $this->get_attendee_meta_rename_map() as $old_key => $new_key ) {
			$this->rename_meta_key( $attendee_id, $old_key, $new_key );
		}

		// Add new meta (dynamic values).
		update_post_meta( $attendee_id, TC_Attendee::$currency_meta_key, Currency::get_currency_code() );
		update_post_meta( $attendee_id, TC_Attendee::$order_relation_meta_key, $order_id );

		// Ensure the ticket and event relations are set correctly.
		update_post_meta( $attendee_id, TC_Attendee::$ticket_relation_meta_key, $ticket_id );
		update_post_meta( $attendee_id, TC_Attendee::$event_relation_meta_key, $event_id );

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
				TC_Attendee::$ticket_relation_meta_key,
				$ticket_id
			)
		);

		if ( empty( $attendee_ids ) ) {
			return [];
		}

		// Prime post caches in a single query to avoid N+1.
		_prime_post_caches( $attendee_ids );

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

		// Restore original post fields saved during migration.
		$original_post_name  = get_post_meta( $attendee_id, self::ORIGINAL_POST_NAME_META_KEY, true );
		$original_post_title = get_post_meta( $attendee_id, self::ORIGINAL_POST_TITLE_META_KEY, true );

		// Update post type, remove parent, restore title and post_name.
		wp_update_post(
			[
				'ID'          => $attendee_id,
				'post_type'   => RSVP::ATTENDEE_OBJECT,
				'post_parent' => 0,
				'post_title'  => $original_post_title ?: '',
				'post_name'   => $original_post_name ?: '',
			]
		);

		// Rollback attendee meta (also deletes ORIGINAL_POST_NAME_META_KEY).
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
		foreach ( $this->get_ticket_meta_rename_map() as $old_key => $new_key ) {
			$this->rename_meta_key( $ticket_id, $new_key, $old_key );
		}

		// Restore datetime fields (merge date + time back into single field).
		$start_date = get_post_meta( $ticket_id, TC_Ticket::START_DATE_META_KEY, true );
		$start_time = get_post_meta( $ticket_id, TC_Ticket::START_TIME_META_KEY, true );
		$end_date   = get_post_meta( $ticket_id, TC_Ticket::END_DATE_META_KEY, true );
		$end_time   = get_post_meta( $ticket_id, TC_Ticket::END_TIME_META_KEY, true );

		if ( $start_date && $start_time ) {
			update_post_meta( $ticket_id, TC_Ticket::START_DATE_META_KEY, $start_date . ' ' . $start_time );
		}

		if ( $end_date && $end_time ) {
			update_post_meta( $ticket_id, TC_Ticket::END_DATE_META_KEY, $end_date . ' ' . $end_time );
		}

		// Remove V2 specific meta.
		foreach ( $this->get_ticket_meta_to_delete() as $key ) {
			delete_post_meta( $ticket_id, $key );
		}

		// Restore original meta values that were overwritten during migration.
		$original_meta = get_post_meta( $ticket_id, self::ORIGINAL_TICKET_META_KEY, true );
		if ( is_array( $original_meta ) ) {
			foreach ( $original_meta as $key => $value ) {
				update_post_meta( $ticket_id, $key, $value );
			}
		}
		delete_post_meta( $ticket_id, self::ORIGINAL_TICKET_META_KEY );
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
		foreach ( $this->get_attendee_meta_rename_map() as $old_key => $new_key ) {
			$this->rename_meta_key( $attendee_id, $new_key, $old_key );
		}

		// Remove V2 specific meta.
		foreach ( $this->get_attendee_meta_to_delete() as $key ) {
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
	 * Rename a meta key using direct SQL for better performance.
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
		DB::query(
			DB::prepare(
				'UPDATE %i SET meta_key = %s WHERE post_id = %d AND meta_key = %s',
				DB::prefix( 'postmeta' ),
				$new_key,
				$post_id,
				$old_key
			)
		);

		// Invalidate the WP post meta cache since the direct SQL update bypasses it.
		wp_cache_delete( $post_id, 'post_meta' );

		// Invalidate tribe_cache entries keyed on save_post since the direct SQL update bypasses the save_post hook.
		tribe_cache()->set_last_occurrence( Cache_Listener::TRIGGER_SAVE_POST );
	}
}
