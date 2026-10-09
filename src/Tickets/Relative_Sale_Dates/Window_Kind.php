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

use TEC\Tickets\Commerce\Ticket;

/**
 * An immutable kind of window: the modes, values, units and anchors its rule accepts, where the rule and the dates it
 * resolves to are stored, and how the window relates to the ticket's own sales.
 *
 * The rules, boundaries, resolver, writer and save work the same for every kind; only these differ.
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
	 * The ticket data key that carries the rule.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $data_key;

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
	 * The ticket meta that turns the window on, or `null` for a window every ticket has.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	private ?string $enabled_meta_key;

	/**
	 * Whether the window's dates are the ticket's own sales dates.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $owns_ticket_sales_dates;

	/**
	 * Whether a front-end ticket form that sends no rule removes the stored one.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $is_removed_by_front_end_form;

	/**
	 * Gets the sales window kind.
	 *
	 * @since TBD
	 *
	 * @return self The sales window kind.
	 */
	public static function sales(): self {
		if ( ! isset( self::$instances[ self::SALES ] ) ) {
			$modes = [ Rule::MODE_DEFAULT, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ];

			self::$instances[ self::SALES ] = new self(
				self::SALES,
				[
					'start' => $modes,
					'end'   => $modes,
				],
				Rule::MODE_DEFAULT,
				Boundary::MAX_VALUE,
				[ MINUTE_IN_SECONDS, HOUR_IN_SECONDS, DAY_IN_SECONDS, WEEK_IN_SECONDS ],
				[ Rule::ANCHOR_START, Rule::ANCHOR_END ],
				null,
				null,
				Ticket_Save::DATA_KEY,
				[
					'start' => [
						'date' => Ticket::START_DATE_META_KEY,
						'time' => Ticket::START_TIME_META_KEY,
					],
					'end'   => [
						'date' => Ticket::END_DATE_META_KEY,
						'time' => Ticket::END_TIME_META_KEY,
					],
				],
				null,
				null,
				true,
				true
			);
		}

		return self::$instances[ self::SALES ];
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
		if ( ! isset( self::$instances[ self::SALE_PRICE ] ) ) {
			self::$instances[ self::SALE_PRICE ] = new self(
				self::SALE_PRICE,
				[
					'start' => [ Rule::MODE_NOW, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ],
					'end'   => [ Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ],
				],
				Rule::MODE_NOW,
				30,
				[ DAY_IN_SECONDS, WEEK_IN_SECONDS ],
				[ Rule::ANCHOR_START ],
				'sale_price',
				self::sales(),
				'ticket_sale_price_relative',
				[
					'start' => [
						'date' => Ticket::$sale_price_start_date_key,
						'time' => null,
					],
					'end'   => [
						'date' => Ticket::$sale_price_end_date_key,
						'time' => null,
					],
				],
				'',
				Ticket::$sale_price_checked_key,
				false,
				false
			);
		}

		return self::$instances[ self::SALE_PRICE ];
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
	 * - lets its end follow the event start when the event moves and the rule leaves the end to the ticket.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the window's dates are the ticket's sales dates.
	 */
	public function owns_ticket_sales_dates(): bool {
		return $this->owns_ticket_sales_dates;
	}

	/**
	 * Gets the ticket data key that carries the rule.
	 *
	 * @since TBD
	 *
	 * @return string The key; its value is a JSON string, an array, or `null` or `''` to remove the rule.
	 */
	public function get_data_key(): string {
		return $this->data_key;
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
	 * @return string|null The value, or `null` to leave the ticket's own start, which `moves_open_start_to_now()` may
	 *                     move to now.
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
		return null === $this->enabled_meta_key || tribe_is_truthy( get_post_meta( $ticket_id, $this->enabled_meta_key, true ) );
	}

	/**
	 * Returns whether an open start moves a ticket start later than now to now.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the open start puts the ticket on sale at once.
	 */
	public function moves_open_start_to_now(): bool {
		return $this->owns_ticket_sales_dates;
	}

	/**
	 * Returns whether the window's end is the ticket's sale end, which the ticket moves to the event start when the
	 * event moves and the rule leaves the end to the ticket.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the window's end may follow the event start.
	 */
	public function lets_end_follow_event_start(): bool {
		return $this->owns_ticket_sales_dates;
	}

	/**
	 * Returns whether the window's dates are announced by the "sales started" and "sales ended" actions.
	 *
	 * @since TBD
	 *
	 * @return bool Whether a change to the window's dates reschedules the sales actions.
	 */
	public function has_sales_actions(): bool {
		return $this->owns_ticket_sales_dates;
	}

	/**
	 * Returns whether a front-end ticket form, such as Community Events', removes the stored rule by sending none.
	 *
	 * Such a form offers no options for the window, so the dates it sends are the ones the person set.
	 *
	 * @since TBD
	 *
	 * @return bool Whether a front-end save without the rule removes it.
	 */
	public function is_removed_by_front_end_form(): bool {
		return $this->is_removed_by_front_end_form;
	}

	/**
	 * Window_Kind constructor.
	 *
	 * @since TBD
	 *
	 * @param string                                           $id                           The kind's ID.
	 * @param array{start: string[], end: string[]}            $modes                        The modes each end of the window accepts.
	 * @param string                                           $open_start_mode              The start mode that opens the window at once.
	 * @param int                                              $max_value                    The highest number of units a relative boundary accepts.
	 * @param int[]                                            $units                        The units a relative boundary accepts.
	 * @param string[]                                         $anchors                      The event dates a relative boundary may be counted from.
	 * @param string|null                                      $store_key                    The key the rule is stored under, or `null` for the top level.
	 * @param self|null                                        $parent_kind                  The kind this one is judged against, or `null`.
	 * @param string                                           $data_key                     The ticket data key that carries the rule.
	 * @param array<string,array{date: string, time: ?string}> $date_metas                   The ticket metas each end, `start` and `end`, is written to.
	 * @param string|null                                      $open_start_value             The value an open start is written as, or `null`.
	 * @param string|null                                      $enabled_meta_key             The ticket meta that turns the window on, or `null`.
	 * @param bool                                             $owns_ticket_sales_dates      Whether the window's dates are the ticket's own sales dates.
	 * @param bool                                             $is_removed_by_front_end_form Whether a front-end form that sends no rule removes it.
	 */
	private function __construct(
		string $id,
		array $modes,
		string $open_start_mode,
		int $max_value,
		array $units,
		array $anchors,
		?string $store_key,
		?self $parent_kind,
		string $data_key,
		array $date_metas,
		?string $open_start_value,
		?string $enabled_meta_key,
		bool $owns_ticket_sales_dates,
		bool $is_removed_by_front_end_form
	) {
		$this->id                           = $id;
		$this->modes                        = $modes;
		$this->open_start_mode              = $open_start_mode;
		$this->max_value                    = $max_value;
		$this->units                        = $units;
		$this->anchors                      = $anchors;
		$this->store_key                    = $store_key;
		$this->parent                       = $parent_kind;
		$this->data_key                     = $data_key;
		$this->date_metas                   = $date_metas;
		$this->open_start_value             = $open_start_value;
		$this->enabled_meta_key             = $enabled_meta_key;
		$this->owns_ticket_sales_dates      = $owns_ticket_sales_dates;
		$this->is_removed_by_front_end_form = $is_removed_by_front_end_form;
	}
}
