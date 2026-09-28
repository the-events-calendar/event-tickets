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

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Template as Template;

/**
 * Replaces the sale dates fields of the classic ticket form with the sales window options.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Editor extends Controller_Contract {
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
		'unit'   => Rule::UNIT_WEEKS,
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
		'unit'   => Rule::UNIT_HOURS,
		'anchor' => Rule::ANCHOR_START,
	];

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tribe_template_include_html:tickets/admin-views/editor/panel/fields/dates', [ $this, 'render_sales_window_fields' ] );
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
			'start' => $this->get_end_fields( $rule ? $rule->get_start() : null, $is_new, self::DEFAULT_RELATIVE_START ),
			'end'   => $this->get_end_fields( $rule ? $rule->get_end() : null, $is_new, self::DEFAULT_RELATIVE_END ),
		];
		$context['rule_json']    = $rule ? $rule->to_json() : '';

		return $template->template( 'relative-sale-dates/sales-window', $context, false );
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tribe_template_include_html:tickets/admin-views/editor/panel/fields/dates', [ $this, 'render_sales_window_fields' ], 10, 4 );
	}

	/**
	 * Returns whether the sales window options apply to the ticket the panel is for.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The ticket panel data.
	 *
	 * @return bool Whether the panel is for a Tickets Commerce ticket, not an RSVP or a Series Pass, on an event.
	 */
	private function applies_to( array $context ): bool {
		// An event defaults to the Tickets Commerce provider even when Tickets Commerce is not active.
		return 'tribe_events' === get_post_type( $context['post_id'] ?? 0 )
			&& Module::class === ( $context['provider_class'] ?? '' )
			&& isset( $context['modules'][ Module::class ] )
			&& ! in_array( $context['ticket_type'] ?? 'default', [ 'rsvp', Series_Passes::TICKET_TYPE ], true );
	}

	/**
	 * Gets the values the form shows for one end of the sales window.
	 *
	 * A ticket saved without a rule has dates of its own, so it opens on them rather than on the defaults of a new ticket.
	 *
	 * @since TBD
	 *
	 * @param array{mode: string, value?: int, unit?: int, anchor?: string}|null $end              The end in the stored rule, or `null` when there is none.
	 * @param bool                                                               $is_new           Whether the ticket is new.
	 * @param array{mode: string, value: int, unit: int, anchor: string}         $default_relative The relative end offered when the rule has none.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor: string} The mode, and the relative values the form offers.
	 */
	private function get_end_fields( ?array $end, bool $is_new, array $default_relative ): array {
		if ( ! $end ) {
			return array_merge( $default_relative, [ 'mode' => $is_new ? Rule::MODE_DEFAULT : Rule::MODE_SPECIFIC ] );
		}

		return Rule::MODE_RELATIVE === $end['mode'] ? $end : array_merge( $default_relative, [ 'mode' => $end['mode'] ] );
	}
}
