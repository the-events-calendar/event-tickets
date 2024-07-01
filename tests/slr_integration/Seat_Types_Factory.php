<?php

namespace TEC\Tickets\Seating\Tests\Integration;

use TEC\Tickets\Seating\Service\Seat_Types;

trait Seat_Types_Factory {
	protected function given_seat_types_just_updated(): void {
		set_transient( Seat_Types::update_transient_name(), time() - 1 );
	}

	protected function given_many_seat_types_in_db_for_layout( string $layout_id, int $count = 3 ): void {
		Seat_Types::insert_rows_from_service(
			array_map(
				function ( int $n ) use ( $layout_id ) {
					return [
						'id'       => "seat-type-$n",
						'name'     => "Test seat type $n",
						'mapId'    => 'some-map',
						'layoutId' => $layout_id,
						'seats'    => $n % 10,
					];
				},
				range( 1, $count )
			)
		);
	}
}