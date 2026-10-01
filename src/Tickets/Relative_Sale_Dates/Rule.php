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
use JsonSerializable;

/**
 * An immutable sales window rule: a boundary for each end of the window, `start` and `end`.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Rule implements JsonSerializable {
	/**
	 * At the start of the window, sales open at once; at its end, they close when the event starts.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MODE_DEFAULT = 'default';

	/**
	 * This end of the window is a number of units before an anchor on the event.
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
	public const UNIT_MINUTES = MINUTE_IN_SECONDS;

	/**
	 * An hour, in seconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const UNIT_HOURS = HOUR_IN_SECONDS;

	/**
	 * A day, in seconds. Identifies the unit only: days are resolved as calendar days.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const UNIT_DAYS = DAY_IN_SECONDS;

	/**
	 * A week, in seconds. Identifies the unit only: weeks are resolved as calendar days.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const UNIT_WEEKS = WEEK_IN_SECONDS;

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
	 * The start of the sales window.
	 *
	 * @since TBD
	 *
	 * @var Boundary
	 */
	private Boundary $start;

	/**
	 * The end of the sales window.
	 *
	 * @since TBD
	 *
	 * @var Boundary
	 */
	private Boundary $end;

	/**
	 * Builds a rule from its array form.
	 *
	 * Top-level keys other than `start` and `end` are ignored, and so are the keys a boundary's mode does not use.
	 *
	 * @since TBD
	 *
	 * @param array{start?: mixed, end?: mixed} $data The rule, in the shape `['start' => [...], 'end' => [...]]`.
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

		return new self( Boundary::from_array( $data['start'] ), Boundary::from_array( $data['end'] ) );
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
	 * Returns the rule's canonical JSON form.
	 *
	 * @since TBD
	 *
	 * @return string The rule, as JSON.
	 */
	public function to_json(): string {
		$json = wp_json_encode( $this );

		// Only strings and integers are ever encoded, so encoding cannot fail.
		return is_string( $json ) ? $json : '';
	}

	/**
	 * Returns the data to encode as the rule's canonical JSON form.
	 *
	 * @since TBD
	 *
	 * @return array{start: Boundary, end: Boundary} The rule's boundaries.
	 */
	public function jsonSerialize(): array {
		return [
			'start' => $this->start,
			'end'   => $this->end,
		];
	}

	/**
	 * Rule constructor.
	 *
	 * @since TBD
	 *
	 * @param Boundary $start The start of the sales window.
	 * @param Boundary $end   The end of the sales window.
	 */
	private function __construct( Boundary $start, Boundary $end ) {
		$this->start = $start;
		$this->end   = $end;
	}
}
