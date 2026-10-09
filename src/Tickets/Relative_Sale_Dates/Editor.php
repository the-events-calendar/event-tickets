<?php
/**
 * Offers the sales window and sale price window options in the classic ticket editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Module;
use Tribe__Date_Utils as Dates;
use Tribe__Template as Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Replaces the sale dates and sale price fields of the classic ticket form with the options of each window kind.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Editor {
	/**
	 * The relative start the form offers when the ticket has none: 2 weeks before the event starts.
	 *
	 * The Ticket block offers it too, from the data its script is localized with.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int, anchor: string}
	 */
	public const DEFAULT_RELATIVE_START = Window_Kind::SALES_DEFAULT_RELATIVE_START;

	/**
	 * The relative end the form offers when the ticket has none: 1 hour before the event starts.
	 *
	 * The Ticket block offers it too, from the data its script is localized with.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int, anchor: string}
	 */
	public const DEFAULT_RELATIVE_END = Window_Kind::SALES_DEFAULT_RELATIVE_END;

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
		return $this->render_fields( $html, $template, Window_Kind::sales() );
	}

	/**
	 * Renders the sale price window options in place of the sale price fields of a Tickets Commerce ticket on an event.
	 *
	 * Only the fields template's own output is replaced, so what other code renders before and after it stays.
	 *
	 * @since TBD
	 *
	 * @param mixed    $html     The HTML of the sale price fields, as an earlier callback may have filtered it.
	 * @param string   $file     The path of the sale price fields template.
	 * @param string[] $name     The template name.
	 * @param Template $template The admin views template, holding the ticket panel data.
	 *
	 * @return mixed The sale price window options, or the HTML passed in when they do not apply.
	 */
	public function render_sale_price_fields( $html, string $file, array $name, Template $template ) {
		return $this->render_fields( $html, $template, Window_Kind::sale_price() );
	}

	/**
	 * Returns whether the relative options apply to the fields a template renders.
	 *
	 * The template's `provider` is the one whose fields it renders: the ticket's for the panel's own fields, and the
	 * event's for the price fields, which Tickets Commerce renders in the panel of a ticket of any provider.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The ticket panel data, as the template holds it.
	 *
	 * @return bool Whether the fields are the wp-admin ones of Tickets Commerce, for a ticket that is not an RSVP or a
	 *              Series Pass, on an event.
	 */
	private function applies_to( array $context ): bool {
		// An event defaults to the Tickets Commerce provider even when Tickets Commerce is not active.
		return 'tribe_events' === get_post_type( $context['post_id'] ?? 0 )
			&& ( $context['provider'] ?? null ) instanceof Module
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
	 * Gets the values the form shows for one boundary of a window.
	 *
	 * A ticket saved with the window but without a rule has dates of its own, so it opens on them rather than on the
	 * defaults.
	 *
	 * @since TBD
	 *
	 * @param Boundary|null $boundary    The boundary in the stored rule, or `null` when there is none.
	 * @param bool          $keeps_dates Whether the ticket has dates of its own for the window.
	 * @param Window_Kind   $kind        The kind of the window.
	 * @param string        $end         The end of the window, `start` or `end`.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor?: string} The mode, and the relative values the form
	 *                                                                      offers.
	 */
	private function get_boundary_fields( ?Boundary $boundary, bool $keeps_dates, Window_Kind $kind, string $end ): array {
		$defaults = $kind->get_form_defaults()[ $end ];

		if ( ! $boundary ) {
			return $keeps_dates ? array_merge( $defaults, [ 'mode' => Rule::MODE_SPECIFIC ] ) : $defaults;
		}

		if ( Rule::MODE_RELATIVE === $boundary->get_mode() ) {
			return $boundary->to_array();
		}

		return array_merge( $defaults, [ 'mode' => $boundary->get_mode() ] );
	}

	/**
	 * Renders a window's options in place of the fields a template rendered for it.
	 *
	 * @since TBD
	 *
	 * @param mixed       $html     The HTML of the fields, as an earlier callback may have filtered it.
	 * @param Template    $template The admin views template, holding the ticket panel data.
	 * @param Window_Kind $kind     The kind of the window.
	 *
	 * @return mixed The window options, or the HTML passed in when they do not apply.
	 */
	private function render_fields( $html, Template $template, Window_Kind $kind ) {
		$context = $template->get_values();

		if ( ! $this->applies_to( $context ) ) {
			return $html;
		}

		$rule   = Rule::from_raw( $context[ $kind->get_rule_keys()['data'] ] ?? null, $kind );
		$ticket = $context['ticket'] ?? null;
		// A saved ticket without the window, such as one without a sale price, has no dates of its own for it.
		$keeps_dates = $ticket instanceof Ticket_Object && $kind->is_enabled_for_ticket( $ticket->ID );

		$context['window_kind']   = $kind;
		$context['window_fields'] = [
			'start' => $this->get_boundary_fields( $rule ? $rule->get_start() : null, $keeps_dates, $kind, 'start' ),
			'end'   => $this->get_boundary_fields( $rule ? $rule->get_end() : null, $keeps_dates, $kind, 'end' ),
		];
		$context['rule_json']     = $rule ? $rule->to_json() : '';

		return $template->template( $kind->get_classic_template(), $context, false );
	}
}
