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
	private const MIN_VALUE = 1;

	/**
	 * The highest number of units a relative boundary accepts.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const MAX_VALUE = 60;

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
		if ( ! in_array( $unit, [ MINUTE_IN_SECONDS, HOUR_IN_SECONDS, DAY_IN_SECONDS, WEEK_IN_SECONDS ], true ) ) {
			throw new InvalidArgumentException( 'The boundary has an unknown unit.' );
		}

		$anchor = $data['anchor'] ?? null;
		if ( ! in_array( $anchor, [ Rule::ANCHOR_START, Rule::ANCHOR_END ], true ) ) {
			throw new InvalidArgumentException( 'The boundary has an unknown anchor.' );
		}

		return new self( $mode, $value, $unit, $anchor );
	}

	/**
	 * Returns the data to encode as the boundary's canonical JSON form, which holds only the keys its mode uses.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int, anchor?: string} The boundary, in canonical form.
	 */
	public function jsonSerialize(): array {
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
