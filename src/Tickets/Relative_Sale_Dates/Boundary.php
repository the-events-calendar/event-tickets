<?php
/**
 * A boundary of a ticket's sales window, its start or its end, as a rule describes it.
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
 * An immutable boundary of the sales window: a mode and, for a relative boundary, how far before which event date it falls.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Boundary implements JsonSerializable {
	/**
	 * The lowest number of units a relative boundary accepts.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const MIN_VALUE = 1;

	/**
	 * The highest number of units a relative boundary accepts.
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
	 * Keys the mode does not use are ignored.
	 *
	 * @since TBD
	 *
	 * @param array{mode?: mixed, value?: mixed, unit?: mixed, anchor?: mixed} $data The boundary, not yet validated.
	 *
	 * @return self The boundary.
	 *
	 * @throws InvalidArgumentException If the boundary is not valid.
	 */
	public static function from_array( array $data ): self {
		$mode = $data['mode'] ?? null;

		if ( Rule::MODE_DEFAULT === $mode || Rule::MODE_SPECIFIC === $mode ) {
			return new self( $mode );
		}

		if ( Rule::MODE_RELATIVE !== $mode ) {
			throw new InvalidArgumentException( 'The boundary has an unknown mode.' );
		}

		$value = $data['value'] ?? null;
		if ( ! is_int( $value ) || $value < self::MIN_VALUE || $value > self::MAX_VALUE ) {
			throw new InvalidArgumentException(
				sprintf( 'The boundary value must be an integer from %d to %d.', self::MIN_VALUE, self::MAX_VALUE )
			);
		}

		$unit = $data['unit'] ?? null;
		if ( ! is_int( $unit ) || ! isset( self::INTERVAL_FORMATS[ $unit ] ) ) {
			throw new InvalidArgumentException( 'The boundary has an unknown unit.' );
		}

		$anchor = $data['anchor'] ?? null;
		if ( ! in_array( $anchor, [ Rule::ANCHOR_START, Rule::ANCHOR_END ], true ) ) {
			throw new InvalidArgumentException( 'The boundary has an unknown anchor.' );
		}

		return new self( $mode, $value, $unit, $anchor );
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
	 * Returns the boundary's canonical array form, which holds only the keys its mode uses.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int, anchor?: string} The boundary, in canonical form.
	 */
	public function to_array(): array {
		if ( Rule::MODE_RELATIVE !== $this->mode ) {
			return [ 'mode' => $this->mode ];
		}

		return [
			'mode'   => $this->mode,
			'value'  => $this->value,
			'unit'   => $this->unit,
			'anchor' => $this->anchor,
		];
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
	 * Gets the number of units a relative boundary falls before its anchor.
	 *
	 * @since TBD
	 *
	 * @return int|null The value, or `null` when the boundary is not relative.
	 */
	public function get_value(): ?int {
		return $this->value;
	}

	/**
	 * Gets the unit of a relative boundary.
	 *
	 * @since TBD
	 *
	 * @return int|null The unit, one of the `*_IN_SECONDS` constants from `MINUTE_IN_SECONDS` to `WEEK_IN_SECONDS`, or `null` when the boundary is not relative.
	 */
	public function get_unit(): ?int {
		return $this->unit;
	}

	/**
	 * Gets the event date a relative boundary is counted from.
	 *
	 * @since TBD
	 *
	 * @return string|null The anchor, one of the `Rule::ANCHOR_*` constants, or `null` when the boundary is not relative.
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
	 * @param string      $mode   The mode, one of the `Rule::MODE_*` constants.
	 * @param int|null    $value  The number of units before the anchor, for a relative boundary.
	 * @param int|null    $unit   The unit, one of the `*_IN_SECONDS` constants from `MINUTE_IN_SECONDS` to `WEEK_IN_SECONDS`, for a relative boundary.
	 * @param string|null $anchor The event date the boundary is counted from, for a relative boundary.
	 */
	private function __construct( string $mode, ?int $value = null, ?int $unit = null, ?string $anchor = null ) {
		$this->mode   = $mode;
		$this->value  = $value;
		$this->unit   = $unit;
		$this->anchor = $anchor;
	}
}
