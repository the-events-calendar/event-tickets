<?php

namespace Tribe\Tickets\Test\Traits;

use PHPUnit\Framework\Assert;

trait With_No_Object_Storage {
	protected function assert_no_object_stored( array $stored ) {
		foreach ( $stored as $key => $meta ) {
			if ( is_array( $meta ) ) {
				$this->assert_no_object_stored( $meta );
			}

			Assert::assertTrue( ! is_object( maybe_unserialize( $meta ) ), sprintf( 'Object stored for key %s', $key ) );
		}
	}
}
