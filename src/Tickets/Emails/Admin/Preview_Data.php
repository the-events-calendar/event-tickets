<?php
/**
 * Tickets Emails Preview Data class.
 *
 * @since 5.5.11
 *
 * @package TEC\Tickets\Emails\Admin
 */

namespace TEC\Tickets\Emails\Admin;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;
use WP_Post;

/**
 * Class Preview_Data.
 *
 * @since 5.5.11
 *
 * @package TEC\Tickets\Emails\Admin
 */
class Preview_Data {
	/**
	 * Get default preview data.
	 *
	 * @since 5.5.11
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
	 * @since 5.5.11
	 *
	 * @param string $args Array of preview data.
	 *
	 * @return WP_Post
	 */
	public static function get_order( $args = [] ) {
		$total_value = Value::create( '100' );

		$order = new WP_Post( (object) [
			'ID'               => -99,
			'gateway_order_id' => -99,
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
			'tickets'          => self::get_tickets(),
			'post_author'      => 1,
			'post_date'        => current_time( 'mysql' ),
			'post_date_gmt'    => current_time( 'mysql', 1 ),
			'post_title'       => __( 'Preview Order', 'event-tickets' ),
			'post_status'      => 'publish',
			'post_name'        => 'preview-order-' . rand( 1, 9999 ),
			'post_type'        => Order::POSTTYPE,
			'filter'           => 'raw',
		] );

		return $order;
	}

	/**
	 * Get Attendees preview data.
	 *
	 * @since 5.5.11
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
	 * @since 5.5.11
	 *
	 * @param string $args Array of preview data.
	 *
	 * @return array
	 */
	public static function get_tickets( $args = [] ): array {
		$tickets = [
			new WP_Post( (object) [
				'ID' => -98,
				'post_author'   => 1,
				'post_date'     => current_time( 'mysql' ),
				'post_date_gmt' => current_time( 'mysql', 1 ),
				'post_title'    => __( 'General Admission', 'event-tickets' ),
				'post_status'   => 'publish',
				'post_name'     => 'preview-order-' . rand( 1, 9999 ),
				'post_type'     => Order::POSTTYPE,
				'filter'        => 'raw',
				'ticket_data'   => [
					'ticket_id' => -98,
					'quantity'  => 2,
					'extra' => [
						'optout' => true,
						'iac' => 'none',
					],
					'price' => 50.0,
					'sub_total' => 50.0,
					'event_id' => -97,
				],
			] ),
		];
		return $tickets;
	}
}
