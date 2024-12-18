<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Order_Schema
 *
 * @since 5.6.0
 *
 * @pacakge TEC\Tickets\Emails\JSON_LD
 */
class Order_Schema extends JSON_LD_Abstract {

	use Is_Ticket;

	/**
	 * The type of the schema.
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	public static string $type = 'Order';

	/**
	 * The order object.
	 *
	 * @since 5.6.0
	 *
	 * @var \WP_Post
	 */
	protected \WP_Post $order;

	/**
	 * Build the schema object from an email.
	 *
	 * @since 5.6.0
	 *
	 * @param Email_Abstract $email The email instance.
	 *
	 * @return Order_Schema The schema instance.
	 */
	public static function build_from_email( Email_Abstract $email ): Order_Schema {
		$schema = tribe( Order_Schema::class );
		$schema->order = $email->get( 'order' );

		return $schema->filter_schema_by_email( $email );
	}

	/**
	 * @inheritDoc
	 */
	public function build_data(): array {
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
		foreach ( $order->items as $item ) {
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}

			$ticket_id               = $item['ticket_id'];
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

	/**
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [
			'order' => $this->order,
		];
	}
}
