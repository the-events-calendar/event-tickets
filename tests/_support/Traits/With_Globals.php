<?php

namespace Tribe\Tickets\Test\Traits;

use PHPUnit\Framework\Assert;

trait With_Globals {
	/**
	 * Store global modifications.
	 *
	 * @var mixed
	 */
	protected static $modified_globals = [];

	/**
	 * @after
	 */
	public function restore_global() {
		// Upside down the array to restore the globals in the reverse order they were modified.
		self::$modified_globals = array_reverse( self::$modified_globals );

		foreach ( self::$modified_globals as $restore_global_callback ) {
			$restore_global_callback();
		}

		self::$modified_globals = [];
	}

	/**
	 * Set a global value.
	 *
	 * @param string $const
	 * @param mixed  $value
	 * @param int    $offset
	 */
	private function set_global_value( $global, $value, $offset = '' ) {
		$previous_value     = empty( $GLOBALS[ $global ] ) ? null : $GLOBALS[ $global ];
		// force set the $global offset.
		$GLOBALS[ $global ] = $previous_value;
		$previous_value     = $offset && ! empty( $previous_value[ $offset ] ) ? $previous_value[ $offset ] : $previous_value;


		if ( null === $previous_value ) {
			$restore_callback = static function () use ( $global, $offset ) {
				if ( $offset ) {
					$GLOBALS[ $global ][ $offset ] = null;
				} else {
					$GLOBALS[ $global ] = null;
				}
				Assert::assertTrue( $offset ? empty( $GLOBALS[ $global ][ $offset ] ) : empty( $GLOBALS[ $global ] ) );
			};
		} else {
			$restore_callback = static function () use ( $previous_value, $global, $offset ) {
				if ( $offset ) {
					$GLOBALS[ $global ][ $offset ] = $previous_value;
				} else {
					$GLOBALS[ $global ] = $previous_value;
				}
				Assert::assertEquals( $previous_value, $offset ? $GLOBALS[ $global ][ $offset ] : $GLOBALS[ $global ] );
			};
		}

		if ( $offset ) {
			$GLOBALS[ $global ][ $offset ] = $value;
		} else {
			$GLOBALS[ $global ] = $value;
		}

		self::$modified_globals[] = $restore_callback;
	}
}
