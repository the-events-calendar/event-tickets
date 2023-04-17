<?php
namespace TEC\Tickets\Emails\JSON_LD;

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
	 * @since TBD
	 *
	 * @param Email_Abstract $email The email object.
	 *
	 * @return array The JSON LD data.
	 */
	public static function get_data( \TEC\Tickets\Emails\Email_Abstract $email ): array {

		$data = [];

		if ( $email->slug === 'completed-order' ) {
			$order = $email->__get( 'order' );

			if ( empty( $order ) ) {
				return [];
			}

			$data = [
				'@context' => 'https://schema.org',
				'@type'    => 'Order',
				'orderNumber' => $order->ID,
				'orderStatus' => 'https://schema.org/OrderDelivered',
				'acceptedOffer' => [
					'@type' => 'Offer',
					'price' => $order->total,
					'priceCurrency' => $order->currency,
					'itemOffered' => [
						'@type' => 'Event',
						'name' => $order->post_title,
					],
				],
			];

		}

		return $data;
	}
}