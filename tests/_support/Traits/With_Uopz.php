<?php

namespace Tribe\Tests\Traits;

use PHPUnit\Framework\Assert;

trait With_Uopz {
	/*
	 * The following properties are static to cover data providers where 2 diff. instances of the test case are used:
	 * one to build the data sets, the other to run the tests.
	 */
	private static array $uopz_set_returns = [];
	private static array $uopz_redefines = [];
	private static array $uopz_set_properties = [];
	private static array $uopz_add_class_fns = [];
	private static array $uopz_del_functions = [];

	/**
	 * @after
	 */
	public function unset_uopz_returns() {
		if ( function_exists( 'uopz_set_return' ) ) {
			foreach ( self::$uopz_set_returns as $f ) {
				if ( is_array( $f ) ) {
					list( $class, $method ) = $f;
					uopz_unset_return( $class, $method );
				} else {
					uopz_unset_return( $f );
				}
			}
		}

		self::$uopz_set_returns = [];
	}

	/**
	 * @after
	 */
	public function unset_uopz_redefines() {
		if ( function_exists( 'uopz_redefine' ) ) {
			foreach ( self::$uopz_redefines as $restore_callback ) {
				$restore_callback();
			}
		}

		self::$uopz_redefines = [];
	}

	/**
	 * @after
	 */
	public function unset_uopz_properties() {
		if ( function_exists( 'uopz_set_property' ) ) {
			foreach ( self::$uopz_set_properties as $definition ) {
				list( $object, $field, $original_value ) = $definition;
				// Overwrite value with what we stored as the original value.
				uopz_set_property( $object, $field, $original_value );
			}
		}
		self::$uopz_set_properties = [];
	}

	/**
	 * @after
	 */
	public function unset_uopz_functions() {
		if ( function_exists( 'uopz_del_function' ) ) {
			foreach ( self::$uopz_del_functions as $function ) {
				uopz_del_function( $function );
			}
		}

		self::$uopz_del_functions = [];
	}

	/**
	 * Wrapper for uopz_set_return
	 *
	 * @since 4.15.1
	 *
	 * @param [type] $fn       The name of an existing function
	 * @param [type] $value    The value the function should return.
	 *                         If a Closure is provided and the execute flag is set,
	 *                         the Closure will be executed in place of the original function.
	 * @param boolean $execute If true, and a Closure was provided as the value,
	 *                         the Closure will be executed in place of the original function.
	 *
	 * @return void
	 */
	private function set_fn_return( $fn, $value, $execute = false ) {
		if ( ! function_exists( 'uopz_set_return' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}
		uopz_set_return( $fn, $value, $execute );
		self::$uopz_set_returns[] = $fn;
	}

	private function set_const_value( $const, ...$args ) {
		if ( ! function_exists( 'uopz_redefine' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}

		if ( count( $args ) === 1 ) {
			// Normal const redefinition.
			$previous_value = defined( $const ) ? constant( $const ) : null;
			if ( null === $previous_value ) {
				$restore_callback = static function () use ( $const ) {
					uopz_undefine( $const );
					Assert::assertFalse( defined( $const ) );
				};
			} else {
				$restore_callback = static function () use ( $previous_value, $const ) {
					uopz_redefine( $const, $previous_value );
					Assert::assertEquals( $previous_value, constant( $const ) );
				};
			}
			uopz_redefine( $const, $args[0] );
			self::$uopz_redefines[] = $restore_callback;

			return;
		}

		// Static class const redefinition.
		$class = $const;
		list( $const, $value ) = $args;
		$previous_value = defined( $class . '::' . $const ) ?
			constant( $class . '::' . $const )
			: null;

		if ( null === $previous_value ) {
			$restore_callback = static function () use ( $const, $class ) {
				uopz_undefine( $class, $const );
				Assert::assertFalse( defined( $class . '::' . $const ) );
			};
		} else {
			$restore_callback = static function () use ( $class, $const, $previous_value ) {
				uopz_redefine( $class, $const, $previous_value );
				Assert::assertEquals( $previous_value, constant( $class . '::' . $const ) );
			};
		}
		uopz_redefine( $const, ...$args );
		self::$uopz_redefines[] = $restore_callback;
	}

	private function set_class_fn_return( $class, $method, $value, $execute = false ) {
		if ( ! function_exists( 'uopz_set_return' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}
		uopz_set_return( $class, $method, $value, $execute );
		self::$uopz_set_returns[] = [ $class, $method ];
	}

	/**
	 * @param $object
	 * @param $field
	 * @param $value
	 */
	private function set_class_property( $object, $field, $value ) {
		if ( ! function_exists( 'uopz_set_property' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}
		$original_value = uopz_get_property( $object, $field );
		uopz_set_property( $object, $field, $value );
		// Store here to override, i.e. unset, later.
		self::$uopz_set_properties[] = [ $object, $field, $original_value ];
	}

	private function add_class_fn( $class, $function, $handler ) {
		if ( ! function_exists( 'uopz_add_function' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}
		uopz_add_function(
			$class,
			$function,
			$handler
		);
		self::$uopz_add_class_fns[] = [ $class, $function ];
	}

	/**
	 * @after
	 */
	public function undefine_uopz_class_fn() {
		if ( ! function_exists( 'uopz_del_function' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}

		foreach ( self::$uopz_add_class_fns as $definition ) {
			list( $class, $function ) = $definition;
			uopz_del_function( $class, $function );
		}
		self::$uopz_add_class_fns = [];
	}

	/**
	 * @param string   $function
	 * @param \Closure $handler
	 */
	private function add_fn( string $function, \Closure $handler ) {
		if ( ! function_exists( 'uopz_add_function' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}
		uopz_add_function( $function, $handler );
		self::$uopz_del_functions[] = $function;
	}
}

