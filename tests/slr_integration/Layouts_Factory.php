<?php

namespace TEC\Tickets\Seating\Tests\Integration;

use TEC\Tickets\Seating\Service\Layouts;

trait Layouts_Factory {
	protected function given_layouts_just_updated(): void {
		set_transient( Layouts::update_transient_name(), time() - 1 );
	}

	protected function given_many_layouts_in_db( int $count = 3, $map_id = 'some-map', string $id_pattern = 'layout-%d' ): void {
		Layouts::insert_rows_from_service(
			array_map(
				static function ( int $n ) use ( $id_pattern ) {
					return [
						'id'            => sprintf( $id_pattern, $n ),
						'name'          => "Test layout $n",
						'seats'         => $n % 10,
						'mapId'         => 'some-map',
						'createdDate'   => microtime( true ) * 1000,
						'screenshotUrl' => "https://example.com/layout-{$n}-screenshot.png",
					];
				},
				range( 1, $count )
			)
		);
	}
}