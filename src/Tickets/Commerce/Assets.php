<?php
/**
 * Handles registering and setup for assets on Ticket Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use \TEC\Common\Contracts\Service_Provider;
use Tribe__Tickets__Main;

/**
 * Class Assets.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */
class Assets extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 * @since 5.25.0 Removed unused tickets-commerce-settings js.
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		// Tickets Commerce main styles.
		tec_asset(
			$tickets_main,
			'tribe-tickets-commerce-style',
			'tickets-commerce.css',
			[
				'tribe-common-skeleton-style',
				'tribe-common-full-style',
				'tribe-common-responsive',
			],
			null,
			[
				'groups' => [
					'tribe-tickets-commerce',
					'tribe-tickets-commerce-checkout',
				],
				'print'  => true,
			]
		);

		tec_asset(
			$tickets_main,
			'tribe-tickets-commerce-js',
			'v2/tickets-commerce.js',
			[
				'jquery',
				'tribe-common',
				'tribe-tickets-provider',
				'tribe-tickets-loader',
			],
			null,
			[
				'groups' => [
					'tribe-tickets-commerce',
					'tribe-tickets-commerce-checkout',
				],
			]
		);

		tec_asset(
			$tickets_main,
			'tribe-tickets-commerce-notice-js',
			'commerce/notice.js',
			[
				'jquery',
				'tribe-common',
			],
			null
		);

		tec_asset(
			$tickets_main,
			'tribe-tickets-commerce-base-gateway-checkout-toggler',
			'commerce/gateway/toggler.js',
			[],
			null,
			[
				'localize' => [
					'name' => 'tecTicketsCommerceCheckoutToggleText',
					'data' => static function () {
						return [
							'default'    => __( 'Default checkout', 'event-tickets' ),
							'additional' => __( 'Additional payment options', 'event-tickets' ),
						];
					}
				]
			]
		);
	}
}
