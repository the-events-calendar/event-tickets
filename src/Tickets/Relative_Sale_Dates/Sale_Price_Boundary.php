<?php
/**
 * A boundary of a ticket's sale price window, its start or its end, as a sale price rule describes it.
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
 * An immutable boundary of the sale price window: a mode and, for a relative boundary, how far before the event start
 * it falls.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Price_Boundary implements JsonSerializable {
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
	public const MAX_VALUE = 30;

	/**
	 * The units a relative boundary accepts: the sale price dates are whole days.
	 *
	 * @since TBD
	 *
	 * @var int[]
	 */
	private const UNITS = [ DAY_IN_SECONDS, WEEK_IN_SECONDS ];

	/**
	 * The mode, `Sale_Price_Rule::MODE_NOW`, `Rule::MODE_RELATIVE` or `Rule::MODE_SPECIFIC`.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $mode;

	/**
	 * The number of units before the event start, for a relative boundary.
	 *
	 * @since TBD
	 *
	 * @var int|null
	 */
	private ?int $value;

	/**
	 * The unit, `DAY_IN_SECONDS` or `WEEK_IN_SECONDS`, for a relative boundary.
	 *
	 * @since TBD
	 *
	 * @var int|null
	 */
	private ?int $unit;

	/**
	 * Builds a boundary from its array form.
	 *
	 * Keys the mode does not use are ignored. A relative boundary is always counted from the event start, so one that
	 * names an anchor is rejected rather than read as something it cannot be.
	 *
	 * @since TBD
	 *
	 * @param array{mode?: mixed, value?: mixed, unit?: mixed, anchor?: mixed} $data The boundary, not yet validated.
	 * @param string                                                           $key  The boundary's key in the rule, `start` or `end`, used in error messages.
	 *
	 * @return self The boundary.
	 *
	 * @throws InvalidArgumentException If the boundary is not valid.
	 */
	public static function from_array( array $data, string $key ): self {
		$mode = $data['mode'] ?? null;

		if ( Sale_Price_Rule::MODE_NOW === $mode || Rule::MODE_SPECIFIC === $mode ) {
			return new self( $mode );
		}

		if ( Rule::MODE_RELATIVE !== $mode ) {
			throw new InvalidArgumentException( "The sale price {$key} has an unknown mode." );
		}

		if ( array_key_exists( 'anchor', $data ) ) {
			throw new InvalidArgumentException( "The sale price {$key} is always counted from the event start and takes no anchor." );
		}

		$value = $data['value'] ?? null;
		if ( ! is_int( $value ) || $value < self::MIN_VALUE || $value > self::MAX_VALUE ) {
			throw new InvalidArgumentException(
				sprintf( 'The sale price %s value must be an integer from %d to %d.', $key, self::MIN_VALUE, self::MAX_VALUE )
			);
		}

		$unit = $data['unit'] ?? null;
		if ( ! in_array( $unit, self::UNITS, true ) ) {
			throw new InvalidArgumentException( "The sale price {$key} unit must be days or weeks." );
		}

		return new self( $mode, $value, $unit );
	}

	/**
	 * Returns the data to encode as the boundary's canonical JSON form.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int} The boundary, in canonical form.
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Returns the boundary's canonical array form, which holds only the keys its mode uses.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int} The boundary, in canonical form.
	 */
	public function to_array(): array {
		if ( Rule::MODE_RELATIVE !== $this->mode ) {
			return [ 'mode' => $this->mode ];
		}

		return [
			'mode'  => $this->mode,
			'value' => $this->value,
			'unit'  => $this->unit,
		];
	}

	/**
	 * Gets the mode.
	 *
	 * @since TBD
	 *
	 * @return string The mode, `Sale_Price_Rule::MODE_NOW`, `Rule::MODE_RELATIVE` or `Rule::MODE_SPECIFIC`.
	 */
	public function get_mode(): string {
		return $this->mode;
	}

	/**
	 * Gets a relative boundary as a sales window boundary on the event start, for the sales window resolver.
	 *
	 * @since TBD
	 *
	 * @return Boundary|null The boundary, or `null` when this one is not relative.
	 */
	public function to_boundary(): ?Boundary {
		if ( Rule::MODE_RELATIVE !== $this->mode ) {
			return null;
		}

		// The sale price limits sit inside the sales window ones, so a valid sale price boundary always builds one.
		return Boundary::from_array( array_merge( $this->to_array(), [ 'anchor' => Rule::ANCHOR_START ] ), Sale_Price_Rule::KEY );
	}

	/**
	 * Sale_Price_Boundary constructor.
	 *
	 * @since TBD
	 *
	 * @param string   $mode  The mode, `Sale_Price_Rule::MODE_NOW`, `Rule::MODE_RELATIVE` or `Rule::MODE_SPECIFIC`.
	 * @param int|null $value The number of units before the event start, for a relative boundary.
	 * @param int|null $unit  The unit, `DAY_IN_SECONDS` or `WEEK_IN_SECONDS`, for a relative boundary.
	 */
	private function __construct( string $mode, ?int $value = null, ?int $unit = null ) {
		$this->mode  = $mode;
		$this->value = $value;
		$this->unit  = $unit;
	}
}
