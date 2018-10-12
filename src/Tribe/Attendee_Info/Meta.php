<?php

/**
 * Class Tribe__Tickets__Attendee_Info__Meta
 *
 * @since TBD
 */
class Tribe__Tickets__Attendee_Info__Meta {

	/**
	 * Add the PII Fields (First Name, Last Name, Email) to the Attendee Info fields array.
	 *
	 * @since TBD
	 *
	 * @filter event_tickets_plus_meta_fields_by_ticket 10 2
	 *
	 * @param $fields
	 * @param $ticket_id
	 *
	 * @return mixed
	 */
	public function add_pii_fields_to_attendee( $fields, $ticket_id ) {
		if ( is_admin() || tribe_is_event() ) {
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

	/**
	 * Add a product-deletion parameter to the shopping URL on Paypal in order to clear old products
	 * if the order is cancelled within Paypal.
	 *
	 * @since TBD
	 *
	 * @param $args
	 *
	 * @filter tribe_tickets_commerce_paypal_add_to_cart_args 10 1
	 *
	 * @return array
	 */
	public function add_product_delete_to_paypal_url( $args ) {
		$args['shopping_url'] = add_query_arg( [ 'clear_product_cache' => true ], $args['shopping_url'] );

		return $args;
	}
}
