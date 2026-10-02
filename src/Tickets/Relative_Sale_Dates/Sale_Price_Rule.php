<?php
/**
 * A ticket's sale price window expressed relative to its event.
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
 * An immutable sale price window rule: a boundary for each end of the window, `start` and `end`.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Price_Rule implements JsonSerializable {
	/**
	 * The key the rule is stored under, next to the sales window rule's `start` and `end`.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const KEY = 'sale_price';

	/**
	 * The sale price applies as soon as the ticket's sales window opens. Only the start of the window takes it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MODE_NOW = 'now';

	/**
	 * The start of the sale price window.
	 *
	 * @since TBD
	 *
	 * @var Sale_Price_Boundary
	 */
	private Sale_Price_Boundary $start;

	/**
	 * The end of the sale price window.
	 *
	 * @since TBD
	 *
	 * @var Sale_Price_Boundary
	 */
	private Sale_Price_Boundary $end;

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
				throw new InvalidArgumentException( "The sale price {$key} is missing or is not an object." );
			}
		}

		$end = Sale_Price_Boundary::from_array( $data['end'] );

		if ( self::MODE_NOW === $end->get_mode() ) {
			throw new InvalidArgumentException( 'The sale price end must be relative or specific.' );
		}

		return new self( Sale_Price_Boundary::from_array( $data['start'] ), $end );
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
			throw new InvalidArgumentException( 'The sale price rule is not a JSON object.' );
		}

		return self::from_array( $data );
	}

	/**
	 * Builds a rule from what is stored for a ticket, where it sits under its own key next to the sales window rule.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $stored The stored rules, keyed by their top-level key.
	 *
	 * @return self|null The rule, or `null` when there is no valid sale price rule in the stored data.
	 */
	public static function from_stored( array $stored ): ?self {
		if ( ! isset( $stored[ self::KEY ] ) || ! is_array( $stored[ self::KEY ] ) ) {
			return null;
		}

		try {
			return self::from_array( $stored[ self::KEY ] );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Gets the start of the sale price window.
	 *
	 * @since TBD
	 *
	 * @return Sale_Price_Boundary The start.
	 */
	public function get_start(): Sale_Price_Boundary {
		return $this->start;
	}

	/**
	 * Gets the end of the sale price window.
	 *
	 * @since TBD
	 *
	 * @return Sale_Price_Boundary The end.
	 */
	public function get_end(): Sale_Price_Boundary {
		return $this->end;
	}

	/**
	 * Returns the rule's canonical array form, for storage and for the editors.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}} The rule.
	 */
	public function to_array(): array {
		return [
			'start' => $this->start->to_array(),
			'end'   => $this->end->to_array(),
		];
	}

	/**
	 * Returns the data to encode as the rule's canonical JSON form.
	 *
	 * @since TBD
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}} The rule, in canonical form.
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Sale_Price_Rule constructor.
	 *
	 * @since TBD
	 *
	 * @param Sale_Price_Boundary $start The start of the sale price window.
	 * @param Sale_Price_Boundary $end   The end of the sale price window.
	 */
	private function __construct( Sale_Price_Boundary $start, Sale_Price_Boundary $end ) {
		$this->start = $start;
		$this->end   = $end;
	}
}
