<?php
/**
 * Recurring event tickets in the classic editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Editor
 */

namespace TEC\Tickets\Recurring_Tickets\Editor;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Event;
use TEC\Tickets\Recurring_Tickets\Admin\Template_Sales;
use TEC\Tickets\Recurring_Tickets\Admin\Tickets_By_Date;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Seating\Meta as Seating_Meta;
use Tribe__Events__Main as TEC;
use Tribe__Template as Template;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Offers the recurring event ticket in the tickets panel of a recurring event, and heads its form.
 *
 * Standard tickets and RSVPs stay unavailable on recurring events: the scripts that hide them are not changed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Editor
 */
final class Classic {
	/**
	 * The template guard.
	 *
	 * @since TBD
	 *
	 * @var Template_Guard
	 */
	private Template_Guard $guard;

	/**
	 * The Tickets by Date section's data.
	 *
	 * @since TBD
	 *
	 * @var Tickets_By_Date
	 */
	private Tickets_By_Date $tickets_by_date;

	/**
	 * The sales of each recurring event ticket over every date.
	 *
	 * @since TBD
	 *
	 * @var Template_Sales
	 */
	private Template_Sales $template_sales;

	/**
	 * The sales of the events whose tickets the panel lists, by event ID: the panel lists one row per ticket.
	 *
	 * @since TBD
	 *
	 * @var array<int,array{tickets: array<int,array{sold: int, available: int}>, shared: array{sold: int, capacity: int}|null}>
	 */
	private array $sales = [];

	/**
	 * Classic constructor.
	 *
	 * @since TBD
	 *
	 * @param Template_Guard  $guard           The template guard.
	 * @param Tickets_By_Date $tickets_by_date The Tickets by Date section's data.
	 * @param Template_Sales  $template_sales  The sales of each recurring event ticket over every date.
	 */
	public function __construct( Template_Guard $guard, Tickets_By_Date $tickets_by_date, Template_Sales $template_sales ) {
		$this->guard           = $guard;
		$this->tickets_by_date = $tickets_by_date;
		$this->template_sales  = $template_sales;
	}

	/**
	 * Renders the head of the recurring event tickets, with the buttons that open the Tickets by Date section and add a
	 * ticket, hidden until the event recurs.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post being edited.
	 *
	 * @return void
	 */
	public function render_header( $post_id ): void {
		$post_id = (int) $post_id;

		if ( ! $this->offers_recurring_tickets( $post_id ) ) {
			return;
		}

		tribe( 'tickets.admin.views' )->template(
			'recurring-tickets/header',
			[
				'hidden'  => ! tribe_is_recurring_event( $post_id ),
				'by_date' => null !== $this->tickets_by_date->for_event( (int) Event::filter_event_id( $post_id, 'recurring-tickets-by-date' ) ),
			]
		);
	}

	/**
	 * Prints, under a recurring event's tickets, the root of its Tickets by Date section with the section's data.
	 *
	 * @since TBD
	 *
	 * @param int|mixed $post_id The post being edited: the event, or one of its dates.
	 *
	 * @return void
	 */
	public function render_tickets_by_date( $post_id ): void {
		$data = $this->tickets_by_date->for_event( (int) Event::filter_event_id( (int) $post_id, 'recurring-tickets-by-date' ) );

		if ( ! $data ) {
			return;
		}

		printf( '<div id="tec-tickets-by-date" class="tec-tickets-by-date-root" data-tickets-by-date="%s"></div>', esc_attr( (string) wp_json_encode( $data ) ) );
	}

	/**
	 * Renders the tickets panel of a date for its event: a date's rows can't be saved from it, the event's recurring
	 * event tickets can, for every date.
	 *
	 * Runs after Flexible Tickets, which keeps a date's ID in the panel for its Series Passes.
	 *
	 * @since TBD
	 *
	 * @param int|null|mixed $event_id    The event ID the panel would render for.
	 * @param string|mixed   $context     The context of the lookup.
	 * @param int|mixed      $original_id The ID the lookup started from.
	 *
	 * @return int|null|mixed The event's ID for a date's panel; `$event_id` otherwise.
	 */
	public function panel_event_id( $event_id, $context = 'default', $original_id = null ) {
		if ( 'tickets-metabox-render' !== $context || ! is_numeric( $original_id ) ) {
			return $event_id;
		}

		$event = (int) Event::filter_event_id( (int) $original_id );

		return $event !== (int) $original_id && $this->offers_recurring_tickets( $event ) ? $event : $event_id;
	}

	/**
	 * Whether the editors offer recurring event tickets on a post: an event without a seating layout.
	 *
	 * Seating keeps its own rule for recurring events, as for Series.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post.
	 *
	 * @return bool Whether recurring event tickets are offered.
	 */
	public function offers_recurring_tickets( int $post_id ): bool {
		return TEC::POSTTYPE === get_post_type( $post_id ) && '' === (string) get_post_meta( $post_id, Seating_Meta::META_KEY_LAYOUT_ID, true );
	}

	/**
	 * Heads a recurring event ticket's form with its type and the event's number of dates.
	 *
	 * @since TBD
	 *
	 * @param string   $file        The template file about to be included.
	 * @param string[] $name        The template name, in parts.
	 * @param Template $admin_views The template rendering the form.
	 *
	 * @return void
	 */
	public function render_type_header( $file, $name, $admin_views ): void {
		$context = $admin_views instanceof Template ? $admin_views->get_values() : [];
		$post_id = (int) ( $context['post_id'] ?? 0 );

		if ( Template_Guard::TICKET_TYPE !== ( $context['ticket_type'] ?? '' ) || ! $post_id ) {
			return;
		}

		$admin_views->template(
			'recurring-tickets/type-header',
			[ 'recurring_dates' => (int) Occurrence::where( 'post_id', $post_id )->count() ]
		);
	}

	/**
	 * Titles the tickets panel's list of recurring event tickets: the head above it says they are the event's.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $table_data The list's data.
	 *
	 * @return array<string,mixed> The list's data.
	 */
	public function title_list( $table_data ): array {
		$table_data                = (array) $table_data;
		$table_data['table_title'] = tribe_get_ticket_label_plural( 'recurring_tickets_list_title' );

		return $table_data;
	}

	/**
	 * Shows, in a recurring event ticket's row, its capacity over every date: the shared capacity's when it shares it.
	 *
	 * @since TBD
	 *
	 * @param int|mixed           $capacity The capacity of one date, -1 when unlimited.
	 * @param Ticket_Object|mixed $ticket   The ticket.
	 * @param int|mixed           $post_id  The post being edited: the event, or one of its dates.
	 *
	 * @return int|mixed The capacity over every date; `$capacity` for another ticket.
	 */
	public function row_capacity( $capacity, $ticket, $post_id ) {
		$sales = $this->row_sales( $ticket, $post_id );

		if ( null === $sales || -1 === (int) $capacity ) {
			return $capacity;
		}

		$shared = in_array( $ticket->global_stock_mode(), [ Global_Stock::GLOBAL_STOCK_MODE, Global_Stock::CAPPED_STOCK_MODE ], true );
		$pool   = $this->sales[ $this->event_id( $post_id ) ]['shared'] ?? null;

		return $shared && $pool ? $pool['capacity'] : $sales['sold'] + $sales['available'];
	}

	/**
	 * Shows, in a recurring event ticket's row, what it has left over every date.
	 *
	 * @since TBD
	 *
	 * @param int|mixed           $available What one date has left, -1 when unlimited.
	 * @param Ticket_Object|mixed $ticket    The ticket.
	 * @param int|mixed           $post_id   The post being edited: the event, or one of its dates.
	 *
	 * @return int|mixed What it has left over every date; `$available` for another ticket.
	 */
	public function row_available( $available, $ticket, $post_id ) {
		$sales = $this->row_sales( $ticket, $post_id );

		return null === $sales ? $available : $sales['available'];
	}

	/**
	 * Shows, under a recurring event's tickets, their total capacity over every date, as their rows do.
	 *
	 * @since TBD
	 *
	 * @param int|null|mixed $total   The total capacity of one date, -1 when unlimited.
	 * @param int|mixed      $post_id The post being edited: the event, or one of its dates.
	 *
	 * @return int|null|mixed The total capacity over every date; `$total` for a post without recurring event tickets.
	 */
	public function total_capacity( $total, $post_id ) {
		$event_id = $this->event_id( $post_id );

		if ( ! $this->offers_recurring_tickets( $event_id ) || ! $this->template_sales->for_event( $event_id ) ) {
			return $total;
		}

		$available = $this->template_sales->available( $event_id );

		return -1 === $available ? -1 : $this->template_sales->sold( $event_id ) + $available;
	}

	/**
	 * Heads the Sold column of the list of recurring event tickets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_sold_header(): void {
		printf( '<th class="ticket_sold">%s</th>', esc_html__( 'Sold', 'event-tickets' ) );
	}

	/**
	 * Prints a recurring event ticket's sales over every date in its row.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object|mixed $ticket  The ticket.
	 * @param int|mixed           $post_id The post being edited: the event, or one of its dates.
	 *
	 * @return void
	 */
	public function render_sold_cell( $ticket, $post_id ): void {
		$sales = $this->row_sales( $ticket, $post_id );

		printf(
			'<td class="ticket_sold"><span class="tribe-mobile-only">%1$s</span> %2$s</td>',
			esc_html__( 'Sold:', 'event-tickets' ),
			esc_html( (string) ( $sales['sold'] ?? 0 ) )
		);
	}

	/**
	 * Returns a recurring event ticket's sales over every date of its event, read once per event.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object|mixed $ticket  The ticket.
	 * @param int|mixed           $post_id The post being edited: the event, or one of its dates.
	 *
	 * @return array{sold: int, available: int}|null The sales, or null for another ticket.
	 */
	private function row_sales( $ticket, $post_id ): ?array {
		if ( ! $ticket instanceof Ticket_Object || ! $this->guard->is_template( (int) $ticket->ID ) ) {
			return null;
		}

		$event_id = $this->event_id( $post_id );

		$this->sales[ $event_id ] ??= [
			'tickets' => $this->template_sales->for_event( $event_id ),
			'shared'  => $this->template_sales->shared_pool( $event_id ),
		];

		return $this->sales[ $event_id ]['tickets'][ (int) $ticket->ID ] ?? null;
	}

	/**
	 * Returns the event of the post being edited.
	 *
	 * @since TBD
	 *
	 * @param int|mixed $post_id The post being edited: the event, or one of its dates.
	 *
	 * @return int The event's post ID.
	 */
	private function event_id( $post_id ): int {
		return (int) Event::filter_event_id( (int) $post_id, 'recurring-tickets-list' );
	}

	/**
	 * Takes out the panel's warning that standard tickets are not supported on a recurring event, which its recurring
	 * event tickets are, and says where the Series Passes of its Series are sold.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The warning's template context.
	 *
	 * @return array<string,mixed> The context.
	 */
	public function filter_warning( $context ): array {
		if ( ! isset( $context['messages'] ) || ! is_array( $context['messages'] ) ) {
			return (array) $context;
		}

		$event = tribe_get_event_label_singular_lowercase();

		// The head of the recurring event tickets says what the event offers in place of standard tickets.
		unset( $context['messages']['et-warning'] );

		$series = isset( $context['post_id'] ) && function_exists( 'tec_series' ) ? tec_series()->where( 'event_post_id', (int) $context['post_id'] )->first() : null;

		// Flexible Tickets says where Series Passes are sold; only an event in a Series has one to name.
		if ( $series instanceof WP_Post && isset( $context['messages']['recurring-warning-message'] ) ) {
			$context['messages']['recurring-warning-message'] = $this->series_passes_message( $series, $event );
		}

		return $context;
	}

	/**
	 * Adds, where the recurrence rules of an event with standard tickets or RSVPs would be, how to sell tickets for
	 * every date instead: an existing ticket never becomes a recurring event ticket.
	 *
	 * @since TBD
	 *
	 * @param string|mixed $message The message saying the event can't be made recurring.
	 *
	 * @return string The message, with the way to sell tickets for every date.
	 */
	public function filter_recurrence_not_supported_message( $message ): string {
		$event = tribe_get_event_label_singular_lowercase();

		return trim(
			sprintf(
				// translators: %1$s is the message saying the event can't be made recurring, %2$s the lowercase singular label for an event.
				__( '%1$s To sell tickets for every date, create a new recurring %2$s and add recurring %2$s tickets there.', 'event-tickets' ),
				is_string( $message ) ? $message : '',
				$event
			)
		);
	}

	/**
	 * Keeps the notice that says recurring tickets are not supported off an event whose tickets are all templates.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function hide_legacy_notice(): void {
		$post_id = absint( tribe_get_request_var( 'post' ) );

		if ( ! $post_id || TEC::POSTTYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$tickets = Tickets::get_all_event_tickets( $post_id );

		if ( ! $tickets ) {
			return;
		}

		foreach ( $tickets as $ticket ) {
			if ( ! $this->guard->is_template( (int) $ticket->ID ) ) {
				return;
			}
		}

		remove_action( 'admin_init', [ tribe( 'tickets.admin.notices' ), 'maybe_display_classic_editor_ecp_recurring_tickets_notice' ] );
	}

	/**
	 * Returns the line that says where the Series Passes of an event's Series are sold.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $series The event's Series.
	 * @param string  $event  The lowercase singular label for an event.
	 *
	 * @return string The line, as HTML.
	 */
	private function series_passes_message( WP_Post $series, string $event ): string {
		$series_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( get_edit_post_link( $series ) ?? '' ),
			esc_html( get_the_title( $series ) )
		);

		$learn_more_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( 'https://evnt.is/-series-passes' ),
			esc_html(
				sprintf(
					// translators: %s is the plural label for Series Passes.
					__( 'Learn more about %s', 'event-tickets' ),
					tec_tickets_get_series_pass_plural_uppercase()
				)
			)
		);

		return sprintf(
			// translators: %1$s is the lowercase singular label for an event, %2$s a link to the Series, %3$s the plural label for Series Passes, %4$s a link to the documentation.
			__( 'This %1$s is part of the %2$s Series. To sell a pass that covers every %1$s in the Series, add %3$s on the Series edit screen. %4$s', 'event-tickets' ),
			esc_html( $event ),
			$series_link,
			esc_html( tec_tickets_get_series_pass_plural_uppercase( 'ticket editor message' ) ),
			$learn_more_link
		);
	}
}
