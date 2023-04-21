<?php
/**
 * Tickets Emails Preview Data class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\Admin
 */

namespace TEC\Tickets\Emails\Admin;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Emails\Email\Ticket;
use WP_Post;

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

		// We'll borrow from the Ticket class.
		$ticket_email = tribe( Ticket::class );
		$ticket_email->set_placeholders( self::get_placeholders() );

		return [
			'title'      => $ticket_email->get_heading(),
			'heading'    => $ticket_email->get_heading(),
			'is_preview' => true,
			'order'      => self::get_order(),
			'tickets'    => self::get_tickets(),
		];
	}

	/**
	 * Get Order preview data.
	 *
	 * @since TBD
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
				'ID' => -98,
				'post_author'     => 1,
				'post_date'       => current_time( 'mysql' ),
				'post_date_gmt'   => current_time( 'mysql', 1 ),
				'post_title'      => __( 'General Admission', 'event-tickets' ),
				'post_status'     => 'publish',
				'post_name'       => 'preview-order-' . rand( 1, 9999 ),
				'post_type'       => Order::POSTTYPE,
				'filter'          => 'raw',
				'ticket'          => __( 'General Admission', 'event-tickets' ),
				'ticket_name'     => __( 'General Admission', 'event-tickets' ),
				'purchaser_id'    => 1,
				'purchaser_name'  => __( 'John Doe', 'event-tickets' ),
				'purchaser_email' => __( 'john@doe.com', 'event-tickets' ),
				'holder_name'     => __( 'John Doe', 'event-tickets' ),
				'holder_email'    => __( 'john@doe.com', 'event-tickets' ),
				'ticket_id'       => -98,
				'qr_ticket_id'    => -98,
				'security_code'   => 'abcdefg12345',
				'is_subscribed'   => false,
				'is_purchaser'    => true,
				'iac'             => 'none',
				'attendee_meta'   => '',
				'ticket_exists'   => true,
				'ticket_data'     => [
					'ticket_id' => -98,
					'quantity'  => 2,
					'extra'     => [
						'optout' => true,
						'iac'    => 'none',
					],
					'price'     => 50.0,
					'sub_total' => 50.0,
					'event_id'  => -96,
				],
			],
		];
		return wp_parse_args( $args, $default );
	}

	public static function get_placeholders( $args = [] ): array {
		$tickets = self::get_tickets();
		$order   = self::get_order();
		$default = [
			'{attendee_name}'  => $tickets[0]['purchaser_name'],
			'{attendee_email}' => $tickets[0]['purchaser_email'],
			'{order_number}'   => $order->ID,
		];
		return wp_parse_args( $args, $default );
	}
}
