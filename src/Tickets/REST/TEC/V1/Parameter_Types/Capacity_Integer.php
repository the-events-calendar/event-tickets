<?php
/**
 * Capacity integer parameter type.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Parameter_Types
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Parameter_Types;

use Closure;
use TEC\Common\REST\TEC\V1\Abstracts\Parameter;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;

/**
 * Capacity integer parameter type.
 *
 * Accepts positive integers for limited capacity or "unlimited" for unlimited capacity.
 *
 * @since TBD
 */
class Capacity_Integer extends Parameter {

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param string         $name                 The name of the parameter.
	 * @param ?Closure       $description_provider The description provider.
	 * @param mixed          $by_default           The default value.
	 * @param ?int           $minimum              The minimum value.
	 * @param ?int           $maximum              The maximum value.
	 * @param bool           $required             Whether the parameter is required.
	 * @param ?Closure       $validator            The validator.
	 * @param ?Closure       $sanitizer            The sanitizer.
	 * @param int|float|null $multiple_of          The multiple of.
	 * @param string         $location             The parameter location.
	 * @param bool           $deprecated           Whether the parameter is deprecated.
	 * @param ?bool          $nullable             Whether the parameter is nullable.
	 * @param ?bool          $read_only            Whether the parameter is read only.
	 * @param ?bool          $write_only           Whether the parameter is write only.
	 */
	public function __construct(
		string $name = 'example',
		?Closure $description_provider = null,
		$by_default = null,
		?int $minimum = null,
		?int $maximum = null,
		bool $required = false,
		?Closure $validator = null,
		?Closure $sanitizer = null,
		$multiple_of = null,
		string $location = self::LOCATION_QUERY,
		?bool $deprecated = null,
		?bool $nullable = null,
		?bool $read_only = null,
		?bool $write_only = null
	) {
		$this->name                 = $name;
		$this->description_provider = $description_provider;
		$this->required             = $required;
		$this->default              = $by_default;
		$this->minimum              = $minimum;
		$this->maximum              = $maximum;
		$this->validator            = $validator;
		$this->sanitizer            = $sanitizer;
		$this->multiple_of          = $multiple_of;
		$this->location             = $location;
		$this->deprecated           = $deprecated;
		$this->nullable             = $nullable;
		$this->read_only            = $read_only;
		$this->write_only           = $write_only;
	}

	/**
	 * Returns the parameter type.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'integer';
	}

	/**
	 * Returns the parameter validator.
	 *
	 * @since TBD
	 *
	 * @return ?Closure The validator or a default one if not set.
	 */
	public function get_validator(): Closure {
		return $this->validator ?? function ( $value ): bool {
			// Accept positive integers for limited capacity.
			if ( is_int( $value ) && $value > 0 ) {
				return true;
			}

			// Accept unlimited values.
			if ( $value === -1 || $value === 'unlimited' || $value === '' ) {
				return true;
			}

			// Accept string representations of positive integers.
			if ( is_string( $value ) && is_numeric( $value ) && (int) $value > 0 ) {
				return true;
			}

			/* translators: 1) is the name of the parameter. */
			$exception = new InvalidRestArgumentException( sprintf( __( 'Parameter `{%1$s}` must be a positive integer or "unlimited".', 'tribe-common' ), $this->get_name() ) );
			$exception->set_argument( $this->get_name() );
			$exception->set_internal_error_code( 'tec_rest_invalid_capacity_parameter' );

			/* translators: 1) is the name of the parameter. */
			$exception->set_details( sprintf( __( 'The parameter `{%1$s}` must be a positive integer for limited capacity or "unlimited" for unlimited capacity.', 'tribe-common' ), $this->get_name() ) );
			throw $exception;
		};
	}

	/**
	 * Returns the parameter sanitizer.
	 *
	 * @since TBD
	 *
	 * @return ?Closure The sanitizer or a default one if not set.
	 */
	public function get_sanitizer(): Closure {
		return $this->sanitizer ?? function ( $value ): int {
			// Convert unlimited values to -1 (backend format).
			if ( $value === 'unlimited' || $value === '' ) {
				return -1;
			}

			// Return -1 for unlimited capacity.
			if ( $value === -1 ) {
				return -1;
			}

			// Convert to integer for limited capacity.
			return (int) $value;
		};
	}

	/**
	 * Returns the parameter default value.
	 *
	 * @since TBD
	 *
	 * @return mixed The default value or null if not set.
	 */
	public function get_default(): ?int {
		return $this->default;
	}

	/**
	 * Returns the parameter example.
	 *
	 * @since TBD
	 *
	 * @return mixed The example value or 100 if not set.
	 */
	public function get_example(): int {
		if ( $this->example ) {
			return (int) $this->example;
		}

		return 100;
	}

	/**
	 * Returns the parameter as an array.
	 *
	 * @since TBD
	 *
	 * @return array The parameter as an array.
	 */
	public function to_array(): array {
		$schema = parent::to_array();

		// Add custom schema properties for OpenAPI documentation.
		$schema['description'] = $this->get_description();
		$schema['examples']    = [
			'limited'   => 100,
			'unlimited' => -1,
		];

		return $schema;
	}
}
