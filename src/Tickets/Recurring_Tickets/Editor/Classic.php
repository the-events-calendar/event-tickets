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
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Seating\Meta as Seating_Meta;
use Tribe__Events__Main as TEC;
use Tribe__Template as Template;
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
	 * Classic constructor.
	 *
	 * @since TBD
	 *
	 * @param Template_Guard $guard The template guard.
	 */
	public function __construct( Template_Guard $guard ) {
		$this->guard = $guard;
	}

	/**
	 * Renders the button that adds a recurring event ticket, hidden until the event recurs.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post being edited.
	 *
	 * @return void
	 */
	public function render_button( $post_id ): void {
		$post_id = (int) $post_id;

		if ( ! $this->offers_recurring_tickets( $post_id ) ) {
			return;
		}

		tribe( 'tickets.admin.views' )->template( 'recurring-tickets/form-toggle', [ 'hidden' => ! tribe_is_recurring_event( $post_id ) ] );
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
	 * Titles the tickets panel's list of recurring event tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $table_data The list's data.
	 *
	 * @return array<string,mixed> The list's data.
	 */
	public function title_list( $table_data ): array {
		$table_data                = (array) $table_data;
		$table_data['table_title'] = _x( 'Recurring event tickets', 'The title of the list of recurring event tickets in the tickets panel.', 'event-tickets' );

		return $table_data;
	}

	/**
	 * Says, where the panel says standard tickets are not supported on a recurring event, what it sells instead, and
	 * where the Series Passes of its Series are sold.
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

		unset( $context['messages']['et-warning'] );
		$context['messages'] = array_merge(
			[
				'recurring-event-tickets' => esc_html(
					sprintf(
						// translators: %s is the lowercase singular label for an event.
						__( 'Tickets you add here are offered on every date of this %1$s. Each date has its own capacity. You can\'t add standard tickets or RSVPs to a recurring %1$s.', 'event-tickets' ),
						$event
					)
				),
			],
			$context['messages']
		);

		$series = isset( $context['post_id'] ) && function_exists( 'tec_series' ) ? tec_series()->where( 'event_post_id', (int) $context['post_id'] )->first() : null;

		// Flexible Tickets says where Series Passes are sold; only an event in a Series has one to name.
		if ( $series instanceof WP_Post && isset( $context['messages']['recurring-warning-message'] ) ) {
			$context['messages']['recurring-warning-message'] = $this->series_passes_message( $series, $event );
		}

		return $context;
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
