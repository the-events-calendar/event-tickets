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
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		tribe_asset(
			$tickets_main,
			'tribe-tickets-admin-commerce-settings',
			'admin/tickets-commerce-settings.js',
			[
				'jquery',
				'tribe-dropdowns',
				'tribe-select2',
			],
			'admin_enqueue_scripts'
		);

		// Tickets Commerce main styles.
		tribe_asset(
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

		tribe_asset(
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

		tribe_asset(
			$tickets_main,
			'tribe-tickets-commerce-notice-js',
			'commerce/notice.js',
			[
				'jquery',
				'tribe-common',
			],
			null
		);

		tribe_asset(
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
