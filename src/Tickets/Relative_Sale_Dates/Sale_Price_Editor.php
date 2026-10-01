<?php
/**
 * Offers the sale price window options in the classic ticket editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Template as Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Replaces the sale price fields of the classic ticket form with the sale price window options.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Price_Editor extends Controller_Contract {
	/**
	 * The start the form offers when the ticket has no rule: now, with 2 weeks before the event starts as the relative
	 * choice.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int}
	 */
	private const DEFAULT_START = [
		'mode'  => Sale_Price_Rule::MODE_NOW,
		'value' => 2,
		'unit'  => WEEK_IN_SECONDS,
	];

	/**
	 * The end the form offers when the ticket has no rule: 1 week before the event starts.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int}
	 */
	private const DEFAULT_END = [
		'mode'  => Rule::MODE_RELATIVE,
		'value' => 1,
		'unit'  => WEEK_IN_SECONDS,
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
	 * Sale_Price_Editor constructor.
	 *
	 * @since TBD
	 *
	 * @param Container  $container  The DI container.
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 */
	public function __construct( Container $container, Rule_Store $rule_store ) {
		parent::__construct( $container );

		$this->rule_store = $rule_store;
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tribe_template_include_html:tickets/admin-views/commerce/metabox/sale-price', [ $this, 'render_sale_price_fields' ] );
	}

	/**
	 * Renders the sale price window options in place of the sale price fields of a ticket on an event.
	 *
	 * Only the fields template's own output is replaced, so what other code renders before and after it stays. The
	 * template only renders for Tickets Commerce tickets.
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
		$context = $template->get_values();

		if ( ! $this->applies_to( $context ) ) {
			return $html;
		}

		$ticket = $context['ticket'] ?? null;
		$rule   = $ticket instanceof Ticket_Object ? Sale_Price_Rule::from_stored( $this->rule_store->get( $ticket->ID ) ) : null;
		// A sale price saved without a rule has dates of its own, so it opens on them rather than on the defaults.
		$keeps_dates = ! $rule && tribe_is_truthy( $context['sale_checkbox_on'] ?? false );

		$context['sale_price_window']    = [
			'start' => $this->get_boundary_fields( $rule ? $rule->get_start() : null, $keeps_dates, self::DEFAULT_START ),
			'end'   => $this->get_boundary_fields( $rule ? $rule->get_end() : null, $keeps_dates, self::DEFAULT_END ),
		];
		$context['sale_price_rule_json'] = $rule ? $rule->to_json() : '';

		return $template->template( 'relative-sale-dates/sale-price', $context, false );
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tribe_template_include_html:tickets/admin-views/commerce/metabox/sale-price', [ $this, 'render_sale_price_fields' ], 10, 4 );
	}

	/**
	 * Returns whether the sale price window options apply to the ticket the panel is for.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The ticket panel data.
	 *
	 * @return bool Whether the panel is for a ticket on an event that is not a Series Pass.
	 */
	private function applies_to( array $context ): bool {
		return 'tribe_events' === get_post_type( $context['post_id'] ?? 0 )
			&& Series_Passes::TICKET_TYPE !== ( $context['ticket_type'] ?? 'default' );
	}

	/**
	 * Gets the values the form shows for one boundary of the sale price window.
	 *
	 * @since TBD
	 *
	 * @param Sale_Price_Boundary|null                   $boundary    The boundary in the stored rule, or `null` when there is none.
	 * @param bool                                       $keeps_dates Whether a ticket without a rule has sale price dates to keep.
	 * @param array{mode: string, value: int, unit: int} $defaults    The mode and relative values offered when the rule has none.
	 *
	 * @return array{mode: string, value: int, unit: int} The mode, and the relative values the form offers.
	 */
	private function get_boundary_fields( ?Sale_Price_Boundary $boundary, bool $keeps_dates, array $defaults ): array {
		if ( $boundary && Rule::MODE_RELATIVE === $boundary->get_mode() ) {
			return $boundary->to_array();
		}

		if ( $boundary ) {
			return array_merge( $defaults, [ 'mode' => $boundary->get_mode() ] );
		}

		return $keeps_dates ? array_merge( $defaults, [ 'mode' => Rule::MODE_SPECIFIC ] ) : $defaults;
	}
}
