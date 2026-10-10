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

/**
 * An immutable kind of window: the modes, values, units and anchors its rule accepts, and where the rule is stored.
 *
 * The rules, boundaries and resolver work the same for every kind; only these limits and the storage differ.
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
	 * Whether the window's dates are the ticket's own sales dates.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $owns_ticket_sales_dates;

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
				true
			);
		}

		return self::$instances[ self::SALES ];
	}

	/**
	 * Gets the sale price window kind.
	 *
	 * A relative boundary is 1 to 30 days or weeks before the event start, which it always counts from.
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
	 * Window_Kind constructor.
	 *
	 * @since TBD
	 *
	 * @param string                                $id                      The kind's ID.
	 * @param array{start: string[], end: string[]} $modes                   The modes each end of the window accepts.
	 * @param string                                $open_start_mode         The start mode that opens the window at once.
	 * @param int                                   $max_value               The highest number of units a relative boundary accepts.
	 * @param int[]                                 $units                   The units a relative boundary accepts.
	 * @param string[]                              $anchors                 The event dates a relative boundary may be counted from.
	 * @param string|null                           $store_key               The key the rule is stored under, or `null` for the top level.
	 * @param self|null                             $parent_kind             The kind this one is judged against, or `null`.
	 * @param bool                                  $owns_ticket_sales_dates Whether the window's dates are the ticket's own sales dates.
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
		bool $owns_ticket_sales_dates
	) {
		$this->id                      = $id;
		$this->modes                   = $modes;
		$this->open_start_mode         = $open_start_mode;
		$this->max_value               = $max_value;
		$this->units                   = $units;
		$this->anchors                 = $anchors;
		$this->store_key               = $store_key;
		$this->parent                  = $parent_kind;
		$this->owns_ticket_sales_dates = $owns_ticket_sales_dates;
	}
}
