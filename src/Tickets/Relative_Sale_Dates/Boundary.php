<?php
/**
 * A boundary of a ticket's sales or sale price window, its start or its end, as a rule describes it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateInterval;
use InvalidArgumentException;
use JsonSerializable;

/**
 * An immutable boundary of a window: a mode and, for a relative boundary, how far before which event date it falls.
 *
 * The window kind sets the modes, values, units and anchors the boundary accepts.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Boundary implements JsonSerializable {
	/**
	 * The lowest number of units a relative boundary of any window accepts.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const MIN_VALUE = 1;

	/**
	 * The highest number of units a relative boundary of the sales window accepts.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const MAX_VALUE = 60;

	/**
	 * The `DateInterval` format of each unit a relative boundary accepts, keyed by the unit's length in seconds.
	 *
	 * @since TBD
	 *
	 * @var array<int,string>
	 */
	private const INTERVAL_FORMATS = [
		MINUTE_IN_SECONDS => 'PT%dM',
		HOUR_IN_SECONDS   => 'PT%dH',
		DAY_IN_SECONDS    => 'P%dD',
		WEEK_IN_SECONDS   => 'P%dW',
	];

	/**
	 * The kind of window the boundary belongs to.
	 *
	 * @since TBD
	 *
	 * @var Window_Kind
	 */
	private Window_Kind $kind;

	/**
	 * The mode, one of the `Rule::MODE_*` constants.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $mode;

	/**
	 * The number of units before the anchor, for a relative boundary.
	 *
	 * @since TBD
	 *
	 * @var int|null
	 */
	private ?int $value;

	/**
	 * The unit as its length in seconds, one of the `*_IN_SECONDS` constants from `MINUTE_IN_SECONDS` to `WEEK_IN_SECONDS`, for a relative boundary.
	 *
	 * It only identifies the unit: days and weeks are resolved as calendar days, never as a number of seconds.
	 *
	 * @since TBD
	 *
	 * @var int|null
	 */
	private ?int $unit;

	/**
	 * The event date the boundary is counted from, one of the `Rule::ANCHOR_*` constants, for a relative boundary.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	private ?string $anchor;

	/**
	 * Builds a boundary from its array form.
	 *
	 * Keys the mode does not use are ignored. A kind that takes no anchor implies it, so a relative boundary of that
	 * kind that names one is rejected rather than read as something it cannot be.
	 *
	 * @since TBD
	 *
	 * @param array{mode?: mixed, value?: mixed, unit?: mixed, anchor?: mixed} $data The boundary, not yet validated.
	 * @param Window_Kind|null                                                 $kind The kind of window, or `null` for the sales window.
	 * @param string                                                           $end  The end of the window the boundary is, `start` or `end`.
	 *
	 * @return self The boundary.
	 *
	 * @throws InvalidArgumentException If the boundary is not valid.
	 */
	public static function from_array( array $data, ?Window_Kind $kind = null, string $end = 'start' ): self {
		$kind ??= Window_Kind::sales();

		$mode  = $data['mode'] ?? null;
		$modes = $kind->get_modes( $end );

		if ( ! in_array( $mode, $modes, true ) ) {
			throw new InvalidArgumentException(
				sprintf( "The window's %s mode must be one of: %s.", $end, implode( ', ', $modes ) )
			);
		}

		if ( Rule::MODE_RELATIVE !== $mode ) {
			return new self( $kind, $mode );
		}

		$value = $data['value'] ?? null;
		if ( ! is_int( $value ) || $value < $kind->get_min_value() || $value > $kind->get_max_value() ) {
			throw new InvalidArgumentException(
				sprintf( 'The boundary value must be an integer from %d to %d.', $kind->get_min_value(), $kind->get_max_value() )
			);
		}

		$unit = $data['unit'] ?? null;
		if ( ! in_array( $unit, $kind->get_units(), true ) ) {
			throw new InvalidArgumentException( 'The boundary has an unknown unit.' );
		}

		if ( ! $kind->takes_anchor() ) {
			if ( array_key_exists( 'anchor', $data ) ) {
				throw new InvalidArgumentException( 'The boundary is always counted from the same event date and takes no anchor.' );
			}

			return new self( $kind, $mode, $value, $unit, $kind->get_anchors()[0] );
		}

		$anchor = $data['anchor'] ?? null;
		if ( ! in_array( $anchor, $kind->get_anchors(), true ) ) {
			throw new InvalidArgumentException( 'The boundary has an unknown anchor.' );
		}

		return new self( $kind, $mode, $value, $unit, $anchor );
	}

	/**
	 * Returns the data to encode as the boundary's canonical JSON form.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int, anchor?: string} The boundary, in canonical form.
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Returns the boundary's canonical array form, which holds only the keys its mode and its kind use.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int, anchor?: string} The boundary, in canonical form.
	 */
	public function to_array(): array {
		if ( Rule::MODE_RELATIVE !== $this->mode ) {
			return [ 'mode' => $this->mode ];
		}

		$data = [
			'mode'  => $this->mode,
			'value' => $this->value,
			'unit'  => $this->unit,
		];

		if ( $this->kind->takes_anchor() ) {
			$data['anchor'] = $this->anchor;
		}

		return $data;
	}

	/**
	 * Gets the kind of window the boundary belongs to.
	 *
	 * @since TBD
	 *
	 * @return Window_Kind The kind.
	 */
	public function get_kind(): Window_Kind {
		return $this->kind;
	}

	/**
	 * Gets the mode.
	 *
	 * @since TBD
	 *
	 * @return string The mode, one of the `Rule::MODE_*` constants.
	 */
	public function get_mode(): string {
		return $this->mode;
	}

	/**
	 * Gets the event date a relative boundary is counted from.
	 *
	 * @since TBD
	 *
	 * @return string|null The anchor, one of the `Rule::ANCHOR_*` constants, or `null` when the boundary is not relative. A kind that takes no anchor gives the one it implies.
	 */
	public function get_anchor(): ?string {
		return $this->anchor;
	}

	/**
	 * Gets how far before its anchor a relative boundary falls.
	 *
	 * @since TBD
	 *
	 * @return DateInterval|null The interval, or `null` when the boundary is not relative.
	 */
	public function get_interval(): ?DateInterval {
		if ( null === $this->value || null === $this->unit ) {
			return null;
		}

		return new DateInterval( sprintf( self::INTERVAL_FORMATS[ $this->unit ], $this->value ) );
	}

	/**
	 * Boundary constructor.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind $kind   The kind of window the boundary belongs to.
	 * @param string      $mode   The mode, one of the `Rule::MODE_*` constants.
	 * @param int|null    $value  The number of units before the anchor, for a relative boundary.
	 * @param int|null    $unit   The unit, one of the `*_IN_SECONDS` constants from `MINUTE_IN_SECONDS` to `WEEK_IN_SECONDS`, for a relative boundary.
	 * @param string|null $anchor The event date the boundary is counted from, for a relative boundary.
	 */
	private function __construct( Window_Kind $kind, string $mode, ?int $value = null, ?int $unit = null, ?string $anchor = null ) {
		$this->kind   = $kind;
		$this->mode   = $mode;
		$this->value  = $value;
		$this->unit   = $unit;
		$this->anchor = $anchor;
	}
}
