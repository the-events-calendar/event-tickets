<?php
/**
 * The kinds of window a ticket's relative sale dates describe: its sales window and its sale price window.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use Closure;
use TEC\Tickets\Commerce\Ticket;
use WP_Error;

/**
 * An immutable kind of window: the modes, values, units and anchors its rule accepts, where the rule and the dates it
 * resolves to are submitted and stored, how the window relates to the ticket's own sales, and the errors that reject it.
 *
 * The rules, boundaries, resolver, writer, save and validation work the same for every kind; only these differ.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Window_Kind {
	/**
	 * The ticket's sales window.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SALES = 'sales';

	/**
	 * The ticket's sale price window.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SALE_PRICE = 'sale_price';

	/**
	 * The kinds built so far, keyed by their ID, so each kind has a single instance.
	 *
	 * @since TBD
	 *
	 * @var array<string,self>
	 */
	private static array $instances = [];

	/**
	 * The kind's ID, one of the class constants.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * The modes each end of the window accepts, keyed by the end, `start` or `end`.
	 *
	 * @since TBD
	 *
	 * @var array{start: string[], end: string[]}
	 */
	private array $modes;

	/**
	 * The start mode that opens the window at once.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $open_start_mode;

	/**
	 * The highest number of units a relative boundary accepts.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $max_value;

	/**
	 * The units a relative boundary accepts, as their length in seconds.
	 *
	 * @since TBD
	 *
	 * @var int[]
	 */
	private array $units;

	/**
	 * The event dates a relative boundary may be counted from, the implied one first.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private array $anchors;

	/**
	 * The key the rule is stored under in the shared rules meta, or `null` for its top level.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	private ?string $store_key;

	/**
	 * The kind whose window this one is judged against, or `null` for none.
	 *
	 * @since TBD
	 *
	 * @var self|null
	 */
	private ?self $parent;

	/**
	 * The keys that carry the rule: `data`, the ticket data key a save sends it under; `tec_rest`, the TEC REST API
	 * ticket field; and `block_editor_request` and `block_editor_response`, the paths of keys to the rule in the
	 * `ticket` param of a block editor ticket save and in the ticket data the block editor reads.
	 *
	 * @since TBD
	 *
	 * @var array{data: string, tec_rest: string, block_editor_request: string[], block_editor_response: string[]}
	 */
	private array $rule_keys;

	/**
	 * The ticket metas each end of the window is written to: its date and, for a kind that stores times, its time.
	 *
	 * @since TBD
	 *
	 * @var array{start: array{date: string, time: ?string}, end: array{date: string, time: ?string}}
	 */
	private array $date_metas;

	/**
	 * The value an open start is written as, or `null` to leave the ticket's own start.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	private ?string $open_start_value;

	/**
	 * The keys of the sale the window belongs to, or `null` for a window every ticket has: `enabled_meta`, the ticket
	 * meta that turns the sale on; `enabled_data`, the ticket data field that keeps it when the ticket is saved; and
	 * `price` and `regular_price`, the ticket data fields of the sale price and of the price it must be lower than.
	 *
	 * @since TBD
	 *
	 * @var array{enabled_meta: string, enabled_data: string, price: string, regular_price: string}|null
	 */
	private ?array $sale_keys;

	/**
	 * Whether the window's dates are the ticket's own sales dates: the ones the ticket is on sale between, which
	 * `ticket_add()` fills in, the sales actions announce and the event start moves.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $owns_ticket_sales_dates;

	/**
	 * The ticket data fields each end of the window is submitted in: its date and, for a kind that stores times, its time.
	 *
	 * @since TBD
	 *
	 * @var array{start: array{date: string, time: ?string}, end: array{date: string, time: ?string}}
	 */
	private array $submitted_fields;

	/**
	 * Whether a specific boundary sent without a date leaves the window without one, which the save rejects.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $specific_needs_date;

	/**
	 * The errors that reject the window, keyed as the editors key them: `endsBeforeStart` for an invalid rule or a window
	 * that ends before it starts, and `outsideParent` for a window that starts outside its parent window.
	 *
	 * Each message is a closure, translated when the error is built: a kind can be built before the text domain loads,
	 * and it lasts the whole request.
	 *
	 * @since TBD
	 *
	 * @var array<string,array{code: string, message: Closure(): string}>
	 */
	private array $errors;

	/**
	 * Whether the block editor ticket data of every provider carries the rule, or only that of Tickets Commerce tickets.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $returns_block_editor_rule_for_every_provider;

	/**
	 * The copy the TEC REST API documents the rule with, each a closure translated when it is read: the rule, its
	 * `start` and `end` boundaries and their modes, and the value and unit, which take the kind's range and units.
	 *
	 * @since TBD
	 *
	 * @var array{rule: Closure(): string, start: Closure(): string, end: Closure(): string, start_mode: Closure(): string, end_mode: Closure(): string, value: Closure(int, int): string, unit: Closure(int ...): string}
	 */
	private array $rest_descriptions;

	/**
	 * The boundaries the classic form opens a ticket without a rule on: the mode, and the relative values each end
	 * offers.
	 *
	 * @since TBD
	 *
	 * @var array{start: array{mode: string, value: int, unit: int, anchor?: string}, end: array{mode: string, value: int, unit: int, anchor?: string}}
	 */
	private array $form_defaults;

	/**
	 * The admin view that renders the window's options in the classic ticket form.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $classic_template;

	/**
	 * Gets the sales window kind.
	 *
	 * @since TBD
	 *
	 * @return self The sales window kind.
	 */
	public static function sales(): self {
		if ( isset( self::$instances[ self::SALES ] ) ) {
			return self::$instances[ self::SALES ];
		}

		$modes = [ Rule::MODE_DEFAULT, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ];
		$kind  = new self();

		$kind->id                      = self::SALES;
		$kind->modes                   = [
			'start' => $modes,
			'end'   => $modes,
		];
		$kind->open_start_mode         = Rule::MODE_DEFAULT;
		$kind->max_value               = Boundary::MAX_VALUE;
		$kind->units                   = [ MINUTE_IN_SECONDS, HOUR_IN_SECONDS, DAY_IN_SECONDS, WEEK_IN_SECONDS ];
		$kind->anchors                 = [ Rule::ANCHOR_START, Rule::ANCHOR_END ];
		$kind->store_key               = null;
		$kind->parent                  = null;
		$kind->rule_keys               = [
			'data'                  => Ticket_Save::DATA_KEY,
			'tec_rest'              => 'relative_sale_dates',
			'block_editor_request'  => [ 'relative_sale_dates' ],
			'block_editor_response' => [ 'relative_sale_dates' ],
		];
		$kind->date_metas              = [
			'start' => [
				'date' => Ticket::START_DATE_META_KEY,
				'time' => Ticket::START_TIME_META_KEY,
			],
			'end'   => [
				'date' => Ticket::END_DATE_META_KEY,
				'time' => Ticket::END_TIME_META_KEY,
			],
		];
		$kind->open_start_value        = null;
		$kind->sale_keys               = null;
		$kind->owns_ticket_sales_dates = true;
		$kind->submitted_fields        = [
			'start' => [
				'date' => 'ticket_start_date',
				'time' => 'ticket_start_time',
			],
			'end'   => [
				'date' => 'ticket_end_date',
				'time' => 'ticket_end_time',
			],
		];
		$kind->specific_needs_date     = true;
		$kind->errors                  = [
			'endsBeforeStart' => [
				'code'    => 'tec_tickets_relative_sale_dates_invalid_window',
				'message' => static fn(): string => __( 'Ticket sales cannot end before they start. Please adjust the sales window.', 'event-tickets' ),
			],
		];
		// The block editor reads `relative_sale_dates` on every ticket, as `null` on one the rule does not apply to.
		$kind->returns_block_editor_rule_for_every_provider = true;
		// A new ticket opens on the default modes; the relative values are the ones the Ticket block offers too.
		$kind->form_defaults    = [
			'start' => [ 'mode' => Rule::MODE_DEFAULT ] + Editor::DEFAULT_RELATIVE_START,
			'end'   => [ 'mode' => Rule::MODE_DEFAULT ] + Editor::DEFAULT_RELATIVE_END,
		];
		$kind->classic_template = 'relative-sale-dates/sales-window';

		$sales_mode              = static fn(): string => __( 'How this end of the window is set: `default` (sales open at once, or close when the event starts), `relative` (before the event) or `specific` (the date sent with the ticket).', 'event-tickets' );
		$kind->rest_descriptions = [
			'rule'       => static fn(): string => __( 'The sales window relative to the event, or null when the ticket has fixed sale dates. Sending null removes the rule.', 'event-tickets' ),
			'start'      => static fn(): string => __( 'When sales start.', 'event-tickets' ),
			'end'        => static fn(): string => __( 'When sales end.', 'event-tickets' ),
			'start_mode' => $sales_mode,
			'end_mode'   => $sales_mode,
			'value'      => static fn( int $min, int $max ): string => sprintf(
				// translators: 1) the lowest number of units, 2) the highest number of units.
				__( 'For a relative boundary, the number of units before the anchor, from %1$d to %2$d.', 'event-tickets' ),
				$min,
				$max
			),
			'unit'       => static fn( int ...$units ): string => sprintf(
				// translators: 1) a minute, 2) an hour, 3) a day and 4) a week, each in seconds.
				__( 'For a relative boundary, the unit in seconds: %1$d (minutes), %2$d (hours), %3$d (days) or %4$d (weeks).', 'event-tickets' ),
				...$units
			),
		];

		self::$instances[ self::SALES ] = $kind;

		return $kind;
	}

	/**
	 * Gets the sale price window kind.
	 *
	 * A relative boundary is 1 to 30 days or weeks before the event start, which it always counts from. The window is
	 * stored as whole days, and an empty start means the sale price has started.
	 *
	 * @since TBD
	 *
	 * @return self The sale price window kind.
	 */
	public static function sale_price(): self {
		if ( isset( self::$instances[ self::SALE_PRICE ] ) ) {
			return self::$instances[ self::SALE_PRICE ];
		}

		$kind = new self();

		$kind->id                      = self::SALE_PRICE;
		$kind->modes                   = [
			'start' => [ Rule::MODE_NOW, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ],
			'end'   => [ Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ],
		];
		$kind->open_start_mode         = Rule::MODE_NOW;
		$kind->max_value               = 30;
		$kind->units                   = [ DAY_IN_SECONDS, WEEK_IN_SECONDS ];
		$kind->anchors                 = [ Rule::ANCHOR_START ];
		$kind->store_key               = 'sale_price';
		$kind->parent                  = self::sales();
		$kind->rule_keys               = [
			'data'                  => 'ticket_sale_price_relative',
			'tec_rest'              => 'sale_price_relative',
			'block_editor_request'  => [ 'sale_price', 'relative' ],
			'block_editor_response' => [ 'sale_price_data', 'relative' ],
		];
		$kind->date_metas              = [
			'start' => [
				'date' => Ticket::$sale_price_start_date_key,
				'time' => null,
			],
			'end'   => [
				'date' => Ticket::$sale_price_end_date_key,
				'time' => null,
			],
		];
		$kind->open_start_value        = '';
		$kind->sale_keys               = [
			'enabled_meta'  => Ticket::$sale_price_checked_key,
			'enabled_data'  => 'ticket_add_sale_price',
			'price'         => 'ticket_sale_price',
			'regular_price' => 'ticket_price',
		];
		$kind->owns_ticket_sales_dates = false;
		$kind->submitted_fields        = [
			'start' => [
				'date' => 'ticket_sale_start_date',
				'time' => null,
			],
			'end'   => [
				'date' => 'ticket_sale_end_date',
				'time' => null,
			],
		];
		$kind->specific_needs_date     = false;
		$kind->errors                  = [
			'endsBeforeStart' => [
				'code'    => 'tec_tickets_relative_sale_dates_sale_price_ends_before_start',
				'message' => static fn(): string => __( 'The sale price cannot end before it starts. Please adjust the sale price window.', 'event-tickets' ),
			],
			'outsideParent'   => [
				'code'    => 'tec_tickets_relative_sale_dates_sale_price_outside_sales_window',
				'message' => static fn(): string => __( 'The sale price window falls outside the ticket sales window. Please adjust the dates.', 'event-tickets' ),
			],
		];
		// Every provider answers with `sale_price_data`, an empty array for an RSVP; only Tickets Commerce's has the rule.
		$kind->returns_block_editor_rule_for_every_provider = false;

		$kind->form_defaults    = [
			'start' => [
				'mode'  => Rule::MODE_NOW,
				'value' => 2,
				'unit'  => WEEK_IN_SECONDS,
			],
			'end'   => [
				'mode'  => Rule::MODE_RELATIVE,
				'value' => 1,
				'unit'  => WEEK_IN_SECONDS,
			],
		];
		$kind->classic_template = 'relative-sale-dates/sale-price';

		$kind->rest_descriptions = [
			'rule'       => static fn(): string => __( 'The sale price window relative to the event start, or null when the sale price has fixed dates. Sending null removes the rule.', 'event-tickets' ),
			'start'      => static fn(): string => __( 'When the sale price starts.', 'event-tickets' ),
			'end'        => static fn(): string => __( 'When the sale price ends.', 'event-tickets' ),
			'start_mode' => static fn(): string => __( 'How the start is set: `now` (as soon as ticket sales open), `relative` (before the event start) or `specific` (the date sent in `sale_price_start_date`).', 'event-tickets' ),
			'end_mode'   => static fn(): string => __( 'How the end is set: `relative` (before the event start) or `specific` (the date sent in `sale_price_end_date`).', 'event-tickets' ),
			'value'      => static fn( int $min, int $max ): string => sprintf(
				// translators: 1) the lowest number of units, 2) the highest number of units.
				__( 'For a relative boundary, the number of units before the event start, from %1$d to %2$d.', 'event-tickets' ),
				$min,
				$max
			),
			'unit'       => static fn( int ...$units ): string => sprintf(
				// translators: 1) a day and 2) a week, each in seconds.
				__( 'For a relative boundary, the unit in seconds: %1$d (days) or %2$d (weeks).', 'event-tickets' ),
				...$units
			),
		];

		self::$instances[ self::SALE_PRICE ] = $kind;

		return $kind;
	}

	/**
	 * Gets every kind.
	 *
	 * @since TBD
	 *
	 * @return self[] The kinds, the sales window first: the sale price is judged against it.
	 */
	public static function all(): array {
		return [ self::sales(), self::sale_price() ];
	}

	/**
	 * Gets the kind's ID.
	 *
	 * @since TBD
	 *
	 * @return string The ID, one of the class constants.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Gets the modes one end of the window accepts.
	 *
	 * @since TBD
	 *
	 * @param string $end The end of the window, `start` or `end`.
	 *
	 * @return string[] The modes, `Rule::MODE_*` constants, or none for an unknown end.
	 */
	public function get_modes( string $end ): array {
		return $this->modes[ $end ] ?? [];
	}

	/**
	 * Gets the start mode that opens the window at once.
	 *
	 * @since TBD
	 *
	 * @return string The mode, `Rule::MODE_DEFAULT` or `Rule::MODE_NOW`.
	 */
	public function get_open_start_mode(): string {
		return $this->open_start_mode;
	}

	/**
	 * Gets the highest number of units a relative boundary accepts.
	 *
	 * @since TBD
	 *
	 * @return int The highest value.
	 */
	public function get_max_value(): int {
		return $this->max_value;
	}

	/**
	 * Gets the units a relative boundary accepts.
	 *
	 * @since TBD
	 *
	 * @return int[] The units, as their length in seconds: `*_IN_SECONDS` constants.
	 */
	public function get_units(): array {
		return $this->units;
	}

	/**
	 * Gets the event dates a relative boundary may be counted from.
	 *
	 * @since TBD
	 *
	 * @return string[] The anchors, `Rule::ANCHOR_*` constants; the first is the one a kind that takes no anchor implies.
	 */
	public function get_anchors(): array {
		return $this->anchors;
	}

	/**
	 * Returns whether a relative boundary names its anchor, which it does when it may be counted from more than one.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the anchor is sent and stored; when it is not, it is implied and must not be sent.
	 */
	public function takes_anchor(): bool {
		return count( $this->anchors ) > 1;
	}

	/**
	 * Gets the key the rule is stored under in the shared rules meta.
	 *
	 * @since TBD
	 *
	 * @return string|null The key, or `null` when the rule is the meta's top level.
	 */
	public function get_store_key(): ?string {
		return $this->store_key;
	}

	/**
	 * Gets the kind whose window this one is judged against.
	 *
	 * @since TBD
	 *
	 * @return self|null The parent kind, or `null` for none.
	 */
	public function get_parent(): ?self {
		return $this->parent;
	}

	/**
	 * Returns whether the window's dates are the ticket's own sales dates, the ones the ticket is on sale between.
	 *
	 * Only such a window:
	 * - moves a ticket start later than now to now when its start opens the window at once;
	 * - lets its end follow the event start when the event moves and the rule leaves the end to the ticket;
	 * - has its dates announced by the "sales started" and "sales ended" actions, so a change to them, or to the event
	 *   timezone they are written in, reschedules those actions;
	 * - flags an end the rule now leaves to the ticket as set by hand, and clears that flag when the rule is removed by a
	 *   save without an end of its own;
	 * - has an empty date filled in the way `ticket_add()` fills it in: the day the event was published for the start,
	 *   the event start for the end.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the window's dates are the ticket's sales dates.
	 */
	public function owns_ticket_sales_dates(): bool {
		return $this->owns_ticket_sales_dates;
	}

	/**
	 * Gets the keys that carry the rule.
	 *
	 * @since TBD
	 *
	 * @return array{data: string, tec_rest: string, block_editor_request: string[], block_editor_response: string[]} The keys:
	 *         - `data`, the ticket data key a save sends the rule under, as a JSON string, an array, or `null` or `''`
	 *           to remove it;
	 *         - `tec_rest`, the TEC REST API ticket field, `relative_sale_dates` or `sale_price_relative`, whose value
	 *           is the rule as an object, or `null` to remove it;
	 *         - `block_editor_request`, the keys to the rule in the `ticket` param of a block editor ticket save,
	 *           outermost first, such as `ticket[sale_price][relative]`; the rule is sent as JSON, or `''` to remove it;
	 *         - `block_editor_response`, the keys to the stored rule in the ticket data the block editor reads, such as
	 *           `sale_price_data.relative`; the rule is returned as an array, or `null`.
	 */
	public function get_rule_keys(): array {
		return $this->rule_keys;
	}

	/**
	 * Gets the ticket metas each end of the window is written to, its date and its time.
	 *
	 * A `null` time means the kind stores whole days.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{date: string, time: ?string}, end: array{date: string, time: ?string}} The meta keys.
	 */
	public function get_date_metas(): array {
		return $this->date_metas;
	}

	/**
	 * Gets the value an open start is written as.
	 *
	 * @since TBD
	 *
	 * @return string|null The value, or `null` to leave the ticket's own start, which a kind that
	 *                     `owns_ticket_sales_dates()` may move to now.
	 */
	public function get_open_start_value(): ?string {
		return $this->open_start_value;
	}

	/**
	 * Returns whether the window is on for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return bool Whether the ticket has the window: always for its sales window, and only with a sale price for its
	 *              sale price window.
	 */
	public function is_enabled_for_ticket( int $ticket_id ): bool {
		return null === $this->sale_keys || tribe_is_truthy( get_post_meta( $ticket_id, $this->sale_keys['enabled_meta'], true ) );
	}

	/**
	 * Gets the ticket data fields each end of the window is submitted in, its date and its time.
	 *
	 * A `null` time means the kind is submitted as whole days.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{date: string, time: ?string}, end: array{date: string, time: ?string}} The field keys.
	 */
	public function get_submitted_fields(): array {
		return $this->submitted_fields;
	}

	/**
	 * Returns whether a specific boundary sent without a date is rejected.
	 *
	 * Where it is not, as for the sale price, whose dates are stored empty when none is set, an empty start counts as
	 * the day the parent window opens, and an empty end as no end.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the save rejects a specific boundary without a date.
	 */
	public function specific_needs_date(): bool {
		return $this->specific_needs_date;
	}

	/**
	 * Returns whether the window's dates are compared as days, the way they are stored and read, rather than instants.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the window is judged by the day.
	 */
	public function compares_days(): bool {
		return null === $this->date_metas['start']['time'];
	}

	/**
	 * Returns whether a save of the ticket data keeps the window.
	 *
	 * Tickets Commerce drops a sale price that is unchecked or not lower than the price, with this same `>=` comparison
	 * on the raw values.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data about to be saved.
	 *
	 * @return bool Whether the ticket will have the window once saved.
	 */
	public function is_saved_with( array $data ): bool {
		if ( null === $this->sale_keys ) {
			return true;
		}

		if ( ! tribe_is_truthy( $data[ $this->sale_keys['enabled_data'] ] ?? false ) ) {
			return false;
		}

		return ! ( ( $data[ $this->sale_keys['price'] ] ?? false ) >= ( $data[ $this->sale_keys['regular_price'] ] ?? false ) );
	}

	/**
	 * Gets an error that rejects the window.
	 *
	 * @since TBD
	 *
	 * @param string $key The error, as the editors key it: `endsBeforeStart` or `outsideParent`.
	 *
	 * @return WP_Error|null The error, with a 400 status for REST responses, or `null` for one the kind does not have.
	 */
	public function get_error( string $key ): ?WP_Error {
		if ( ! isset( $this->errors[ $key ] ) ) {
			return null;
		}

		return new WP_Error( $this->errors[ $key ]['code'], $this->errors[ $key ]['message'](), [ 'status' => 400 ] );
	}

	/**
	 * Returns whether the block editor ticket data of every provider carries the rule, or only that of Tickets Commerce
	 * tickets.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the rule, or `null`, is returned for a ticket of any provider: true for the sales window, whose
	 *              `relative_sale_dates` every ticket answers with, and false for the sale price, which only a Tickets
	 *              Commerce ticket's `sale_price_data` carries.
	 */
	public function returns_block_editor_rule_for_every_provider(): bool {
		return $this->returns_block_editor_rule_for_every_provider;
	}

	/**
	 * Gets the copy the TEC REST API documents the rule with.
	 *
	 * @since TBD
	 *
	 * @return array{rule: Closure(): string, start: Closure(): string, end: Closure(): string, start_mode: Closure(): string, end_mode: Closure(): string, value: Closure(int, int): string, unit: Closure(int ...): string} The descriptions of the rule, of its `start` and `end` boundaries and their modes, and of a relative boundary's value, given the kind's range, and unit, given its units.
	 */
	public function get_rest_descriptions(): array {
		return $this->rest_descriptions;
	}

	/**
	 * Gets the boundaries the classic form opens a ticket without a rule on.
	 *
	 * A boundary the form shows in another mode still offers these relative values next to it.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{mode: string, value: int, unit: int, anchor?: string}, end: array{mode: string, value: int, unit: int, anchor?: string}} The
	 *         default mode, relative value and unit of the start and of the end, with the anchor only for a kind that takes one.
	 */
	public function get_form_defaults(): array {
		return $this->form_defaults;
	}

	/**
	 * Gets the admin view that renders the window's options in the classic ticket form.
	 *
	 * @since TBD
	 *
	 * @return string The template name, relative to the admin views folder.
	 */
	public function get_classic_template(): string {
		return $this->classic_template;
	}

	/**
	 * Window_Kind constructor.
	 *
	 * Private: each factory builds its kind and sets every property by name.
	 *
	 * @since TBD
	 */
	private function __construct() {
	}
}
