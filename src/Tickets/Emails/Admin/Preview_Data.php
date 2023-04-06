<?php
/**
 * Tickets Emails Preview Data class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\Admin
 */

namespace TEC\Tickets\Emails\Admin;

/**
 * Class Preview_Data.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\Admin
 */
class Preview_Data {

	/**
	 * Get Order preview data.
	 * 
	 * @since TBD
	 *
	 * @param string $status Status of order.
	 * 
	 * @return array
	 */
	static public function get_order( $args = [] ): array {
		$default = [
			'created'    => __( 'March 1, 2023', 'event-tickets' ),
			'id'         => 123,
			'provider'   => 'Stripe',
			'status'     => 'success',
			'post_title' => __( 'Black Midi with Special Guests Chat Pile and Apprehend', 'event-tickets' ),
			'total'      => '$100.00',
			'purchaser'  => [
				'first_name'  => __( 'John', 'event-tickets' ),
				'name'        => __( 'John Doe', 'event-tickets' ),
				'email'       => 'john@doe.com',
			],
		];
		return wp_parse_args( $args, $default );
	}

	/**
	 * Get Attendees preview data.
	 * 
	 * @since TBD
	 * 
	 * @return array
	 */
	static public function get_attendees( $args = [] ): array {
		$default = [
			[
				'ticket_title' => __( 'General Admission', 'event-tickets' ),
				'ticket_id'    => '17e4a14cec',
				'name'         => __( 'John Doe', 'event-tickets' ),
				'email'        => 'john@doe.com',
				'custom_fields' => [
					[
						'label' => __( 'Shirt size', 'event-tickets' ),
						'value' => __( 'large', 'event-tickets' ),
					],
					[
						'label' => __( 'Backstage pass', 'event-tickets' ),
						'value' => __( 'yes', 'event-tickets' ),
					],
				],
			],
			[
				'ticket_title' => __( 'General Admission', 'event-tickets' ),
				'ticket_id'    => '55e5e14w4',
				'name'         => __( 'Jane Doe', 'event-tickets' ),
				'email'        => 'jane@doe.com',
				'custom_fields' => [
					[
						'label' => __( 'Shirt size', 'event-tickets' ),
						'value' => __( 'small', 'event-tickets' ),
					],
					[
						'label' => __( 'Backstage pass', 'event-tickets' ),
						'value' => __( 'yes', 'event-tickets' ),
					],
				],
			],
		];
		return wp_parse_args( $args, $default );
	}

	/**
	 * Get Tickets preview data.
	 * 
	 * @since TBD
	 * 
	 * @return array
	 */
	static public function get_tickets( $args = [] ): array {
		$default = [
			[
				'title'    => __( 'General Admission', 'event-tickets' ),
				'quantity' => 2,
				'price'    => '$50.00'
			]
		];
		return wp_parse_args( $args, $default );
	}

}