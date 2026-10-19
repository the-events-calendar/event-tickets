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

use InvalidArgumentException;
use TEC\Tickets\Commerce\Module;
use Tribe__Date_Utils as Dates;
use Tribe__Template as Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Ticket_Provider;

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
	public const DEFAULT_RELATIVE_START = [
		'mode'   => Rule::MODE_RELATIVE,
		'value'  => 2,
		'unit'   => WEEK_IN_SECONDS,
		'anchor' => Rule::ANCHOR_START,
	];

	/**
	 * The relative end the form offers when the ticket has none: 1 hour before the event starts.
	 *
	 * The Ticket block offers it too, from the data its script is localized with.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int, anchor: string}
	 */
	public const DEFAULT_RELATIVE_END = [
		'mode'   => Rule::MODE_RELATIVE,
		'value'  => 1,
		'unit'   => HOUR_IN_SECONDS,
		'anchor' => Rule::ANCHOR_START,
	];

	/**
	 * The modes a ticket preset's sales window offers: a preset has no event to pick a specific date for.
	 *
	 * Event Tickets Plus checks the rule a preset form posts against them.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	public const PRESET_MODES = [ Rule::MODE_DEFAULT, Rule::MODE_RELATIVE ];

	/**
	 * The relative start the sale price form offers when the ticket has none: 2 weeks before the event starts.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int}
	 */
	private const SALE_PRICE_DEFAULT_RELATIVE_START = [
		'mode'  => Rule::MODE_RELATIVE,
		'value' => 2,
		'unit'  => WEEK_IN_SECONDS,
	];

	/**
	 * The relative end the sale price form offers when the ticket has none: 1 week before the event starts.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value: int, unit: int}
	 */
	private const SALE_PRICE_DEFAULT_RELATIVE_END = [
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
	 * The providers the relative sales window applies to.
	 *
	 * @since TBD
	 *
	 * @var Providers
	 */
	private Providers $providers;

	/**
	 * Editor constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 * @param Providers  $providers  The providers the relative sales window applies to.
	 */
	public function __construct( Rule_Store $rule_store, Providers $providers ) {
		$this->rule_store = $rule_store;
		$this->providers  = $providers;
	}

	/**
	 * Adds to a ticket row of the tickets list the attributes the classic script rewrites its sale dates from.
	 *
	 * The attributes are set on every row, empty for a ticket without a rule: the template merges each row's context
	 * into the values the next row inherits.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $context The context of the ticket row's sale dates template.
	 *
	 * @return array<string,mixed>|mixed The context, with `relative_sale_dates_attributes`, or what was passed in when it
	 *                                   is not an array.
	 */
	public function filter_available_dates_context( $context ) {
		if ( ! is_array( $context ) ) {
			return $context;
		}

		$ticket = $context['ticket'] ?? null;
		// Tickets Commerce caches a ticket with the ID it was first loaded with, which can be a numeric string.
		$rule = $ticket instanceof Ticket_Object ? Rule::from_stored( $this->rule_store->get( absint( $ticket->ID ) ), Window_Kind::sales() ) : null;

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
	 * Renders the sales window options in place of the sale dates fields of a ticket on an event, for a provider the
	 * relative sales window applies to.
	 *
	 * Only the fields template's own output is replaced, so what other code renders before and after it stays.
	 *
	 * @since TBD
	 *
	 * @param string|mixed $html     The HTML of the sale dates fields, as an earlier callback may have filtered it.
	 * @param string       $file     The path of the sale dates fields template.
	 * @param string[]     $name     The template name.
	 * @param Template     $template The admin views template, holding the ticket panel data.
	 *
	 * @return string|mixed The sales window options, or the HTML passed in when they do not apply.
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
	 * @param string|mixed $html     The HTML of the sale price fields, as an earlier callback may have filtered it.
	 * @param string       $file     The path of the sale price fields template.
	 * @param string[]     $name     The template name.
	 * @param Template     $template The admin views template, holding the ticket panel data.
	 *
	 * @return string|mixed The sale price window options, or the HTML passed in when they do not apply.
	 */
	public function render_sale_price_fields( $html, string $file, array $name, Template $template ) {
		return $this->render_fields( $html, $template, Window_Kind::sale_price() );
	}

	/**
	 * Gets the boundaries the classic form opens a ticket without a rule on: the mode, and the relative values each end
	 * offers next to a boundary shown in another mode.
	 *
	 * The Ticket block opens a new window on the same boundaries.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind $kind The kind of the window.
	 *
	 * @return array{start: array{mode: string, value: int, unit: int, anchor?: string}, end: array{mode: string, value: int, unit: int, anchor?: string}} The
	 *         mode and relative values of each end; the anchor only for a kind that takes one.
	 */
	public function get_form_defaults( Window_Kind $kind ): array {
		// A new ticket's sale price starts now and ends a week before the event starts.
		if ( Window_Kind::SALE_PRICE === $kind->get_id() ) {
			return [
				'start' => [ 'mode' => Rule::MODE_NOW ] + self::SALE_PRICE_DEFAULT_RELATIVE_START,
				'end'   => self::SALE_PRICE_DEFAULT_RELATIVE_END,
			];
		}

		// A new ticket opens on the default modes; the relative values are the ones the Ticket block offers too.
		return [
			'start' => [ 'mode' => Rule::MODE_DEFAULT ] + self::DEFAULT_RELATIVE_START,
			'end'   => [ 'mode' => Rule::MODE_DEFAULT ] + self::DEFAULT_RELATIVE_END,
		];
	}

	/**
	 * Gets the context the `relative-sale-dates/sales-window` admin view renders the sales window options of a ticket
	 * preset with.
	 *
	 * A preset has no event: it offers the default and relative modes of the ticket form, with no specific date, no
	 * helper text and no field of its own for the rule, which the preset form sends. A boundary the preset form has no
	 * option for, or does not have, shows the ticket form's default.
	 *
	 * Render the view with an admin views template of its own, not the shared `tickets.admin.views` one: that one
	 * carries each render's context into the next, and renders the ticket form on the screen where a preset can be saved.
	 *
	 * @since TBD
	 *
	 * @param array{start?: array<string,int|string>|null, end?: array<string,int|string>|null} $rule         The preset's sales window rule, each
	 *                                                                                           boundary in its array form.
	 * @param string                                                                            $field_prefix The prefix of the ids of the
	 *                                                                                           window's inputs.
	 *
	 * @return array{window_kind: Window_Kind, window_fields: array{start: array{mode: string, value: int, unit: int, anchor?: string}, end: array{mode: string, value: int, unit: int, anchor?: string}}, window_modes: array{start: string[], end: string[]}, variant: string, field_prefix: string, rule_json: string, unit_names: array<int,string[]>} The
	 *         context of the view.
	 */
	public function get_preset_sales_window_context( array $rule, string $field_prefix ): array {
		$kind = Window_Kind::sales();

		return [
			'window_kind'   => $kind,
			'window_fields' => [
				'start' => $this->get_preset_boundary_fields( $rule['start'] ?? null, $kind, Boundary::EDGE_START ),
				'end'   => $this->get_preset_boundary_fields( $rule['end'] ?? null, $kind, Boundary::EDGE_END ),
			],
			'window_modes'  => [
				'start' => self::PRESET_MODES,
				'end'   => self::PRESET_MODES,
			],
			'variant'       => 'preset',
			'field_prefix'  => $field_prefix,
			'rule_json'     => '',
			'unit_names'    => $this->get_unit_names_by_number( $kind ),
		];
	}

	/**
	 * Gets the name of a unit of a relative sale date for a number of it, as the classic templates and scripts show it.
	 *
	 * The msgids and context match the scripts' (`app/unit-names.js`), so one translation serves both.
	 *
	 * @since TBD
	 *
	 * @param int $unit   The unit, in seconds.
	 * @param int $number The number of units.
	 *
	 * @return string The name, or an empty string for a unit the rules have not.
	 */
	public function get_unit_name( int $unit, int $number ): string {
		switch ( $unit ) {
			case MINUTE_IN_SECONDS:
				return _nx( 'minute', 'minutes', $number, 'Unit of a relative ticket sale date.', 'event-tickets' );
			case HOUR_IN_SECONDS:
				return _nx( 'hour', 'hours', $number, 'Unit of a relative ticket sale date.', 'event-tickets' );
			case DAY_IN_SECONDS:
				return _nx( 'day', 'days', $number, 'Unit of a relative ticket sale date.', 'event-tickets' );
			case WEEK_IN_SECONDS:
				return _nx( 'week', 'weeks', $number, 'Unit of a relative ticket sale date.', 'event-tickets' );
			default:
				return '';
		}
	}

	/**
	 * Returns whether the relative options of a window apply to the fields a template renders.
	 *
	 * The template's `provider` is the one whose fields it renders: the ticket's for the panel's own fields, and the
	 * event's for a new ticket's price fields. The sales window applies to the providers `Providers` accepts, while it
	 * is active; the sale price window to Tickets Commerce alone, whose fields it replaces.
	 *
	 * A front-end form, such as Community Events', loads its panels through admin-ajax, where `is_admin()` is true, so it
	 * says it is not in wp-admin with an `is_admin` request var, which the ticket controls read the same way
	 * (`Tribe__Tickets__Metabox::get_ticket_controls()`).
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The ticket panel data, as the template holds it.
	 * @param Window_Kind         $kind    The kind of the window.
	 *
	 * @return bool Whether the fields are the wp-admin ones of an active provider the window applies to, for a ticket
	 *              that is not an RSVP or a Series Pass, on an event.
	 */
	private function applies_to( array $context, Window_Kind $kind ): bool {
		$provider       = $context['provider'] ?? null;
		$provider_class = $provider instanceof Ticket_Provider ? $provider->class_name : '';
		// An event defaults to the Tickets Commerce provider even when Tickets Commerce is not active.
		$is_active_accepted = $kind->owns_ticket_sales_dates()
			? isset( $this->providers->get_active()[ $provider_class ] )
			: Module::class === $provider_class && isset( $context['modules'][ Module::class ] );

		return 'tribe_events' === get_post_type( $context['post_id'] ?? 0 )
			&& $is_active_accepted
			&& ! in_array( $context['ticket_type'] ?? 'default', array_merge( [ 'rsvp' ], Ticket_Save::EXCLUDED_TICKET_TYPES ), true )
			// A front-end form, such as Community Events', does not load the script that writes the rule.
			&& tribe_is_truthy( tec_get_request_var( 'is_admin', is_admin() ) );
	}

	/**
	 * Returns whether a saved ticket has a date of its own for one boundary of a window.
	 *
	 * A ticket created on a page and moved to an event, or created by code, can have no end date. Saving it without one
	 * gave it the event start, which is what the default end resolves to.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of the window.
	 * @param string      $end       The end of the window, `start` or `end`.
	 *
	 * @return bool Whether the ticket has a date stored for the boundary.
	 */
	private function has_own_date( int $ticket_id, Window_Kind $kind, string $end ): bool {
		return '' !== get_post_meta( $ticket_id, $kind->get_date_metas()[ $end ]['date'], true );
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
	 * @param Boundary|null $boundary     The boundary in the stored rule, or `null` when there is none.
	 * @param bool          $has_own_date Whether the ticket has a date of its own for the boundary.
	 * @param Window_Kind   $kind         The kind of the window.
	 * @param string        $end          The end of the window, `start` or `end`.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor?: string} The mode, and the relative values the form
	 *                                                                      offers.
	 */
	private function get_boundary_fields( ?Boundary $boundary, bool $has_own_date, Window_Kind $kind, string $end ): array {
		$defaults = $this->get_form_defaults( $kind )[ $end ];

		if ( ! $boundary ) {
			return $has_own_date ? array_merge( $defaults, [ 'mode' => Rule::MODE_SPECIFIC ] ) : $defaults;
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
	 * @param string|mixed $html     The HTML of the fields, as an earlier callback may have filtered it.
	 * @param Template     $template The admin views template, holding the ticket panel data.
	 * @param Window_Kind  $kind     The kind of the window.
	 *
	 * @return string|mixed The window options, or the HTML passed in when they do not apply.
	 */
	private function render_fields( $html, Template $template, Window_Kind $kind ) {
		$context = $template->get_values();

		if ( ! $this->applies_to( $context, $kind ) ) {
			return $html;
		}

		$rule   = Rule::from_raw( $context[ $kind->get_rule_keys()['data'] ] ?? null, $kind );
		$ticket = $context['ticket'] ?? null;
		// Tickets Commerce caches a ticket with the ID it was first loaded with, which can be a numeric string.
		$ticket_id = $ticket instanceof Ticket_Object ? absint( $ticket->ID ) : 0;
		// A saved ticket without the window, such as one without a sale price, has no dates of its own for it.
		$keeps_dates = $ticket_id && $kind->is_enabled_for_ticket( $ticket_id );
		// Only a missing ticket sales date was saved as the event start; a sale price keeps opening on its own dates.
		$has_date = fn( string $end ): bool => $keeps_dates
			&& ( ! $kind->owns_ticket_sales_dates() || $this->has_own_date( $ticket_id, $kind, $end ) );

		$context['window_kind']   = $kind;
		$context['window_fields'] = [
			'start' => $this->get_boundary_fields( $rule ? $rule->get_start() : null, $has_date( 'start' ), $kind, 'start' ),
			'end'   => $this->get_boundary_fields( $rule ? $rule->get_end() : null, $has_date( 'end' ), $kind, 'end' ),
		];
		$context['rule_json']     = $rule ? $rule->to_json() : '';
		$context['window_modes']  = [
			'start' => $kind->get_modes( Boundary::EDGE_START ),
			'end'   => $kind->get_modes( Boundary::EDGE_END ),
		];
		$context['variant']       = 'ticket';
		$context['field_prefix']  = 'ticket_sales';
		$context['unit_names']    = [];

		return $template->template( $this->get_template( $kind ), $context, false );
	}

	/**
	 * Gets the values a ticket preset's sales window shows for one boundary.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string>|mixed $data The boundary in its array form, as the preset stores it.
	 * @param Window_Kind                    $kind The sales window kind.
	 * @param string                         $end  The end of the window, `start` or `end`.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor?: string} The mode, and the relative values the form
	 *                                                                      offers.
	 */
	private function get_preset_boundary_fields( $data, Window_Kind $kind, string $end ): array {
		if ( ! is_array( $data ) ) {
			return $this->get_form_defaults( $kind )[ $end ];
		}

		try {
			$boundary = Boundary::from_array( $data, $kind, $end );
		} catch ( InvalidArgumentException $e ) {
			return $this->get_form_defaults( $kind )[ $end ];
		}

		if ( ! in_array( $boundary->get_mode(), self::PRESET_MODES, true ) ) {
			return $this->get_form_defaults( $kind )[ $end ];
		}

		return $this->get_boundary_fields( $boundary, false, $kind, $end );
	}

	/**
	 * Gets the name of each unit of a kind for every number its relative boundaries take, for a form that names the unit
	 * after the number with no script of Event Tickets', such as a ticket preset's.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind $kind The kind of the window.
	 *
	 * @return array<int,string[]> The names of each unit, keyed by the unit in seconds, from the lowest number up.
	 */
	private function get_unit_names_by_number( Window_Kind $kind ): array {
		$names   = [];
		$numbers = range( Boundary::MIN_VALUE, $kind->get_max_value() );

		foreach ( $kind->get_units() as $unit ) {
			$names[ $unit ] = array_map( fn( int $number ): string => $this->get_unit_name( $unit, $number ), $numbers );
		}

		return $names;
	}

	/**
	 * Gets the admin view that renders a window's options in the classic ticket form.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind $kind The kind of the window.
	 *
	 * @return string The template name, relative to the admin views folder.
	 */
	private function get_template( Window_Kind $kind ): string {
		return Window_Kind::SALE_PRICE === $kind->get_id() ? 'relative-sale-dates/sale-price' : 'relative-sale-dates/sales-window';
	}
}
