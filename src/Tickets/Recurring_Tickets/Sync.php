<?php
/**
 * Keeps one row per template and date of a recurring event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use DateTimeImmutable;
use DateTimeZone;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Relative_Sale_Dates\Rule;
use TEC\Tickets\Relative_Sale_Dates\Rule_Store;
use TEC\Tickets\Relative_Sale_Dates\Sale_Window;
use TEC\Tickets\Relative_Sale_Dates\Window_Kind;
use Tribe__Date_Utils as Dates;
use Tribe__Events__Main as TEC;
use Tribe__Events__Timezones as Timezones;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

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
	 * The columns of a row's sales window.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const SALE_DATE_COLUMNS = [ 'start_date', 'start_date_utc', 'end_date', 'end_date_utc' ];

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
	 * The store of the tickets' relative sale date rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The resolver of relative sales windows.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * Whether a ticket save is under way: it syncs its event once done.
	 *
	 * Mid-save, Tickets Commerce has written the ticket's type back to `default`: a sync then would take the template
	 * for a deleted one, and delete its rows and their sales.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $saving_ticket = false;

	/**
	 * Sync constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows           $rows        The rows repository.
	 * @param Template_Guard $guard       The template guard.
	 * @param Rule_Store     $rule_store  The store of the tickets' relative sale date rules.
	 * @param Sale_Window    $sale_window The resolver of relative sales windows.
	 */
	public function __construct( Rows $rows, Template_Guard $guard, Rule_Store $rule_store, Sale_Window $sale_window ) {
		$this->rows        = $rows;
		$this->guard       = $guard;
		$this->rule_store  = $rule_store;
		$this->sale_window = $sale_window;
	}

	/**
	 * Brings an event's rows in line with its templates and dates.
	 *
	 * Deletes the rows whose template is no longer a published template of the event, or whose date no longer
	 * exists; a row whose date moved to another event is left alone. Refreshes the other rows from their template,
	 * then inserts a row for every template and date without one.
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

		// Most events have no recurring event tickets: leave their dates alone.
		if ( ! $templates && ! $this->rows->get_by_post( $post_id ) ) {
			return;
		}

		$dates  = $this->dates( $post_id );
		$rows   = $this->rows->get_by_post( $post_id );
		$rows   = $this->delete_stale( $rows, array_merge( $templates, $this->templates_being_saved( $post_id, $rows, $templates ) ), $dates );
		$values = [];

		$rules    = [];
		$timezone = Timezones::build_timezone_object( Timezones::get_event_timezone_string( $post_id ) );

		foreach ( $templates as $template_id ) {
			$values[ $template_id ] = $this->values( $template_id, $post_id );
			$rules[ $template_id ]  = Rule::from_stored( $this->rule_store->get( $template_id ), Window_Kind::sales() );

			// A relative sales window is each date's own: the template's is its first date's.
			$refreshed = $rules[ $template_id ]
				? array_diff_key( $values[ $template_id ], array_flip( self::SALE_DATE_COLUMNS ) )
				: $values[ $template_id ];
			$this->rows->refresh_template( $template_id, $refreshed );
		}

		$this->insert_missing( $post_id, $values, $dates, $rows, $rules, $timezone );
		$this->refresh_sale_windows( $rows, $values, $dates, $rules, $timezone );
		$this->sync_pools( $post_id, $values, $dates );
	}

	/**
	 * Syncs an event's dates once its shared capacity changed outside a ticket save: the ticket settings, or the block
	 * editor's post save.
	 *
	 * @since TBD
	 *
	 * @param int|mixed    $meta_id  Unused.
	 * @param int|mixed    $post_id  The post whose meta changed.
	 * @param string|mixed $meta_key The meta key.
	 *
	 * @return void
	 */
	public function sync_capacity( $meta_id, $post_id, $meta_key ): void {
		if ( ! $this->saving_ticket && tribe( 'tickets.handler' )->key_capacity === $meta_key ) {
			$this->sync_event( $post_id );
		}
	}

	/**
	 * Notes that a ticket save starts: the event's capacity it writes is synced once the save is done.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function start_ticket_save(): void {
		$this->saving_ticket = true;
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
		$this->saving_ticket = false;

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
	 * Returns the templates of an event's rows that another request is saving.
	 *
	 * A save writes the template's type back to `default` until it ends, and the block editor saves every ticket
	 * alongside the event. A template keeps its type through every save, so a published ticket of the event with
	 * rows is removed only once it has another type. The type is read from the request's cache, which can be either
	 * side of the other request's save.
	 *
	 * @since TBD
	 *
	 * @param int      $post_id   The event's post ID.
	 * @param Ticket[] $rows      The event's rows.
	 * @param int[]    $templates The event's templates.
	 *
	 * @return int[] The IDs of the templates being saved.
	 */
	private function templates_being_saved( int $post_id, array $rows, array $templates ): array {
		$parents = array_diff( array_unique( array_map( static fn( Ticket $row ) => (int) $row->parent_id, $rows ) ), $templates );

		return array_values(
			array_filter(
				$parents,
				static fn( int $ticket_id ) => Commerce_Ticket::POSTTYPE === get_post_type( $ticket_id )
					&& 'publish' === get_post_status( $ticket_id )
					&& in_array( get_post_meta( $ticket_id, '_type', true ), [ '', 'default', Template_Guard::TICKET_TYPE ], true )
					&& $post_id === (int) get_post_meta( $ticket_id, Commerce_Ticket::$event_relation_meta_key, true )
			)
		);
	}

	/**
	 * Gives every date a pool of the event's shared capacity while a template shares it, and none otherwise.
	 *
	 * A pool counts its date's shared sales, so a ticket turned shared on a date with sales takes them along.
	 *
	 * @since TBD
	 *
	 * @param int                                      $post_id The event's post ID.
	 * @param array<int,array<string,int|string|null>> $values  The templates' values, by template ID.
	 * @param array<int,object>                        $dates   The event's dates, by occurrence ID.
	 *
	 * @return void
	 */
	private function sync_pools( int $post_id, array $values, array $dates ): void {
		$shared   = array_intersect( [ Global_Stock::GLOBAL_STOCK_MODE, Global_Stock::CAPPED_STOCK_MODE ], array_column( $values, 'stock_mode' ) );
		$capacity = get_post_meta( $post_id, tribe( 'tickets.handler' )->key_capacity, true );

		// Unlimited shared capacity has nothing to pool.
		if ( ! $shared || ! is_numeric( $capacity ) || (int) $capacity < 0 || ! $dates ) {
			$this->rows->delete_pools( $post_id );

			return;
		}

		$this->rows->upsert_pools( $post_id, (int) $capacity, array_keys( $dates ) );
		$this->rows->delete_pools( $post_id, array_keys( $dates ) );
		$this->rows->refresh_pools( $post_id );
	}

	/**
	 * Returns an event's dates.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event's post ID.
	 *
	 * @return array<int,object> The dates, by occurrence ID, with their local and UTC start and their local end.
	 */
	private function dates( int $post_id ): array {
		$dates = DB::get_results(
			DB::prepare(
				'SELECT occurrence_id, start_date, start_date_utc, end_date FROM %i WHERE post_id = %d ORDER BY start_date_utc, occurrence_id',
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
	 * @param int                                      $post_id  The event's post ID.
	 * @param array<int,array<string,int|string|null>> $values   Each template's values, by template ID.
	 * @param array<int,object>                        $dates    The event's dates, by occurrence ID.
	 * @param Ticket[]                                 $rows     The event's rows.
	 * @param array<int,Rule|null>                     $rules    Each template's relative sales window, by template ID.
	 * @param DateTimeZone                             $timezone The event's time zone.
	 *
	 * @return void
	 */
	private function insert_missing( int $post_id, array $values, array $dates, array $rows, array $rules, DateTimeZone $timezone ): void {
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
					$this->date_sale_window( $template_values, $rules[ $template_id ] ?? null, $date, $timezone ),
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
			try {
				$this->rows->insert_many( $chunk );
			} catch ( DatabaseQueryException $e ) {
				// Another request synced the event meanwhile: insert what it did not.
				$this->insert_still_missing( $post_id, $chunk );
			}
		}
	}

	/**
	 * Gives the existing rows of the templates with a relative sales window their own date's window.
	 *
	 * @since TBD
	 *
	 * @param Ticket[]                                 $rows     The event's rows.
	 * @param array<int,array<string,int|string|null>> $values   Each template's values, by template ID.
	 * @param array<int,object>                        $dates    The event's dates, by occurrence ID.
	 * @param array<int,Rule|null>                     $rules    Each template's relative sales window, by template ID.
	 * @param DateTimeZone                             $timezone The event's time zone.
	 *
	 * @return void
	 */
	private function refresh_sale_windows( array $rows, array $values, array $dates, array $rules, DateTimeZone $timezone ): void {
		foreach ( $rows as $row ) {
			$template_id = (int) $row->parent_id;
			$date        = $dates[ (int) $row->occurrence_id ] ?? null;

			// A row whose date moved to another event keeps its window.
			if ( ! $date || empty( $rules[ $template_id ] ) || ! isset( $values[ $template_id ] ) ) {
				continue;
			}

			$this->rows->refresh_row( $row, $this->date_sale_window( $values[ $template_id ], $rules[ $template_id ], $date, $timezone ) );
		}
	}

	/**
	 * Returns a template's sales window on one date.
	 *
	 * A boundary the rule gives no date, a Now start or a specific date, is the template's own.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string|null> $values   The template's values.
	 * @param Rule|null                     $rule     The template's relative sales window, if it has one.
	 * @param object                        $date     The date, with its local start and end.
	 * @param DateTimeZone                  $timezone The event's time zone.
	 *
	 * @return array<string,string|null> The window's local and UTC start and end, keyed by column.
	 */
	private function date_sale_window( array $values, ?Rule $rule, object $date, DateTimeZone $timezone ): array {
		$window = array_intersect_key( $values, array_flip( self::SALE_DATE_COLUMNS ) );

		if ( ! $rule ) {
			return $window;
		}

		$resolved = $this->sale_window->resolve_for_dates(
			$rule,
			new DateTimeImmutable( $date->start_date, $timezone ),
			new DateTimeImmutable( $date->end_date, $timezone )
		);
		$utc      = new DateTimeZone( 'UTC' );
		$edges    = [
			'start' => $resolved->get_start(),
			'end'   => $resolved->get_end(),
		];

		foreach ( $edges as $edge => $resolved_date ) {
			if ( $resolved_date ) {
				$window[ "{$edge}_date" ]     = $resolved_date->format( Dates::DBDATETIMEFORMAT );
				$window[ "{$edge}_date_utc" ] = $resolved_date->setTimezone( $utc )->format( Dates::DBDATETIMEFORMAT );
			}
		}

		return $window;
	}

	/**
	 * Inserts the rows of a failed insert that are still missing, once another request wrote some of them.
	 *
	 * @since TBD
	 *
	 * @param int                                      $post_id The event's post ID.
	 * @param array<int,array<string,int|string|null>> $chunk   The rows of the failed insert.
	 *
	 * @return void
	 */
	private function insert_still_missing( int $post_id, array $chunk ): void {
		$have = [];
		foreach ( $this->rows->get_by_post( $post_id ) as $row ) {
			$have[ $row->parent_id . ':' . $row->occurrence_id ] = true;
		}

		$missing = array_values( array_filter( $chunk, static fn( array $row ) => ! isset( $have[ $row['parent_id'] . ':' . $row['occurrence_id'] ] ) ) );

		if ( ! $missing ) {
			return;
		}

		try {
			$this->rows->insert_many( $missing );
		} catch ( DatabaseQueryException $e ) {
			// The next sync of the event writes them.
			do_action(
				'tribe_log',
				'warning',
				'Recurring event ticket rows could not be inserted.',
				[
					'post_id' => $post_id,
					'error'   => $e->getMessage(),
				]
			);
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
			'stock_mode'       => $this->stock_mode( $template_id, $capacity ),
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
	 * Returns the stock mode a template's rows sell with.
	 *
	 * @since TBD
	 *
	 * @param int $template_id The template.
	 * @param int $capacity    Its capacity, -1 when unlimited.
	 *
	 * @return string The stock mode.
	 */
	private function stock_mode( int $template_id, int $capacity ): string {
		if ( -1 === $capacity ) {
			return 'unlimited';
		}

		$mode = get_post_meta( $template_id, Global_Stock::TICKET_STOCK_MODE, true );

		return in_array( $mode, [ Global_Stock::GLOBAL_STOCK_MODE, Global_Stock::CAPPED_STOCK_MODE ], true ) ? $mode : Global_Stock::OWN_STOCK_MODE;
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
