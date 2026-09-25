<?php
/**
 * A ticket's sales window expressed relative to its event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use InvalidArgumentException;

/**
 * An immutable sales window rule: a mode for each end of the window, `start` and `end`.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Rule {
	/**
	 * Sales open at once (start) or close when the event starts (end).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MODE_DEFAULT = 'default';

	/**
	 * The end is a number of units before an anchor on the event.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MODE_RELATIVE = 'relative';

	/**
	 * The admin picked a date; the rule does not hold it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MODE_SPECIFIC = 'specific';

	/**
	 * A minute, in seconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const UNIT_MINUTES = 60;

	/**
	 * An hour, in seconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const UNIT_HOURS = 3600;

	/**
	 * A day, in seconds. Identifies the unit only: days are resolved as calendar days.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const UNIT_DAYS = 86400;

	/**
	 * A week, in seconds. Identifies the unit only: weeks are resolved as calendar days.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const UNIT_WEEKS = 604800;

	/**
	 * Measured from the event start.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ANCHOR_START = 'start';

	/**
	 * Measured from the event end.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ANCHOR_END = 'end';

	/**
	 * The lowest number of units a relative end accepts.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const MIN_VALUE = 1;

	/**
	 * The highest number of units a relative end accepts.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const MAX_VALUE = 60;

	/**
	 * The start of the sales window, in canonical form.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value?: int, unit?: int, anchor?: string}
	 */
	private array $start;

	/**
	 * The end of the sales window, in canonical form.
	 *
	 * @since TBD
	 *
	 * @var array{mode: string, value?: int, unit?: int, anchor?: string}
	 */
	private array $end;

	/**
	 * Builds a rule from its array form.
	 *
	 * Top-level keys other than `start` and `end` are ignored, and so are the keys an end's mode does not use.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The rule, in the shape `['start' => [...], 'end' => [...]]`.
	 *
	 * @return self The rule.
	 *
	 * @throws InvalidArgumentException If the rule is not valid.
	 */
	public static function from_array( array $data ): self {
		foreach ( [ 'start', 'end' ] as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				throw new InvalidArgumentException( "The rule's {$key} is missing or is not an object." );
			}
		}

		return new self( self::read_end( $data['start'], 'start' ), self::read_end( $data['end'], 'end' ) );
	}

	/**
	 * Builds a rule from its JSON form.
	 *
	 * @since TBD
	 *
	 * @param string $json The rule, as JSON.
	 *
	 * @return self The rule.
	 *
	 * @throws InvalidArgumentException If the JSON cannot be decoded or the rule is not valid.
	 */
	public static function from_json( string $json ): self {
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			throw new InvalidArgumentException( 'The rule is not a JSON object.' );
		}

		return self::from_array( $data );
	}

	/**
	 * Builds a rule from what is stored for a ticket, which other top-level keys may share.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $stored The stored rules, keyed by their top-level key.
	 *
	 * @return self|null The rule, or `null` when there is no valid rule in the stored data.
	 */
	public static function from_stored( array $stored ): ?self {
		try {
			return self::from_array( $stored );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Gets the start of the sales window.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int, anchor?: string} The start, in canonical form.
	 */
	public function get_start(): array {
		return $this->start;
	}

	/**
	 * Gets the end of the sales window.
	 *
	 * @since TBD
	 *
	 * @return array{mode: string, value?: int, unit?: int, anchor?: string} The end, in canonical form.
	 */
	public function get_end(): array {
		return $this->end;
	}

	/**
	 * Returns the rule's canonical array form.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}} The rule.
	 */
	public function to_array(): array {
		return [
			'start' => $this->start,
			'end'   => $this->end,
		];
	}

	/**
	 * Returns the rule's canonical JSON form.
	 *
	 * @since TBD
	 *
	 * @return string The rule, as JSON.
	 */
	public function to_json(): string {
		$json = wp_json_encode( $this->to_array() );

		// Only strings and integers are ever encoded, so encoding cannot fail.
		return is_string( $json ) ? $json : '';
	}

	/**
	 * Rule constructor.
	 *
	 * @since TBD
	 *
	 * @param array{mode: string, value?: int, unit?: int, anchor?: string} $start The start of the sales window, in canonical form.
	 * @param array{mode: string, value?: int, unit?: int, anchor?: string} $end   The end of the sales window, in canonical form.
	 */
	private function __construct( array $start, array $end ) {
		$this->start = $start;
		$this->end   = $end;
	}

	/**
	 * Validates one end of the window and returns it in canonical form.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $end The end to read.
	 * @param string              $key The end's key, `start` or `end`, used in error messages.
	 *
	 * @return array{mode: string, value?: int, unit?: int, anchor?: string} The end, in canonical form.
	 *
	 * @throws InvalidArgumentException If the end is not valid.
	 */
	private static function read_end( array $end, string $key ): array {
		$mode = $end['mode'] ?? null;

		if ( self::MODE_DEFAULT === $mode || self::MODE_SPECIFIC === $mode ) {
			return [ 'mode' => $mode ];
		}

		if ( self::MODE_RELATIVE !== $mode ) {
			throw new InvalidArgumentException( "The rule's {$key} has an unknown mode." );
		}

		$value = $end['value'] ?? null;
		if ( ! is_int( $value ) || $value < self::MIN_VALUE || $value > self::MAX_VALUE ) {
			throw new InvalidArgumentException(
				sprintf( "The rule's %s value must be an integer from %d to %d.", $key, self::MIN_VALUE, self::MAX_VALUE )
			);
		}

		$unit = $end['unit'] ?? null;
		if ( ! in_array( $unit, [ self::UNIT_MINUTES, self::UNIT_HOURS, self::UNIT_DAYS, self::UNIT_WEEKS ], true ) ) {
			throw new InvalidArgumentException( "The rule's {$key} has an unknown unit." );
		}

		$anchor = $end['anchor'] ?? null;
		if ( ! in_array( $anchor, [ self::ANCHOR_START, self::ANCHOR_END ], true ) ) {
			throw new InvalidArgumentException( "The rule's {$key} has an unknown anchor." );
		}

		return [
			'mode'   => $mode,
			'value'  => $value,
			'unit'   => $unit,
			'anchor' => $anchor,
		];
	}
}
