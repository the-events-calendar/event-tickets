<?php
/**
 * A relative sale dates rule as a TEC REST API parameter.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use Closure;
use TEC\Common\REST\TEC\V1\Abstracts\Parameter;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use TEC\Common\REST\TEC\V1\Parameter_Types\Entity;
use TEC\Common\REST\TEC\V1\Parameter_Types\Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;

/**
 * A nullable object parameter that keeps a rule as it was sent, for `Rule` to validate against its window kind.
 *
 * An `Entity` would suit the documentation, but request body collections register an entity's leaf properties as
 * separate arguments, and its sanitizer does not accept `null`.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Rule_Parameter extends Parameter {
	/**
	 * Builds the parameter of a window kind's rule, named after the kind's TEC REST API field.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind $kind The kind of the rule.
	 *
	 * @return self The parameter, such as `relative_sale_dates` or `sale_price_relative`.
	 */
	public static function for_kind( Window_Kind $kind ): self {
		return new self( $kind );
	}

	/**
	 * Rule_Parameter constructor.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind $kind The kind of the rule.
	 */
	private function __construct( Window_Kind $kind ) {
		$descriptions = self::get_descriptions()[ $kind->get_id() ];

		$this->name                 = $kind->get_tec_rest_field();
		$this->description_provider = $descriptions['rule'];
		$this->required             = false;
		$this->nullable             = true;
		$this->properties           = new PropertiesCollection();
		$this->properties[]         = $this->get_boundary_parameter( $kind, 'start', $descriptions );
		$this->properties[]         = $this->get_boundary_parameter( $kind, 'end', $descriptions );
	}

	/**
	 * Gets the parameter type.
	 *
	 * @since TBD
	 *
	 * @return string The parameter type.
	 */
	public function get_type(): string {
		return 'object';
	}

	/**
	 * Gets the parameter default.
	 *
	 * @since TBD
	 *
	 * @return null There is no default: a request without the rule leaves it out.
	 */
	public function get_default() {
		return null;
	}

	/**
	 * Gets the parameter example.
	 *
	 * The inner properties carry the examples. The rule is not an ORM field, and the shared TEC REST API endpoint tests
	 * round-trip every documented example through the ORM.
	 *
	 * @since TBD
	 *
	 * @return null There is no example.
	 */
	public function get_example() {
		return null;
	}

	/**
	 * Gets the validator, which only checks the value is an object or `null`: the ticket save validates the rule.
	 *
	 * @since TBD
	 *
	 * @return Closure The validator.
	 */
	public function get_validator(): Closure {
		return $this->validator ?? function ( $value ): bool {
			if ( null !== $value && ! is_array( $value ) ) {
				throw InvalidRestArgumentException::create(
					// translators: 1) is the name of the parameter.
					sprintf( __( 'The argument `{%1$s}` must be an object or null.', 'event-tickets' ), $this->get_name() ),
					$this->get_name(),
					'tec_rest_invalid_relative_sale_dates_argument',
					// translators: 1) is the name of the parameter.
					sprintf( __( 'The argument `{%1$s}` must be an object or null.', 'event-tickets' ), $this->get_name() )
				);
			}

			return true;
		};
	}

	/**
	 * Gets the sanitizer, which keeps the value as sent so the rule's integers stay integers.
	 *
	 * @since TBD
	 *
	 * @return Closure The sanitizer.
	 */
	public function get_sanitizer(): Closure {
		return $this->sanitizer ?? static fn( $value ) => $value;
	}

	/**
	 * Gets the documentation of one boundary of the window: its mode and, for a relative boundary, its value, its unit
	 * and, for a kind that takes one, its anchor.
	 *
	 * @since TBD
	 *
	 * @param Window_Kind           $kind         The kind of the rule.
	 * @param string                $name         The boundary, `start` or `end`.
	 * @param array<string,Closure> $descriptions The kind's descriptions, from `get_descriptions()`.
	 *
	 * @return Entity The boundary's documentation.
	 */
	private function get_boundary_parameter( Window_Kind $kind, string $name, array $descriptions ): Entity {
		$properties   = new PropertiesCollection();
		$properties[] = ( new Text( 'mode', $descriptions[ $name . '_mode' ], null, $kind->get_modes( $name ) ) )->set_example( Rule::MODE_RELATIVE );
		$properties[] = (
			new Integer(
				'value',
				static fn(): string => $descriptions['value']( Boundary::MIN_VALUE, $kind->get_max_value() ),
				null,
				Boundary::MIN_VALUE,
				$kind->get_max_value()
			)
		)->set_example( 2 );
		$properties[] = ( new Integer( 'unit', static fn(): string => $descriptions['unit']( ...$kind->get_units() ) ) )->set_example( WEEK_IN_SECONDS );

		if ( $kind->takes_anchor() ) {
			$properties[] = (
				new Text(
					'anchor',
					static fn(): string => __( 'For a relative boundary, the event date it is counted from.', 'event-tickets' ),
					null,
					$kind->get_anchors()
				)
			)->set_example( Rule::ANCHOR_START );
		}

		return new Entity( $name, $descriptions[ $name ], $properties, false );
	}

	/**
	 * Gets the descriptions of each kind's rule, keyed by the kind's ID.
	 *
	 * The value and unit descriptions take the kind's bounds and units, so each kind's copy lists what it accepts.
	 *
	 * @since TBD
	 *
	 * @return array<string,array{rule: Closure(): string, start: Closure(): string, end: Closure(): string, start_mode: Closure(): string, end_mode: Closure(): string, value: Closure(int, int): string, unit: Closure(int ...): string}> The descriptions.
	 */
	private static function get_descriptions(): array {
		$sales_mode = static fn(): string => __( 'How this end of the window is set: `default` (sales open at once, or close when the event starts), `relative` (before the event) or `specific` (the date sent with the ticket).', 'event-tickets' );

		return [
			Window_Kind::SALES      => [
				'rule'       => static fn(): string => __( 'The sales window relative to the event, or null when the ticket has fixed sale dates. Sending null removes the rule.', 'event-tickets' ),
				'start'      => static fn(): string => __( 'When sales start.', 'event-tickets' ),
				'end'        => static fn(): string => __( 'When sales end.', 'event-tickets' ),
				'start_mode' => $sales_mode,
				'end_mode'   => $sales_mode,
				'value'      => static fn( int $min, int $max ): string => sprintf(
					// translators: 1) the lowest number of units, 2) the highest number of units.
					__( 'For a relative boundary, the number of units before the anchor, from %1$d to %2$d.', 'event-tickets' ),
					$min,
					$max
				),
				'unit'       => static fn( int ...$units ): string => sprintf(
					// translators: 1) a minute, 2) an hour, 3) a day and 4) a week, each in seconds.
					__( 'For a relative boundary, the unit in seconds: %1$d (minutes), %2$d (hours), %3$d (days) or %4$d (weeks).', 'event-tickets' ),
					...$units
				),
			],
			Window_Kind::SALE_PRICE => [
				'rule'       => static fn(): string => __( 'The sale price window relative to the event start, or null when the sale price has fixed dates. Sending null removes the rule.', 'event-tickets' ),
				'start'      => static fn(): string => __( 'When the sale price starts.', 'event-tickets' ),
				'end'        => static fn(): string => __( 'When the sale price ends.', 'event-tickets' ),
				'start_mode' => static fn(): string => __( 'How the start is set: `now` (as soon as ticket sales open), `relative` (before the event start) or `specific` (the date sent in `sale_price_start_date`).', 'event-tickets' ),
				'end_mode'   => static fn(): string => __( 'How the end is set: `relative` (before the event start) or `specific` (the date sent in `sale_price_end_date`).', 'event-tickets' ),
				'value'      => static fn( int $min, int $max ): string => sprintf(
					// translators: 1) the lowest number of units, 2) the highest number of units.
					__( 'For a relative boundary, the number of units before the event start, from %1$d to %2$d.', 'event-tickets' ),
					$min,
					$max
				),
				'unit'       => static fn( int ...$units ): string => sprintf(
					// translators: 1) a day and 2) a week, each in seconds.
					__( 'For a relative boundary, the unit in seconds: %1$d (days) or %2$d (weeks).', 'event-tickets' ),
					...$units
				),
			],
		];
	}
}
