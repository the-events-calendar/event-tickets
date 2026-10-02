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
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Date_Utils as Dates;
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
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * Editor constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 */
	public function __construct( Rule_Store $rule_store ) {
		$this->rule_store = $rule_store;
	}

	/**
	 * Adds to a ticket row of the tickets list the attributes the classic script rewrites its sale dates from.
	 *
	 * The attributes are set on every row, empty for a ticket without a rule: the template merges each row's context
	 * into the values the next row inherits.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The context of the ticket row's sale dates template.
	 *
	 * @return array<string,mixed> The context, with `relative_sale_dates_attributes`.
	 */
	public function filter_available_dates_context( array $context ): array {
		$ticket = $context['ticket'] ?? null;
		$rule   = $ticket instanceof Ticket_Object ? Rule::from_stored( $this->rule_store->get( $ticket->ID ) ) : null;

		$context['relative_sale_dates_attributes'] = $rule
			? [
				'data-relative-sale-dates' => $rule->to_json(),
				'data-sale-start'          => $this->get_sale_date( $ticket->start_date ),
				'data-sale-end'            => $this->get_sale_date( $ticket->end_date ),
			]
			: [];

		return $context;
	}

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
		$is_new = empty( $context['ticket'] );

		$context['sales_window'] = [
			'start' => $this->get_boundary_fields( $rule ? $rule->get_start() : null, $is_new, self::DEFAULT_RELATIVE_START ),
			'end'   => $this->get_boundary_fields( $rule ? $rule->get_end() : null, $is_new, self::DEFAULT_RELATIVE_END ),
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
	 * Gets a ticket sale date as the classic script reads it.
	 *
	 * @since TBD
	 *
	 * @param string|null $date The ticket sale date.
	 *
	 * @return string The date, `Y-m-d`, or an empty string when the ticket has none.
	 */
	private function get_sale_date( ?string $date ): string {
		return $date ? Dates::date_only( $date, false, Dates::DBDATEFORMAT ) : '';
	}

	/**
	 * Gets the values the form shows for one boundary of the sales window.
	 *
	 * A ticket saved without a rule has dates of its own, so it opens on them rather than on the defaults of a new ticket.
	 *
	 * @since TBD
	 *
	 * @param Boundary|null                                              $boundary         The boundary in the stored rule, or `null` when there is none.
	 * @param bool                                                       $is_new           Whether the ticket is new.
	 * @param array{mode: string, value: int, unit: int, anchor: string} $default_relative The relative boundary offered when the rule has none.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor: string} The mode, and the relative values the form offers.
	 */
	private function get_boundary_fields( ?Boundary $boundary, bool $is_new, array $default_relative ): array {
		if ( ! $boundary ) {
			return array_merge( $default_relative, [ 'mode' => $is_new ? Rule::MODE_DEFAULT : Rule::MODE_SPECIFIC ] );
		}

		if ( Rule::MODE_RELATIVE === $boundary->get_mode() ) {
			return $boundary->to_array();
		}

		return array_merge( $default_relative, [ 'mode' => $boundary->get_mode() ] );
	}
}
