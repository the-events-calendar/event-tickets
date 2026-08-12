<?php
/**
 * Square Generic Hooks.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use WP_Post;
use Tribe__Repository;
use Exception;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Models\Webhook as Webhook_Model;
use TEC\Tickets\Commerce\Order as Commerce_Order;

/**
 * Square Hooks class.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Hooks extends Controller_Contract {
	/**
	 * Gateway instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Gateway
	 */
	private Gateway $gateway;

	/**
	 * Ajax constructor.
	 *
	 * @since 5.24.0
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
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );
		add_filter( 'tec_repository_schema_tc_orders', [ $this, 'filter_orders_repository_schema' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_order_square_get_value_refunded', [ $this, 'filter_order_get_value_refunded' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_success_page_should_display_billing_fields', [ $this, 'filter_display_billing_fields' ], 20 );
		add_filter( 'tec_tickets_commerce_order_has_pending_non_completed_transition', [ $this, 'filter_order_has_pending_non_completed_transition' ], 10, 2 );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );
		remove_filter( 'tec_repository_schema_tc_orders', [ $this, 'filter_orders_repository_schema' ] );
		remove_filter( 'tec_tickets_commerce_order_square_get_value_refunded', [ $this, 'filter_order_get_value_refunded' ] );
		remove_filter( 'tec_tickets_commerce_success_page_should_display_billing_fields', [ $this, 'filter_display_billing_fields' ], 20 );
		remove_filter( 'tec_tickets_commerce_order_has_pending_non_completed_transition', [ $this, 'filter_order_has_pending_non_completed_transition' ] );
	}

	/**
	 * Filter the Commerce Gateways to add Square.
	 *
	 * @since 5.24.0
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
	 * Filter the display of the billing fields.
	 *
	 * @since 5.25.1.1
	 *
	 * @param bool $value Whether the billing fields should be displayed.
	 *
	 * @return bool
	 */
	public function filter_display_billing_fields( bool $value ): bool {
		if ( $value ) {
			return $value;
		}

		return $this->gateway->is_enabled();
	}

	/**
	 * Filter the Commerce Gateways to add Square.
	 *
	 * @since 5.24.0
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
	 * Reports whether a Square order has a deferred webhook queued that would move it away from a
	 * completed status - e.g. a refund received while the order is still within its post-checkout hold
	 * window (see TEC\Tickets\Commerce\Order::has_on_checkout_screen_hold()).
	 *
	 * @since 5.29.2
	 *
	 * @param bool $has_pending Whether another callback already found a pending non-completed transition.
	 * @param int  $order_id    The order ID.
	 *
	 * @return bool
	 */
	public function filter_order_has_pending_non_completed_transition( $has_pending, int $order_id ): bool {
		if ( $has_pending ) {
			return $has_pending;
		}

		if ( ! tribe( Commerce_Order::class )->has_on_checkout_screen_hold( $order_id ) ) {
			return false;
		}

		$pending_webhooks = tribe( Webhooks::class )->get_pending_webhooks( $order_id );

		if ( ! $pending_webhooks ) {
			return false;
		}

		$completed_wp_slug = tribe( Status_Handler::class )->get_by_slug( Completed::SLUG )->get_wp_slug();

		foreach ( $pending_webhooks as $pending_webhook ) {
			if ( is_array( $pending_webhook ) && ! empty( $pending_webhook['new_status'] ) && $completed_wp_slug !== $pending_webhook['new_status'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process the async square webhook.
	 *
	 * @since 5.24.0
	 *
	 * @param int $order_id The order ID.
	 * @param int $retry    The number of times this has been tried.
	 *
	 * @throws Exception If the action fails after too many retries.
	 */
	public function process_async_webhook( int $order_id, int $retry = 0 ): void {
		$order = tec_tc_get_order( $order_id, OBJECT, 'raw', true );

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

		// Flag the order so that attendee archive triggered by the async webhook resolution
		// (via modify_status below) can be distinguished from post-event refund archives.
		// The flag is checked by TEC\Tickets\Hooks::uncheckin_attendee_on_archive() and deleted
		// after all pending webhooks are processed.
		update_post_meta( $order->ID, '_tec_tickets_commerce_webhook_resolving_archive', 1 );

		try {
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

				$event_id = $pending_webhook['metadata']['event_id'] ?? '';

				if ( $event_id ) {
					Webhook_Model::update(
						[
							'event_id'     => $event_id,
							'order_id'     => $order->ID,
							'processed_at' => current_time( 'mysql' ),
						]
					);
				}

				tribe( Commerce_Order::class )->modify_status(
					$order->ID,
					tribe( Status_Handler::class )->get_by_wp_slug( $new_status_wp_slug )->get_slug(),
					$pending_webhook['metadata']
				);
			}
		} finally {
			delete_post_meta( $order->ID, '_tec_tickets_commerce_webhook_resolving_archive' );
		}
	}

	/**
	 * Filter the refunded amount for the order.
	 *
	 * @since 5.24.0
	 *
	 * @param ?int  $nothing The current value.
	 * @param array $refunds The refunds for the order.
	 *
	 * @return int
	 */
	public function filter_order_get_value_refunded( ?int $nothing, array $refunds ): int {
		if ( $nothing ) {
			return $nothing;
		}

		$data = [];

		foreach ( $refunds as $refund ) {
			if ( empty( $refund['data']['object']['refund']['id'] ) || empty( $refund['data']['object']['refund']['amount_money']['amount'] ) ) {
				continue;
			}

			$data[ $refund['data']['object']['refund']['id'] ] = $refund['data']['object']['refund']['amount_money']['amount'];
		}

		return (int) array_sum( $data );
	}
}
