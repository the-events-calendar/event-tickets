<?php
/**
 * A ticket's sales or sale price window expressed relative to its event.
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
 * An immutable window rule: a boundary for each end of the window, `start` and `end`, of one kind of window.
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
	 * At the start of the sale price window, the sale price applies as soon as the ticket's sales window opens.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MODE_NOW = 'now';

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
	 * The kind of window the rule describes.
	 *
	 * @since TBD
	 *
	 * @var Window_Kind
	 */
	private Window_Kind $kind;

	/**
	 * The start of the window.
	 *
	 * @since TBD
	 *
	 * @var Boundary
	 */
	private Boundary $start;

	/**
	 * The end of the window.
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
	 * @param Window_Kind                       $kind The kind of window.
	 *
	 * @return self The rule.
	 *
	 * @throws InvalidArgumentException If the rule is not valid.
	 */
	public static function from_array( array $data, Window_Kind $kind ): self {
		foreach ( [ 'start', 'end' ] as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				throw new InvalidArgumentException( "The rule's {$key} is missing or is not an object." );
			}
		}

		return new self(
			$kind,
			Boundary::from_array( $data['start'], $kind, Boundary::EDGE_START ),
			Boundary::from_array( $data['end'], $kind, Boundary::EDGE_END )
		);
	}

	/**
	 * Builds a rule from the value a request sends for it, its array form or its JSON form.
	 *
	 * An invalid rule is not a request to remove the valid one already stored, so it builds nothing.
	 *
	 * @since TBD
	 *
	 * @param mixed       $raw  The rule as sent, a JSON string or an array.
	 * @param Window_Kind $kind The kind of window.
	 *
	 * @return self|null The rule, or `null` when nothing valid was sent.
	 */
	public static function from_raw( $raw, Window_Kind $kind ): ?self {
		try {
			if ( is_array( $raw ) ) {
				return self::from_array( $raw, $kind );
			}

			if ( is_string( $raw ) ) {
				return self::from_json( $raw, $kind );
			}
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}

	/**
	 * Builds a rule from what is stored for a ticket, where the rules of every kind share one array.
	 *
	 * The sales window rule is its top level; another kind's rule sits under the kind's store key.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $stored The stored rules, keyed by their top-level key.
	 * @param Window_Kind         $kind   The kind of window.
	 *
	 * @return self|null The rule, or `null` when there is no valid rule of the kind in the stored data.
	 */
	public static function from_stored( array $stored, Window_Kind $kind ): ?self {
		$key = $kind->get_store_key();

		if ( null !== $key ) {
			if ( ! isset( $stored[ $key ] ) || ! is_array( $stored[ $key ] ) ) {
				return null;
			}

			$stored = $stored[ $key ];
		}

		try {
			return self::from_array( $stored, $kind );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Gets the kind of window the rule describes.
	 *
	 * @since TBD
	 *
	 * @return Window_Kind The kind.
	 */
	public function get_kind(): Window_Kind {
		return $this->kind;
	}

	/**
	 * Gets the start of the window.
	 *
	 * @since TBD
	 *
	 * @return Boundary The start.
	 */
	public function get_start(): Boundary {
		return $this->start;
	}

	/**
	 * Gets the end of the window.
	 *
	 * @since TBD
	 *
	 * @return Boundary The end.
	 */
	public function get_end(): Boundary {
		return $this->end;
	}

	/**
	 * Returns whether the window opens at once: its start is the kind's open start, which has no date of its own.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the start opens the window at once.
	 */
	public function opens_at_once(): bool {
		return $this->kind->get_open_start_mode() === $this->start->get_mode();
	}

	/**
	 * Returns whether the window ends when or before it starts whatever the event's dates, as for a ticket preset, which
	 * has no event.
	 *
	 * A boundary is a length of time before its anchor; the default end is the event start itself. With a start and an
	 * end before the same anchor, or a start before the event end and an end before the event start, the start falls
	 * on or after the end for every event as soon as its length is not the longer. A start before the event start and
	 * an end before the event end fit a long enough event. A default start, or a specific date, is only judged once
	 * the event's dates are known. Lengths compare in seconds, a day as 24 hours.
	 *
	 * @since TBD
	 *
	 * @return bool Whether no event's dates would make the window end after it starts.
	 */
	public function ends_before_start_for_every_event(): bool {
		$start = $this->get_length_before_anchor( $this->start );
		$end   = self::MODE_DEFAULT === $this->end->get_mode() ? [ self::ANCHOR_START, 0 ] : $this->get_length_before_anchor( $this->end );

		if ( ! $start || ! $end ) {
			return false;
		}

		if ( self::ANCHOR_START === $start[0] && self::ANCHOR_END === $end[0] ) {
			return false;
		}

		return $start[1] <= $end[1];
	}

	/**
	 * Returns the rule's canonical array form, for storage and for the editors.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}} The rule.
	 */
	public function to_array(): array {
		return [
			'start' => $this->start->to_array(),
			'end'   => $this->end->to_array(),
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
		$json = wp_json_encode( $this );

		// Only strings and integers are ever encoded, so encoding cannot fail.
		return is_string( $json ) ? $json : '';
	}

	/**
	 * Returns the data to encode as the rule's canonical JSON form.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}} The rule, in canonical form.
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Rule constructor.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind $kind  The kind of window the rule describes.
	 * @param Boundary    $start The start of the window.
	 * @param Boundary    $end   The end of the window.
	 */
	private function __construct( Window_Kind $kind, Boundary $start, Boundary $end ) {
		$this->kind  = $kind;
		$this->start = $start;
		$this->end   = $end;
	}

	/**
	 * Builds a rule from its JSON form.
	 *
	 * @since TBD
	 *
	 * @param string      $json The rule, as JSON.
	 * @param Window_Kind $kind The kind of window.
	 *
	 * @return self The rule.
	 *
	 * @throws InvalidArgumentException If the JSON cannot be decoded or the rule is not valid.
	 */
	private static function from_json( string $json, Window_Kind $kind ): self {
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			throw new InvalidArgumentException( 'The rule is not a JSON object.' );
		}

		return self::from_array( $data, $kind );
	}

	/**
	 * Gets how long before its anchor a relative boundary falls.
	 *
	 * @since TBD
	 *
	 * @param Boundary $boundary The boundary.
	 *
	 * @return array{0: string, 1: int}|null The anchor and the length in seconds, or `null` when the boundary is not
	 *                                       relative.
	 */
	private function get_length_before_anchor( Boundary $boundary ): ?array {
		$data = $boundary->to_array();

		if ( self::MODE_RELATIVE !== $data['mode'] ) {
			return null;
		}

		return [ $boundary->get_anchor(), $data['value'] * $data['unit'] ];
	}
}
