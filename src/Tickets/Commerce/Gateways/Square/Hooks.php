<?php
/**
 * Square Generic Hooks.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use WP_Post;
use Tribe__Repository;
use Exception;
use TEC\Tickets\Commerce\Status\Status_Handler;

/**
 * Square Hooks class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Hooks extends Controller_Contract {
	/**
	 * Gateway instance.
	 *
	 * @since TBD
	 *
	 * @var Gateway
	 */
	private Gateway $gateway;

	/**
	 * Ajax constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container Container instance.
	 * @param Gateway   $gateway Gateway instance.
	 */
	public function __construct( Container $container, Gateway $gateway ) {
		parent::__construct( $container );
		$this->gateway = $gateway;
	}

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );
		add_filter( 'tec_repository_schema_tc_orders', [ $this, 'filter_orders_repository_schema' ], 10, 2 );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );
		remove_filter( 'tec_repository_schema_tc_orders', [ $this, 'filter_orders_repository_schema' ], 10 );
	}

	/**
	 * Filter the Commerce Gateways to add Square.
	 *
	 * @since TBD
	 *
	 * @param array             $schema     The schema.
	 * @param Tribe__Repository $repository The repository.
	 *
	 * @return array
	 */
	public function filter_orders_repository_schema( array $schema = [], ?Tribe__Repository $repository = null ) {
		return tribe( Order::class )->filter_schema( $schema, $repository );
	}

	/**
	 * Filter the Commerce Gateways to add Square.
	 *
	 * @since TBD
	 *
	 * @param array $gateways List of gateways.
	 *
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		$gateways[ Gateway::get_key() ] = $this->gateway;

		return $gateways;
	}

	/**
	 * Process the async square webhook.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 * @param int $retry    The number of times this has been tried.
	 *
	 * @throws Exception If the action fails after too many retries.
	 */
	public function process_async_webhook( int $order_id, int $retry = 0 ): void {
		$order = tec_tc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( ! $order instanceof WP_Post ) {
			return;
		}

		if ( ! $order->ID ) {
			return;
		}

		$webhooks = tribe( Webhooks::class );

		if ( time() < $order->on_checkout_hold ) {
			if ( $retry > $webhooks->get_max_number_of_retries() ) {
				throw new Exception( __( 'Failed to process the webhook after too many tries.', 'event-tickets' ) );
			}

			as_schedule_single_action(
				$order->on_checkout_hold + MINUTE_IN_SECONDS,
				'tec_tickets_commerce_async_webhook_process',
				[
					'order_id' => $order_id,
					'try'      => ++$retry,
				],
				'tec-tickets-commerce-webhooks'
			);
			return;
		}

		$pending_webhooks = $webhooks->get_pending_webhooks( $order->ID );

		// On multiple checkout completes, make sure we don't process the same webhook twice.
		$webhooks->delete_pending_webhooks( $order->ID );

		foreach ( $pending_webhooks as $pending_webhook ) {
			if ( ! ( is_array( $pending_webhook ) ) ) {
				continue;
			}

			if ( ! isset( $pending_webhook['new_status'], $pending_webhook['metadata'], $pending_webhook['old_status'] ) ) {
				continue;
			}

			$new_status_wp_slug = $pending_webhook['new_status'];

			// The order is already there!
			if ( $order->post_status === $new_status_wp_slug ) {
				continue;
			}

			// The order is no longer where it was... that could be dangerous, lets bail?
			if ( $order->post_status !== $pending_webhook['old_status'] ) {
				continue;
			}

			tribe( Order::class )->modify_status(
				$order->ID,
				tribe( Status_Handler::class )->get_by_wp_slug( $new_status_wp_slug )->get_slug(),
				$pending_webhook['metadata']
			);
		}
	}
}
