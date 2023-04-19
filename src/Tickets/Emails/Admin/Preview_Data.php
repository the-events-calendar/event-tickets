<?php
/**
 * Tickets Emails Preview Data class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\Admin
 */

namespace TEC\Tickets\Emails\Admin;

use TEC\Tickets\Commerce\Utils\Value;
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
		$title        = __( 'Here\'s your ticket!', 'event-tickets' );

		if ( ! empty( $current_user->first_name ) ) {
			$title = sprintf(
				// Translators: %s - First name of email recipient.
				__( 'Here\'s your ticket, %s!', 'event-tickets' ),
				$current_user->first_name
			);
		}

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
	public static function get_order( $args = [] ) {
		$total_value = Value::create();
		$total_value->set_value( '100' );

		$order = (object) [
			'id'               => '123',
			'gateway_order_id' => '123',
			'total'            => $total_value,
			'total_value'      => $total_value,
			'purchaser'        => [
				'first_name' => __( 'John', 'event-tickets' ),
				'name'       => __( 'John Doe', 'event-tickets' ),
				'email'      => 'john@doe.com',
			],
			'purchaser_name'   => __( 'John Doe', 'event-tickets' ),
			'purchaser_email'  => 'john@doe.com',
			'gateway'          => __( 'Stripe', 'event-tickets' ),
			'status'           => 'completed',
			'items'            => [
				[
					'ticket_id'         => '1234',
					'ticket_name'       => esc_html__( 'General Admission', 'event-tickets' ),
					'holder_name'       => __( 'John Doe', 'event-tickets' ),
					'holder_first_name' => __( 'John', 'event-tickets' ),
					'holder_last_name'  => __( 'Doe', 'event-tickets' ),
					'security_code'     => '17e4a14cec',
				],
			],
		];

		return $order;
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
