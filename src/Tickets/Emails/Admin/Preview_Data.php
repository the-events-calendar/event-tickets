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
use TEC\Tickets\Emails\Email\Ticket;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Gateways\Manual\Gateway;
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

		// We'll borrow from the Ticket class.
		$ticket_email = tribe( Ticket::class );
		$ticket_email->set_placeholders( self::get_placeholders() );

		return [
			'title'      => $ticket_email->get_heading(),
			'heading'    => $ticket_email->get_heading(),
			'is_preview' => true,
			'order'      => static::get_order(),
			'tickets'    => static::get_tickets(),
			'post'       => static::get_post(),
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
			'gateway_order_id' => 'test_cd7d068a5ef24c02',
			'items' =>  [
				[
					'ticket_title' => __( 'General Admission', 'event-tickets' ),
					'ticket_id' => -98,
					'quantity'  => 2,
					'extra'     => [
						'optout' => true,
						'iac'    => 'none',
					],
					'price'     => 50.0,
					'sub_total' => 50.0,
					'event_id'  => -96,
				]
			],
			'total'            => $total_value,
			'total_value'      => $total_value,
			'purchaser'        => [
				'first_name' => __( 'John', 'event-tickets' ),
				'name'       => __( 'John Doe', 'event-tickets' ),
				'email'      => 'john@doe.com',
			],
			'purchaser_name'   => __( 'John Doe', 'event-tickets' ),
			'purchaser_email'  => 'john@doe.com',
			'gateway'          => Gateway::get_key(),
			'status'           => __( 'Completed', 'event-tickets' ),
			'status_slug'      => 'completed',
			'tickets'          => self::get_tickets(),
			'post_author'      => 1,
			'post_date'        => '2023-04-17 17:06:56',
			'post_date_gmt'    => '2023-04-17 22:06:56',
			'purchase_time'    => '2023-04-17 17:06:56',
			'purchase_date'    => '2023-04-17 17:06:56',
			'post_title'       => __( 'Preview Order', 'event-tickets' ),
			'post_status'      => 'tec-tc-completed',
			'post_name'        => 'preview-order-test_cd7d068a5ef24c02',
			'post_type'        => Order::POSTTYPE,
			'filter'           => 'raw',
			'provider'         => Module::class,
			'gateway_payload'  => [
				'tec-tc-completed' => [],
			],
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
				'ID'                    => 9999,
				'post_author'           => '1',
				'post_date'             => '2023-04-17 17:06:56',
				'post_date_gmt'         => '2023-04-17 22:06:56',
				'post_content'          => '',
				'post_title'            => '',
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => '9999',
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => '2023-04-17 17:06:56',
				'post_modified_gmt'     => '2023-04-17 22:06:56',
				'post_content_filtered' => '',
				'post_parent'           => 9998,
				'guid'                  => '',
				'menu_order'            => 0,
				'post_type'             => 'tec_tc_attendee',
				'post_mime_type'        => '',
				'comment_count'         => '0',
				'filter'                => 'raw',
				'order_id'              => 9999,
				'order_status'          => 'Completed',
				'optout'                => true,
				'ticket'                => 'General Admission',
				'attendee_id'           => 9997,
				'security'              => 'abcde12345',
				'product_id'            => '1',
				'check_in'              => NULL,
				'ticket_sent'           => 0,
				'price_paid'            => '50',
				'currency'              => 'USD',
				'provider'              => 'TEC\\Tickets\\Commerce\\Module',
				'provider_slug'         => 'tc',
				'purchaser_id'          => 1,
				'purchaser_name'        => 'John Doe',
				'purchaser_email'       => 'john@doe.com',
				'event_id'              => '9991',
				'ticket_name'           => 'General Admission',
				'user_id'               => '1',
				'holder_name'           => 'John Doe',
				'holder_email'          => 'john@doe.com',
				'ticket_id'             => 9999,
				'qr_ticket_id'          => 9999,
				'security_code'         => 'abcde12345',
				'is_subscribed'         => false,
				'is_purchaser'          => true,
				'iac'                   => 'none',
				'attendee_meta'         => '',
				'ticket_exists'         => true,
			],
			[
				'ID'                    => 10000,
				'post_author'           => '1',
				'post_date'             => '2023-04-17 17:06:56',
				'post_date_gmt'         => '2023-04-17 22:06:56',
				'post_content'          => '',
				'post_title'            => '',
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => '9999',
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => '2023-04-17 17:06:56',
				'post_modified_gmt'     => '2023-04-17 22:06:56',
				'post_content_filtered' => '',
				'post_parent'           => 9998,
				'guid'                  => '',
				'menu_order'            => 0,
				'post_type'             => 'tec_tc_attendee',
				'post_mime_type'        => '',
				'comment_count'         => '0',
				'filter'                => 'raw',
				'order_id'              => 10000,
				'order_status'          => 'Completed',
				'optout'                => true,
				'ticket'                => 'General Admission',
				'attendee_id'           => 9997,
				'security'              => 'abcde12345',
				'product_id'            => '1',
				'check_in'              => NULL,
				'ticket_sent'           => 0,
				'price_paid'            => '50',
				'currency'              => 'USD',
				'provider'              => 'TEC\\Tickets\\Commerce\\Module',
				'provider_slug'         => 'tc',
				'purchaser_id'          => 1,
				'purchaser_name'        => 'John Doe',
				'purchaser_email'       => 'john@doe.com',
				'event_id'              => '9991',
				'ticket_name'           => 'General Admission',
				'user_id'               => '1',
				'holder_name'           => 'Jane Doe',
				'holder_email'          => 'jane@doe.com',
				'ticket_id'             => 10000,
				'qr_ticket_id'          => 10000,
				'security_code'         => '12345abcde',
				'is_subscribed'         => false,
				'is_purchaser'          => true,
				'iac'                   => 'none',
				'attendee_meta'         => '',
				'ticket_exists'         => true,
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
		$default = [
			[
				'ID' => -98,
				'post_author'     => 1,
				'post_date'       => '2023-04-17 17:06:56',
				'post_date_gmt'   => '2023-04-17 17:06:56',
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
				'security'        => 'abcdefg12345',
				'is_subscribed'   => false,
				'is_purchaser'    => true,
				'iac'             => 'none',
				'attendee_meta'   => '',
				'ticket_exists'   => true,
				'event_id'        => 999,
				'product_id'      => 998,
				'attendee_id'     => 997,
			],
		];
		return wp_parse_args( $args, $default );
	}

	/**
	 * Get Post Data for preview.
	 *
	 * @since 5.6.0
	 *
	 * @param string $args Array of preview data.
	 *
	 * @return object
	 */
	public static function get_post( $args = [] ) {
		$default = [
			'ID'             => -91,
			'post_author'    => 1,
			'post_date'      => '2023-04-17 17:06:56',
			'post_date_gmt'  => '2023-04-17 17:06:56',
			'post_title'     => __( 'Arts in the Park', 'event-tickets' ),
			'post_excerpt'   => __( 'Experience the magic of creativity in nature. Save the date and indulge your senses at "Arts in the Park"!  Join us for an enchanting day of vibrant musics and captivating... ', 'event-tickets' ),
			'post_status'    => 'publish',
			'post_permalink' => '#',
			'post_name'      => 'preview-post-91',
			'post_type'      => 'post',
			'filter'         => 'raw',
		];

		return (object) wp_parse_args( $args, $default );
	}

	/**
	 * Get preview placeholders.
	 *
	 * @since 5.6.0
	 *
	 * @param array $args Override arguments.
	 *
	 * @return array
	 */
	public static function get_placeholders( $args = [] ): array {
		$tickets = self::get_tickets();
		$order   = self::get_order();
		$default = [
			'{attendee_name}'  => $tickets[0]['purchaser_name'],
			'{attendee_email}' => $tickets[0]['purchaser_email'],
			'{order_number}'   => $order->ID,
			'{order_id}'       => $order->ID,
		];
		return wp_parse_args( $args, $default );
	}
}
