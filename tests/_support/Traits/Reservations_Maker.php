<?php

namespace Tribe\Tickets\Test\Traits;

trait Reservations_Maker {
	private int $mock_reservation_counter = 1;
	private int $mock_seat_type_counter = 1;
	private int $mock_seat_label_counter = 1;

	/**
	 * @before
	 * @after
	 */
	public function reset_mock_reservation_counters(): void {
		$this->mock_reservation_counter = 1;
		$this->mock_seat_type_counter   = 1;
		$this->mock_seat_label_counter  = 1;
	}

	protected function create_mock_reservation_data( array $overrides = [] ): array {
		return [
			'reservation_id' => $overrides['reservation_id'] ?? ( 'reservation-id-' . $this->mock_reservation_counter ++ ),
			'seat_type_id'   => $overrides['seat_type_id'] ?? ( 'seat-type-id-' . $this->mock_seat_type_counter ++ ),
			'seat_label'     => $overrides['seat_label'] ?? ( 'seat-label-' . $this->mock_seat_label_counter ++ ),
		];
	}

	protected function create_mock_reservations_data( array $object_ids, int $count = 1 ): array {
		$data = [];
		foreach ( $object_ids as $i => $object_id ) {
			foreach ( range( 1, $count ) as $k ) {
				$data[ $object_id ][] = $this->create_mock_reservation_data( [
					'seat_type_id' => 'seat-type-id-' . $i,
					'seat_label'   => 'seat-label-' . $i . '-' . $k,
				] );
			}
		}

		return $data;
	}

	protected function create_mock_ajax_reservation_data( array $overrides = [] ): array {
		return [
			'reservationId' => $overrides['reservationId'] ?? ( 'reservation-id-' . $this->mock_reservation_counter ++ ),
			'seatTypeId'    => $overrides['seatTypeId'] ?? ( 'seat-type-id-' . $this->mock_seat_type_counter ++ ),
			'seatLabel'     => $overrides['seatLabel'] ?? ( 'seat-label-' . $this->mock_seat_label_counter ++ ),
		];
	}

	protected function create_mock_ajax_reservations_data( array $object_ids, int $count = 1 ): array {
		$data = [];
		foreach ( $object_ids as $i => $object_id ) {
			foreach ( range( 1, $count ) as $k ) {
				$data[ $object_id ][] = $this->create_mock_ajax_reservation_data( [
					'seatTypeId' => 'seat-type-id-' . $i,
					'seatLabel'  => 'seat-label-' . $i . '-' . $k,
				] );
			}
		}

		return $data;
	}
}
