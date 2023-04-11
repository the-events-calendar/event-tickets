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
	 * Get default preview data.
	 *
	 * @since TBD
	 *
	 * @return array The default preview data.
	 */
	public static function get_default_preview_data(): array {
		$current_user = wp_get_current_user();
		$title        = empty( $current_user->first_name ) ?
		__( 'Here\'s your ticket!', 'event-tickets' ) :
		sprintf(
			// Translators: %s - First name of email recipient.
			__( 'Here\'s your ticket, %s!', 'event-tickets' ),
			$current_user->first_name
		);

		return [
			'title'      => $title,
			'heading'    => $title,
			'is_preview' => true,
			'tickets'    => [
				[
					'ticket_id'         => '1234',
					'ticket_name'       => esc_html__( 'General Admission', 'event-tickets' ),
					'holder_name'       => $current_user->first_name . ' ' . $current_user->last_name,
					'holder_first_name' => $current_user->first_name,
					'holder_last_name'  => $current_user->last_name,
					'security_code'     => '17e4a14cec',
					// @todo @juanfra @codingmusician @rafsuntaskin: These should come from TEC.
					'event' => [
						'title'          => esc_html__( 'Rebirth Brass Band', 'event-tickets' ),
						'description'    => '<h4>Additional Information</h4><p>Age Restriction: 18+<br>Door Time: 8:00PM<br>Event Time: 9:00PM</p>',
						'date'           => esc_html__( 'September 22 @ 7:00 pm - 11:00 pm', 'event-tickets' ),
						'image_url'      => esc_url( plugins_url( '/event-tickets/src/resources/images/example-event-image.png' ) ),
						'venue'          => [
							'name'       => esc_html__( 'Saturn', 'event-tickets' ),
							'address1'   => esc_html__( '200 41st Street South', 'event-tickets' ),
							'address2'   => esc_html__( 'Birmingham, AL, 35222', 'event-tickets' ),
							'phone'      => esc_html__( '(987) 654-3210', 'event-tickets' ),
							'website'    => esc_url( get_site_url() ),
						]
					],

				],
			],
		];
	}

	/**
	 * Get Order preview data.
	 *
	 * @since TBD
	 *
	 * @param string $args Array of preview data.
	 *
	 * @return array
	 */
	public static function get_order( $args = [] ): array {
		$default = [
			'created'    => __( 'March 1, 2023', 'event-tickets' ),
			'id'         => 123,
			'provider'   => 'Stripe',
			'status'     => 'success',
			'post_title' => __( 'Black Midi with Special Guests Chat Pile and Apprehend', 'event-tickets' ),
			// @todo @codingmusician: We will need to make this work with the currency settings selected for Tickets Commerce.
			'total'      => '$100.00',
			'purchaser'  => [
				'name'  => __( 'John Doe', 'event-tickets' ),
				'email' => 'john@doe.com',
			],
		];
		return wp_parse_args( $args, $default );
	}

	/**
	 * Get Attendees preview data.
	 *
	 * @since TBD
	 *
	 * @param string $args Array of preview data.
	 *
	 * @return array
	 */
	public static function get_attendees( $args = [] ): array {
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
	 * @param string $args Array of preview data.
	 *
	 * @return array
	 */
	public static function get_tickets( $args = [] ): array {
		$default = [
			[
				'title'    => __( 'General Admission', 'event-tickets' ),
				'quantity' => 2,
				// @todo @codingmusician: We will need to make this work with the currency settings selected for Tickets Commerce.
				'price'    => '$50.00',
			],
		];
		return wp_parse_args( $args, $default );
	}
}
