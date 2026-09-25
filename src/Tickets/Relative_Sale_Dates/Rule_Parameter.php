<?php
/**
 * The sales window rule as a TEC REST API parameter.
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
 * A nullable object parameter that keeps the rule as it was sent, for `Rule` to validate.
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
	 * Rule_Parameter constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->name                 = Ticket_Save::DATA_KEY;
		$this->description_provider = fn() => __( 'The sales window relative to the event, or null when the ticket has fixed sale dates. Sending null removes the rule.', 'event-tickets' );
		$this->required             = false;
		$this->nullable             = true;
		$this->properties           = new PropertiesCollection();
		$this->properties[]         = $this->get_end_parameter( 'start', fn() => __( 'When sales start.', 'event-tickets' ) );
		$this->properties[]         = $this->get_end_parameter( 'end', fn() => __( 'When sales end.', 'event-tickets' ) );
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
	 * Gets the documentation of one end of the window.
	 *
	 * @since TBD
	 *
	 * @param string  $name                 The end, `start` or `end`.
	 * @param Closure $description_provider The provider of the end's description.
	 *
	 * @return Entity The end's documentation.
	 */
	private function get_end_parameter( string $name, Closure $description_provider ): Entity {
		$properties   = new PropertiesCollection();
		$properties[] = (
			new Text(
				'mode',
				fn() => __( 'How the end is set: `default` (sales open at once, or close when the event starts), `relative` (before the event) or `specific` (the date sent with the ticket).', 'event-tickets' ),
				null,
				[ Rule::MODE_DEFAULT, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ]
			)
		)->set_example( Rule::MODE_RELATIVE );
		$properties[] = (
			new Integer(
				'value',
				fn() => sprintf(
					// translators: 1) the lowest number of units, 2) the highest number of units.
					__( 'For a relative end, the number of units before the anchor, from %1$d to %2$d.', 'event-tickets' ),
					Rule::MIN_VALUE,
					Rule::MAX_VALUE
				),
				null,
				Rule::MIN_VALUE,
				Rule::MAX_VALUE
			)
		)->set_example( 2 );
		$properties[] = (
			new Integer(
				'unit',
				fn() => sprintf(
					// translators: 1) a minute, 2) an hour, 3) a day and 4) a week, each in seconds.
					__( 'For a relative end, the unit in seconds: %1$d (minutes), %2$d (hours), %3$d (days) or %4$d (weeks).', 'event-tickets' ),
					Rule::UNIT_MINUTES,
					Rule::UNIT_HOURS,
					Rule::UNIT_DAYS,
					Rule::UNIT_WEEKS
				)
			)
		)->set_example( Rule::UNIT_WEEKS );
		$properties[] = (
			new Text(
				'anchor',
				fn() => __( 'For a relative end, the event date it is counted from.', 'event-tickets' ),
				null,
				[ Rule::ANCHOR_START, Rule::ANCHOR_END ]
			)
		)->set_example( Rule::ANCHOR_START );

		return new Entity( $name, $description_provider, $properties, false );
	}
}
