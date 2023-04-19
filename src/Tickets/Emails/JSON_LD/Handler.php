<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Emails\Email\Completed_Order;
use TEC\Tickets\Emails\Email\Purchase_Receipt;
use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Handler
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\JSON_LD
 */
class Handler {

	/**
	 * Get the JSON LD data for the email.
	 *
	 * @param Email_Abstract $email The email object.
	 *
	 * @return array The JSON LD data.
	 * @since TBD
	 *
	 */
	public static function get_data( \TEC\Tickets\Emails\Email_Abstract $email ): array {

		$data = [];

		if ( $email::$slug === Completed_Order::$slug
		     || $email::$slug === Purchase_Receipt::$slug ) {
			$order = $email->__get( 'order' );

			if ( empty( $order ) ) {
				return [];
			}

			return ( new Handler )->get_completed_order_data( $order );
		}

		return $data;
	}

	/**
	 * Get the JSON LD data for a completed order.
	 *
	 * @param $order \WP_Post The order post object.
	 *
	 * @return array<string,mixed>
	 * @since TBD
	 *
	 */
	public function get_completed_order_data( $order ): array {

		$commerce    = tribe( Module::class );
		$report_link = $commerce->get_event_reports_link( $order->events_in_order[0], true );

		$data = [
			'@context'        => 'https://schema.org',
			'@type'           => 'Order',
			'orderNumber'     => $order->ID,
			'merchant'        => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			],
			'priceCurrency'   => $order->currency,
			'price'           => $order->total,
			'orderStatus'     => 'https://schema.org/OrderDelivered',
			'customer'        => [
				'@type' => 'Person',
				'name'  => $order->purchaser_name,
				'email' => $order->purchaser_email,
			],
			'url'             => $report_link,
			'potentialAction' => [
				'@type' => 'ViewAction',
				'url'   => $report_link,
				'name'  => esc_html__( 'View Order', 'event-tickets' ),
			],
		];

		// Add order items.
		foreach ( $order->items as $ticket_id => $item ) {
			$ticket                  = tec_tc_get_ticket( $ticket_id );
			$data['acceptedOffer'][] = [
				'@type'            => 'Offer',
				'price'            => $item['price'],
				'priceCurrency'    => $order->currency,
				'itemOffered'      => [
					'@type' => 'Ticket',
					'name'  => $ticket->post_title,
				],
				'eligibleQuantity' => [
					'@type' => 'QuantitativeValue',
					'value' => $item['quantity'],
				],

			];
		}

		return $data;
	}
}