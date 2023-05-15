<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Reservation_Schema.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\JSON_LD
 */
class Reservation_Schema extends JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $type = 'EventReservation';

	/**
	 * The event data.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	public array $event_data;

	/**
	 * Tickets data.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	public array $tickets;

	/**
	 * Build the schema object from an email.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email The email instance.
	 *
	 * @return Reservation_Schema The schema instance.
	 */
	public static function build_from_email( Email_Abstract $email ): Reservation_Schema {
		$tickets            = $email->get( 'tickets' );
		$schema             = tribe( Reservation_Schema::class );
		$schema->tickets    = $tickets;
		$schema->event_data = Event_Schema::build_from_email( $email )->get_data();

		return $schema->filter_schema_by_email( $email );
	}

	/**
	 * @inheritDoc
	 */
	public function build_data(): array {
		$data = [];

		// Bail if there's no tickets or post ID.
		if ( ! tec_tickets_tec_events_is_active() || empty( $this->tickets ) || empty( $this->event_data ) ) {
			return [];
		}

		foreach ( $this->tickets as $ticket ) {
			$ticket_data = [
				'reservationNumber' => $ticket['order_id'],
				'reservationStatus' => "https://schema.org/Confirmed",
				'underName'         => [
					'@type' => "Person",
					'name'  => $ticket['holder_name'],
					'email' => $ticket['holder_email'],
				],
				'reservationFor'    => $this->event_data,
				'ticketToken'       => $ticket['security_code'],
				'ticketNumber'      => $ticket['attendee_id'],
				'numSeats'          => "1",
			];
			$data[] = array_merge( $this->get_basic_data(), $ticket_data );
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [
			'event'   => $this->event_data,
			'tickets' => $this->tickets,
		];
	}
}