<?php
/**
 * Offers the sales window options in the classic ticket editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Module;
use Tribe__Template as Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Replaces the sale dates fields of the classic ticket form with the sales window options.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Editor {
	/**
	 * The relative start the form offers when the ticket has none: 2 weeks before the event starts.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int, anchor: string}
	 */
	private const DEFAULT_RELATIVE_START = [
		'mode'   => Rule::MODE_RELATIVE,
		'value'  => 2,
		'unit'   => WEEK_IN_SECONDS,
		'anchor' => Rule::ANCHOR_START,
	];

	/**
	 * The relative end the form offers when the ticket has none: 1 hour before the event starts.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int, anchor: string}
	 */
	private const DEFAULT_RELATIVE_END = [
		'mode'   => Rule::MODE_RELATIVE,
		'value'  => 1,
		'unit'   => HOUR_IN_SECONDS,
		'anchor' => Rule::ANCHOR_START,
	];

	/**
	 * Renders the sales window options in place of the sale dates fields of a Tickets Commerce ticket on an event.
	 *
	 * Only the fields template's own output is replaced, so what other code renders before and after it stays.
	 *
	 * @since TBD
	 *
	 * @param mixed    $html     The HTML of the sale dates fields, as an earlier callback may have filtered it.
	 * @param string   $file     The path of the sale dates fields template.
	 * @param string[] $name     The template name.
	 * @param Template $template The admin views template, holding the ticket panel data.
	 *
	 * @return mixed The sales window options, or the HTML passed in when they do not apply.
	 */
	public function render_sales_window_fields( $html, string $file, array $name, Template $template ) {
		$context = $template->get_values();

		if ( ! $this->applies_to( $context ) ) {
			return $html;
		}

		$stored = $context[ Ticket_Save::DATA_KEY ] ?? null;
		$rule   = is_array( $stored ) ? Rule::from_stored( $stored ) : null;
		$ticket = $context['ticket'] ?? null;

		$context['sales_window'] = [
			'start' => $this->get_boundary_fields( $rule ? $rule->get_start() : null, $this->has_own_date( $ticket, 'start_date' ), self::DEFAULT_RELATIVE_START ),
			'end'   => $this->get_boundary_fields( $rule ? $rule->get_end() : null, $this->has_own_date( $ticket, 'end_date' ), self::DEFAULT_RELATIVE_END ),
		];
		$context['rule_json']    = $rule ? $rule->to_json() : '';

		return $template->template( 'relative-sale-dates/sales-window', $context, false );
	}

	/**
	 * Returns whether the sales window options apply to the ticket the panel is for.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The ticket panel data.
	 *
	 * @return bool Whether the panel is the wp-admin one for a Tickets Commerce ticket, not an RSVP or a Series Pass, on an
	 *              event.
	 */
	private function applies_to( array $context ): bool {
		// An event defaults to the Tickets Commerce provider even when Tickets Commerce is not active.
		return 'tribe_events' === get_post_type( $context['post_id'] ?? 0 )
			&& Module::class === ( $context['provider_class'] ?? '' )
			&& isset( $context['modules'][ Module::class ] )
			&& ! in_array( $context['ticket_type'] ?? 'default', array_merge( [ 'rsvp' ], Ticket_Save::EXCLUDED_TICKET_TYPES ), true )
			// A front-end form, such as Community Events', does not load the script that writes the rule.
			&& tribe_is_truthy( tec_get_request_var( 'is_admin', is_admin() ) );
	}

	/**
	 * Returns whether a saved ticket has a date of its own for one boundary of the sales window.
	 *
	 * A ticket created on a page and moved to an event, or created by code, can have no end date. Saving it without one
	 * gave it the event start, which is what the default end resolves to.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object|null $ticket   The ticket the panel is for, or `null` for a new ticket.
	 * @param string             $property The ticket property that holds the boundary date, `start_date` or `end_date`.
	 *
	 * @return bool Whether the ticket is saved and has a date for the boundary.
	 */
	private function has_own_date( $ticket, string $property ): bool {
		return $ticket instanceof Ticket_Object && ! empty( $ticket->{$property} );
	}

	/**
	 * Gets the values the form shows for one boundary of the sales window.
	 *
	 * A ticket saved without a rule has dates of its own, so it opens on them rather than on the defaults of a new ticket.
	 *
	 * @since TBD
	 *
	 * @param Boundary|null                                              $boundary         The boundary in the stored rule, or `null` when there is none.
	 * @param bool                                                       $has_own_date     Whether the ticket has a date of its own for the boundary.
	 * @param array{mode: string, value: int, unit: int, anchor: string} $default_relative The relative boundary offered when the rule has none.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor: string} The mode, and the relative values the form offers.
	 */
	private function get_boundary_fields( ?Boundary $boundary, bool $has_own_date, array $default_relative ): array {
		if ( ! $boundary ) {
			return array_merge( $default_relative, [ 'mode' => $has_own_date ? Rule::MODE_SPECIFIC : Rule::MODE_DEFAULT ] );
		}

		if ( Rule::MODE_RELATIVE === $boundary->get_mode() ) {
			return $boundary->to_array();
		}

		return array_merge( $default_relative, [ 'mode' => $boundary->get_mode() ] );
	}
}
