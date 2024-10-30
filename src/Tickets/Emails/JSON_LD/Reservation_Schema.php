<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Reservation_Schema.
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Emails\JSON_LD
 */
class Reservation_Schema extends JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	public static string $type = 'EventReservation';

	/**
	 * The event data.
	 *
	 * @since 5.6.0
	 *
	 * @var array
	 */
	public array $event_data;

	/**
	 * Tickets data.
	 *
	 * @since 5.6.0
	 *
	 * @var array
	 */
	public array $tickets;

	/**
	 * Build the schema object from an email.
	 *
	 * @since 5.6.0
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
	 * Build the schema object from an email and specified event.
	 *
	 * @since 5.8.4
	 *
	 * @param Email_Abstract $email    The email instance.
	 * @param int|null       $event_id The Event post ID.
	 *
	 * @return JSON_LD_Abstract The schema instance.
	 */
	public static function build_from_email_and_event( Email_Abstract $email, ?int $event_id = null ): JSON_LD_Abstract {
		$tickets            = $email->get( 'tickets' );
		$schema             = tribe( Reservation_Schema::class );
		$schema->tickets    = $tickets;
		$schema->event_data = $event_id ? Event_Schema::build_from_email_and_post( $email, $event_id )->get_data() : [];

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