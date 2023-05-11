<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Commerce\Module;

/**
 * Class Order_Schema
 *
 * @since TBD
 *
 * @pacakge TEC\Tickets\Emails\JSON_LD
 */
class Order_Schema extends JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $type = 'Order';

	/**
	 * The order object.
	 *
	 * @since TBD
	 *
	 * @var \WP_Post
	 */
	public \WP_Post $order;

	/**
	 * Order_Schema constructor.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $order The order object.
	 */
	public function __construct( \WP_Post $order ) {
		$this->order = $order;
	}

	/**
	 * @inheritDoc
	 */
	public function get_data(): array {
		$order       = $this->order;
		$commerce    = tribe( Module::class );
		$report_link = $commerce->get_event_reports_link( $order->events_in_order[0], true );

		$data = [
			'orderNumber'     => $order->ID,
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

		return array_merge( $this->get_basic_data(), $this->get_merchant_data(), $data );
	}
}