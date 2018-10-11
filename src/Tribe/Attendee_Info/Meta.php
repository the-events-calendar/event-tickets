<?php

class Tribe__Tickets__Attendee_Info__Meta {

	public function add_pii_fields_to_attendee( $fields, $ticket_id ) {
		if ( is_admin() ) {
			return $fields;
		}

		/**
		 * @var Tribe__Tickets_Plus__Meta $meta
		 */
		$meta = tribe( 'tickets-plus.main' )->meta();

		$pii_fields = [
			[
				'type'     => 'text',
				'required' => 'on',
				'label'    => __( 'Email', 'event-tickets' ),
				'slug'     => Tribe__Tickets__Tickets::ATTENDEE_EMAIL,
			],
			[
				'type'     => 'text',
				'required' => 'on',
				'label'    => __( 'Last Name', 'event-tickets' ),
				'slug'     => Tribe__Tickets__Tickets::ATTENDEE_LAST_NAME,
			],
			[
				'type'     => 'text',
				'required' => 'on',
				'label'    => __( 'First Name', 'event-tickets' ),
				'slug'     => Tribe__Tickets__Tickets::ATTENDEE_FIRST_NAME,
			],
		];

		foreach ( $pii_fields as $field ) {
			$field_object = $meta->generate_field( $ticket_id, $field['type'], $field );
			array_unshift( $fields, $field_object );
		}

		return $fields;
	}

}
