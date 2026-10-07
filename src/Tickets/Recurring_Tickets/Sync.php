<?php
/**
 * Keeps one row per template and date of a recurring event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use DateTimeZone;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tasks\Sync_Task;
use Tribe__Date_Utils as Dates;
use Tribe__Events__Main as TEC;
use Tribe__Events__Timezones as Timezones;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

use function TEC\Common\StellarWP\Shepherd\shepherd;

/**
 * The only writer of rows. Running it again changes nothing.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Sync {
	/**
	 * How many rows one insert writes.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const CHUNK = 500;

	/**
	 * How many template-date pairs an event can have for Sync to write them in the request.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const INLINE_LIMIT = 1000;

	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The template guard.
	 *
	 * @since TBD
	 *
	 * @var Template_Guard
	 */
	private Template_Guard $guard;

	/**
	 * The same-date re-pointing.
	 *
	 * @since TBD
	 *
	 * @var Reconcile
	 */
	private Reconcile $reconcile;

	/**
	 * Sync constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows           $rows      The rows repository.
	 * @param Template_Guard $guard     The template guard.
	 * @param Reconcile      $reconcile The same-date re-pointing.
	 */
	public function __construct( Rows $rows, Template_Guard $guard, Reconcile $reconcile ) {
		$this->rows      = $rows;
		$this->guard     = $guard;
		$this->reconcile = $reconcile;
	}

	/**
	 * Brings an event's rows in line with its templates and dates.
	 *
	 * Deletes the rows whose template is no longer a published template of the event, or whose date no longer
	 * exists; a row whose date moved to another event is left alone. Refreshes the other rows from their template,
	 * then inserts a row for every template and date without one.
	 *
	 * An event with more template-date pairs than the inline limit is synced by a background task instead.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event's post ID.
	 *
	 * @return void
	 */
	public function sync_event( $post_id ): void {
		$post_id = (int) $post_id;

		if ( TEC::POSTTYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$templates = $this->templates( $post_id );
		$dates     = $this->dates( $post_id );

		/**
		 * Filters how many template-date pairs an event can have for its rows to be written in the request.
		 *
		 * Above it, a background task syncs the event.
		 *
		 * @since TBD
		 *
		 * @param int $limit   The limit. Default 1,000.
		 * @param int $post_id The event's post ID.
		 */
		$limit = (int) apply_filters( 'tec_tickets_recurring_tickets_sync_inline_limit', self::INLINE_LIMIT, $post_id );

		if ( count( $templates ) * count( $dates ) > $limit ) {
			shepherd()->dispatch( new Sync_Task( $post_id ) );

			return;
		}

		$this->apply( $post_id, $templates, $dates );
	}

	/**
	 * Brings an event's rows in line with its templates and dates in this request, whatever their number.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event's post ID.
	 *
	 * @return void
	 */
	public function sync_event_inline( int $post_id ): void {
		if ( TEC::POSTTYPE === get_post_type( $post_id ) ) {
			$this->apply( $post_id, $this->templates( $post_id ), $this->dates( $post_id ) );
		}
	}

	/**
	 * Deletes, refreshes and inserts an event's rows.
	 *
	 * @since TBD
	 *
	 * @param int               $post_id   The event's post ID.
	 * @param int[]             $templates The event's templates.
	 * @param array<int,object> $dates     The event's dates, by occurrence ID.
	 *
	 * @return void
	 */
	private function apply( int $post_id, array $templates, array $dates ): void {
		// A gone date may be the same day under a new ID: its rows follow it before the rest go.
		$rows   = $this->reconcile->repoint( $post_id, $this->rows->get_by_post( $post_id ), $dates );
		$rows   = $this->delete_stale( $rows, $templates, $dates );
		$values = [];

		foreach ( $templates as $template_id ) {
			$values[ $template_id ] = $this->values( $template_id, $post_id );
			$this->rows->refresh_template( $template_id, $values[ $template_id ] );
		}

		$this->insert_missing( $post_id, $values, $dates, $rows );

		/**
		 * Fires after Sync brought an event's rows in line with its templates and dates.
		 *
		 * @since TBD
		 *
		 * @param int   $post_id        The event's post ID.
		 * @param int[] $occurrence_ids The IDs of the event's dates.
		 */
		do_action( 'tec_tickets_recurring_tickets_synced', $post_id, array_map( 'intval', array_keys( $dates ) ) );
	}

	/**
	 * Deletes the rows of an event deleted for good. Its attendees and orders stay; a trashed event keeps its rows.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post about to be deleted.
	 *
	 * @return void
	 */
	public function delete_event( $post_id ): void {
		if ( TEC::POSTTYPE === get_post_type( (int) $post_id ) ) {
			$this->rows->delete_by_post( (int) $post_id );
		}
	}

	/**
	 * Syncs the event of a ticket just saved, when the ticket is a template.
	 *
	 * @since TBD
	 *
	 * @param int           $post_id The ticket's post.
	 * @param Ticket_Object $ticket  The ticket.
	 *
	 * @return void
	 */
	public function sync_saved_ticket( $post_id, $ticket ): void {
		if ( $ticket instanceof Ticket_Object && $this->guard->is_template( (int) $ticket->ID ) ) {
			$this->sync_event( $post_id );
		}
	}

	/**
	 * Syncs the event of a ticket just deleted, when the ticket had rows.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket.
	 * @param int $post_id   The ticket's post.
	 *
	 * @return void
	 */
	public function sync_deleted_ticket( $ticket_id, $post_id ): void {
		if ( $this->rows->get_by_template( (int) $ticket_id ) ) {
			$this->sync_event( $post_id );
		}
	}

	/**
	 * Returns an event's published templates, in menu order.
	 *
	 * Read from the posts, not from the event's ticket list, which the front end and the date pages filter.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event's post ID.
	 *
	 * @return int[] The template IDs.
	 */
	private function templates( int $post_id ): array {
		return array_map(
			'intval',
			get_posts(
				[
					'post_type'      => Commerce_Ticket::POSTTYPE,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => [
						'menu_order' => 'ASC',
						'ID'         => 'ASC',
					],
					'no_found_rows'  => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The two keys ET relates a ticket by.
					'meta_query'     => [
						[
							'key'   => Commerce_Ticket::$event_relation_meta_key,
							'value' => $post_id,
						],
						[
							'key'   => '_type',
							'value' => Template_Guard::TICKET_TYPE,
						],
					],
				]
			)
		);
	}

	/**
	 * Returns an event's dates.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event's post ID.
	 *
	 * @return array<int,object> The dates, by occurrence ID, with their local and UTC start.
	 */
	private function dates( int $post_id ): array {
		$dates = DB::get_results(
			DB::prepare(
				'SELECT occurrence_id, start_date, start_date_utc FROM %i WHERE post_id = %d ORDER BY start_date_utc, occurrence_id',
				Occurrences::table_name(),
				$post_id
			)
		);

		return array_column( (array) $dates, null, 'occurrence_id' );
	}

	/**
	 * Deletes the rows whose template is gone, or whose date no longer exists anywhere.
	 *
	 * @since TBD
	 *
	 * @param Ticket[]          $rows      The event's rows.
	 * @param int[]             $templates The event's templates.
	 * @param array<int,object> $dates     The event's dates, by occurrence ID.
	 *
	 * @return Ticket[] The rows kept.
	 */
	private function delete_stale( array $rows, array $templates, array $dates ): array {
		$elsewhere = array_diff( array_map( static fn( Ticket $row ) => (int) $row->occurrence_id, $rows ), array_keys( $dates ), [ 0 ] );
		$existing  = $elsewhere ? $this->existing_dates( $elsewhere ) : [];
		$kept      = [];
		$stale     = [];

		foreach ( $rows as $row ) {
			$occurrence_id = (int) $row->occurrence_id;
			$date_exists   = isset( $dates[ $occurrence_id ] ) || isset( $existing[ $occurrence_id ] );

			if ( $date_exists && in_array( (int) $row->parent_id, $templates, true ) ) {
				$kept[] = $row;
			} else {
				$stale[] = (int) $row->id;
			}
		}

		$this->rows->delete_ids( $stale );

		return $kept;
	}

	/**
	 * Returns which of some dates still exist, on any event.
	 *
	 * @since TBD
	 *
	 * @param int[] $occurrence_ids The occurrence IDs.
	 *
	 * @return array<int,true> The occurrence IDs that exist.
	 */
	private function existing_dates( array $occurrence_ids ): array {
		$placeholders = implode( ', ', array_fill( 0, count( $occurrence_ids ), '%d' ) );
		$existing     = DB::get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One placeholder per ID.
			DB::prepare( "SELECT occurrence_id FROM %i WHERE occurrence_id IN ({$placeholders})", Occurrences::table_name(), ...array_values( $occurrence_ids ) )
		);

		return array_fill_keys( array_map( 'intval', (array) $existing ), true );
	}

	/**
	 * Inserts a row for every template and date without one.
	 *
	 * @since TBD
	 *
	 * @param int                                      $post_id The event's post ID.
	 * @param array<int,array<string,int|string|null>> $values  Each template's values, by template ID.
	 * @param array<int,object>                        $dates   The event's dates, by occurrence ID.
	 * @param Ticket[]                                 $rows    The event's rows.
	 *
	 * @return void
	 */
	private function insert_missing( int $post_id, array $values, array $dates, array $rows ): void {
		$have = [];
		foreach ( $rows as $row ) {
			$have[ $row->parent_id . ':' . $row->occurrence_id ] = true;
		}

		$new = [];
		foreach ( $values as $template_id => $template_values ) {
			foreach ( $dates as $occurrence_id => $date ) {
				if ( isset( $have[ "{$template_id}:{$occurrence_id}" ] ) ) {
					continue;
				}

				$new[] = array_merge(
					$template_values,
					[
						'type'                 => Template_Guard::TICKET_TYPE,
						'parent_id'            => $template_id,
						'post_id'              => $post_id,
						'occurrence_id'        => (int) $occurrence_id,
						'occurrence_start'     => $date->start_date,
						'occurrence_start_utc' => $date->start_date_utc,
						'stock'                => -1 === $template_values['capacity'] ? null : $template_values['capacity'],
						'sales'                => 0,
					]
				);
			}
		}

		foreach ( array_chunk( $new, self::CHUNK ) as $chunk ) {
			$this->rows->insert_many( $chunk );
		}
	}

	/**
	 * Returns the values a template gives its rows.
	 *
	 * The sale window is the template's, until sale dates relative to each date exist.
	 *
	 * @since TBD
	 *
	 * @param int $template_id The template.
	 * @param int $post_id     The event's post ID.
	 *
	 * @return array<string,int|string|null> The values, keyed by column.
	 */
	private function values( int $template_id, int $post_id ): array {
		$post     = get_post( $template_id );
		$handler  = tribe( 'tickets.handler' );
		$capacity = get_post_meta( $template_id, $handler->key_capacity, true );
		$capacity = is_numeric( $capacity ) && (int) $capacity >= 0 ? (int) $capacity : -1;
		$sku      = (string) get_post_meta( $template_id, Commerce_Ticket::$sku_meta_key, true );
		$iac      = (string) get_post_meta( $template_id, '_tribe_tickets_ar_iac', true );
		$timezone = Timezones::build_timezone_object( Timezones::get_event_timezone_string( $post_id ) );

		[ $start_date, $start_date_utc ] = $this->sale_date( $template_id, Commerce_Ticket::START_DATE_META_KEY, Commerce_Ticket::START_TIME_META_KEY, $timezone );
		[ $end_date, $end_date_utc ]     = $this->sale_date( $template_id, Commerce_Ticket::END_DATE_META_KEY, Commerce_Ticket::END_TIME_META_KEY, $timezone );

		return [
			'name'             => (string) $post->post_title,
			'description'      => (string) $post->post_excerpt,
			'show_description' => 'no' === get_post_meta( $template_id, $handler->key_show_description, true ) ? 0 : 1,
			'sku'              => '' === $sku ? null : $sku,
			'price'            => Price::from_decimal( get_post_meta( $template_id, Commerce_Ticket::$price_meta_key, true ) ),
			'capacity'         => $capacity,
			'stock_mode'       => -1 === $capacity ? 'unlimited' : 'own',
			'start_date'       => $start_date,
			'start_date_utc'   => $start_date_utc,
			'end_date'         => $end_date,
			'end_date_utc'     => $end_date_utc,
			'menu_order'       => (int) $post->menu_order,
			'status'           => 'publish',
			'iac_settings'     => '' === $iac ? null : (string) wp_json_encode( [ 'iac' => $iac ] ),
		];
	}

	/**
	 * Returns one end of a template's sale window, local to the event and in UTC.
	 *
	 * @since TBD
	 *
	 * @param int          $template_id The template.
	 * @param string       $date_key    The meta key of the date.
	 * @param string       $time_key    The meta key of the time.
	 * @param DateTimeZone $timezone    The event's time zone.
	 *
	 * @return array{0: string|null, 1: string|null} The local and the UTC datetime, NULL when the template has no date.
	 */
	private function sale_date( int $template_id, string $date_key, string $time_key, DateTimeZone $timezone ): array {
		$date = (string) get_post_meta( $template_id, $date_key, true );

		if ( '' === $date ) {
			return [ null, null ];
		}

		$time  = (string) get_post_meta( $template_id, $time_key, true );
		$local = Dates::immutable( trim( "{$date} {$time}" ), $timezone );

		return [ $local->format( Dates::DBDATETIMEFORMAT ), $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( Dates::DBDATETIMEFORMAT ) ];
	}
}
