<?php
/**
 * Remote objects.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Event_Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Inventory_Change;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Ticket_Item;
use TEC\Tickets\Commerce\Gateways\Square\Order as Square_Order;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Commerce\Ticket as Ticket_Data;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\NoChangeNeededException;
use InvalidArgumentException;
use TEC\Tickets\Commerce\Utils\Value;
use WP_Post;

/**
 * Remote objects.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Remote_Objects {
	/**
	 * The Square date time format.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const SQUARE_DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.v\Z';

	/**
	 * The order.
	 *
	 * @since 5.24.0
	 *
	 * @var Square_Order
	 */
	private Square_Order $square_order;

	/**
	 * The ticket data.
	 *
	 * @since 5.24.0
	 *
	 * @var Ticket_Data
	 */
	private Ticket_Data $ticket_data;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Square_Order $square_order The order.
	 * @param Ticket_Data  $ticket_data  The ticket data.
	 */
	public function __construct( Square_Order $square_order, Ticket_Data $ticket_data ) {
		$this->square_order = $square_order;
		$this->ticket_data  = $ticket_data;
	}

	/**
	 * Transform the batch.
	 *
	 * @since 5.24.0
	 *
	 * @param array $batch The batch.
	 *
	 * @return array The transformed batch.
	 */
	public function transform( array $batch ): array {
		$transformed = [];

		foreach ( $batch as $post_id => $tickets ) {
			$transformed[] = new Event_Item( $post_id, $tickets );
		}

		return $transformed;
	}

	/**
	 * Transform the batch.
	 *
	 * @since 5.24.0
	 *
	 * @param array $batch The batch.
	 *
	 * @return array The transformed batch.
	 */
	public function transform_batch( array $batch ): array {
		$transformed = $this->transform( $batch );

		$batches = [];

		$cache = tribe_cache();

		$cache_key = 'square_items_sync_discarded_objects';

		$discarded = [];

		foreach ( $transformed as $event_item ) {
			if ( ! $event_item->needs_sync() ) {
				$discarded[] = $event_item->get_wp_id();
				continue;
			}

			$batches[] = [
				'objects' => [ $event_item ],
			];
		}

		$cache[ $cache_key ] = $discarded;

		return $batches;
	}

	/**
	 * Transform the inventory batch.
	 *
	 * @since 5.24.0
	 *
	 * @param array $batch The batch.
	 *
	 * @return array The transformed batch.
	 */
	public function transform_inventory_batch( array $batch ): array {
		$location_ids = $this->get_location_ids();

		if ( empty( $location_ids ) ) {
			return [];
		}

		$cache = tribe_cache();

		$cache_key = 'square_inventory_sync_discarded_objects';

		$transformed = [];
		$discarded   = [];

		foreach ( $batch as $post_id => $tickets ) {
			foreach ( $tickets as $ticket ) {
				foreach ( $location_ids as $location_id ) {
					try {
						$change = new Inventory_Change( 'ADJUSTMENT', new Ticket_Item( $ticket ), [ 'location_id' => $location_id ] );
					} catch ( NoChangeNeededException $e ) {
						if ( ! isset( $discarded[ $post_id ] ) ) {
							$discarded[ $post_id ] = [];
						}

						$discarded[ $post_id ][] = $ticket;
						continue;
					}

					$transformed[] = $change;
				}
			}
		}

		$cache[ $cache_key ] = $discarded;

		return $transformed;
	}

	/**
	 * Delete the remote object.
	 *
	 * @since 5.24.0
	 *
	 * @param int    $object_id        The object ID.
	 * @param string $remote_object_id The remote object ID.
	 *
	 * @return void
	 * @throws InvalidArgumentException If no event ID or remote object ID is provided.
	 */
	public function delete( int $object_id = 0, string $remote_object_id = '' ): void {
		if ( ! $object_id && ! $remote_object_id ) {
			throw new InvalidArgumentException( 'Either event ID or remote object ID must be provided' );
		}

		if ( $object_id ) {
			$remote_object_id = $this->delete_remote_object_data( $object_id );
		}

		if ( ! $remote_object_id ) {
			// Was not synced yet.
			return;
		}

		$response = Requests::delete( sprintf( 'catalog/object/%s', $remote_object_id ) );

		if ( ! empty( $response['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Delete event', $response['errors'] );
		}
	}

	/**
	 * Delete the remote object data.
	 *
	 * @since 5.24.0
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return string The remote object ID.
	 */
	public function delete_remote_object_data( int $object_id ): string {
		// Careful! We store it in local var and then we delete it!
		$remote_object_id = Item::get_remote_object_id( $object_id );
		Item::delete( $object_id );

		$is_ticket = in_array( get_post_type( $object_id ), tribe_tickets()->ticket_types(), true );

		if ( $is_ticket ) {
			return $remote_object_id;
		}

		foreach ( $this->ticket_data->get_posts_tickets( $object_id ) as $ticket ) {
			Item::delete( $ticket->ID );
		}

		return $remote_object_id;
	}

	/**
	 * Cache the remote object state.
	 *
	 * @since 5.24.0
	 *
	 * @param array $batch The batch.
	 *
	 * @return string The location ID.
	 */
	public function cache_remote_object_state( array $batch ): string {
		$cache = tribe_cache();

		$location_ids = $this->get_location_ids();

		$location_id = $location_ids[0] ?? false;

		if ( ! $location_id ) {
			return '';
		}

		$data = [
			'location_ids'       => $location_ids,
			'catalog_object_ids' => [],
			'states'             => [ 'IN_STOCK' ],
		];

		$cache_template_key = 'square_sync_object_state_%s_%s';

		foreach ( $batch as $tickets ) {
			foreach ( $tickets as $ticket ) {
				$ticket_item       = new Ticket_Item( $ticket );
				$catalog_object_id = $ticket_item->get_id();

				$cache_key = sprintf( $cache_template_key, $catalog_object_id, $location_id );

				if ( ! empty( $cache->get( $cache_key ) ) && is_array( $cache->get( $cache_key ) ) ) {
					continue;
				}

				$data['catalog_object_ids'][] = $catalog_object_id;
			}
		}

		if ( empty( $data['catalog_object_ids'] ) ) {
			// Everything is cached already.
			return $location_id;
		}

		$args = [
			'body'    => $data,
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		$response = Requests::post(
			'inventory/counts/batch-retrieve',
			[],
			$args
		);

		if ( ! empty( $response['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Inventory Sync', $response['errors'] );
		}

		if ( empty( $response['counts'] ) ) {
			return $location_id;
		}

		foreach ( $response['counts'] as $count ) {
			$cache_key = sprintf( $cache_template_key, $count['catalog_object_id'], $count['location_id'] );
			$cache->set(
				$cache_key,
				[
					'quantity' => $count['quantity'],
					'state'    => $count['state'],
				],
				MINUTE_IN_SECONDS * 2
			);
		}

		return $location_id;
	}

	/**
	 * Get the line item.
	 *
	 * @since 5.24.0
	 *
	 * @param array   $item  The item.
	 * @param WP_Post $order The order post object.
	 *
	 * @return array The line item.
	 */
	public function get_line_item( array $item, WP_Post $order ): array {
		$ticket_id = $item['ticket_id'] ?? false;

		if ( ! $ticket_id ) {
			return [];
		}

		$ticket = $this->square_order->get_cached_remote_data( $order->ID, $ticket_id );

		unset(
			$ticket['variation_total_price_money'],
			$ticket['gross_sales_money'],
			$ticket['total_tax_money'],
			$ticket['total_discount_money'],
			$ticket['total_money'],
			$ticket['total_service_charge_money'],
		);

		$ticket_object = $this->ticket_data->load_ticket_object( $ticket_id );

		if ( ! $ticket_object ) {
			// We can't do anything for a ticket we can't load anymore. Probably it was deleted...
			return $ticket;
		}

		$remote_object_id = Item::get_remote_object_id( $ticket_id );

		$updates = [
			'note'             => $ticket_object->get_event()->post_title . ' - ' . $ticket_object->name,
			'quantity'         => (string) ( $item['quantity'] ?? 1 ),
			'item_type'        => $remote_object_id ? 'ITEM' : 'CUSTOM_AMOUNT',
			'metadata'         => [
				'local_id' => (string) $ticket_id,
			],
			'base_price_money' => [
				'amount'   => absint( 100 * $item['price'] ),
				'currency' => $order->currency,
			],
		];

		if ( $remote_object_id ) {
			$updates['catalog_object_id'] = $remote_object_id;
			$updates['name']              = $ticket_object->get_event()->post_title;
			$updates['variation_name']    = $ticket_object->name;
			unset( $updates['note'] );
		}

		return array_merge( $ticket, $updates );
	}

	/**
	 * Get the discount.
	 *
	 * @since 5.24.0
	 *
	 * @param array   $item  The item.
	 * @param WP_Post $order The order post object.
	 *
	 * @return array The discount.
	 */
	public function get_discount( array $item, WP_Post $order ): array {
		$id = $item['id'] ?? false;

		if ( ! $id ) {
			return [];
		}

		$discount = $this->square_order->get_cached_remote_data( $order->ID, $id, 'discounts' );

		// Remove possible READ-ONLY properties.
		unset(
			$discount['reward_ids'],
			$discount['pricing_rule_id'],
		);

		if ( $item['sub_total'] instanceof Value ) {
			$item['sub_total'] = $item['sub_total']->get_float();
		}

		$updates = [
			'name'         => $item['display_name'],
			'type'         => 'FIXED_AMOUNT',
			'scope'        => 'ORDER',
			'amount_money' => [
				'amount'   => absint( 100 * $item['sub_total'] ),
				'currency' => $order->currency,
			],
			'metadata'     => [
				'local_id' => (string) $id,
			],
		];

		return array_merge( $discount, $updates );
	}

	/**
	 * Get the service charge.
	 *
	 * @since 5.24.0
	 *
	 * @param array   $item  The item.
	 * @param WP_Post $order The order post object.
	 *
	 * @return array The service charge.
	 */
	public function get_service_charge( array $item, WP_Post $order ): array {
		$id = $item['id'] ?? false;

		if ( ! $id ) {
			return [];
		}

		$charge = $this->square_order->get_cached_remote_data( $order->ID, $id, 'service_charges' );

		// Remove possible READ-ONLY properties.
		unset(
			$charge['applied_money'],
			$charge['total_money'],
			$charge['total_tax_money'],
			$charge['type'],
		);

		if ( $item['sub_total'] instanceof Value ) {
			$item['sub_total'] = $item['sub_total']->get_float();
		}

		$updates = [
			'name'              => $item['display_name'],
			'calculation_phase' => 'SUBTOTAL_PHASE',
			'amount_money'      => [
				'amount'   => absint( 100 * $item['sub_total'] ),
				'currency' => $order->currency,
			],
			'metadata'          => [
				'local_id' => (string) $id,
			],
		];

		return array_merge( $charge, $updates );
	}

	/**
	 * Get the customer ID.
	 *
	 * @since 5.24.0
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return string The customer ID.
	 */
	public function get_customer_id( int $order_id ): string {
		$cache = tribe_cache();

		$cache_key = 'tec_tickets_commerce_square_customer_id_' . $order_id;

		if ( ! empty( $cache[ $cache_key ] ) && is_string( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		if ( is_user_logged_in() ) {
			$customer_id = (string) Commerce_Meta::get( get_current_user_id(), '_tec_tickets_commerce_gateways_square_customer_id_%s', [], 'user' );

			if ( $customer_id ) {
				$cache[ $cache_key ] = $customer_id;
				Commerce_Meta::set( $order_id, Commerce_Order::GATEWAY_CUSTOMER_ID_META_KEY, $customer_id, [], 'post', false );

				return $customer_id;
			}
		}

		$customer_id = (string) Commerce_Meta::get( $order_id, Commerce_Order::GATEWAY_CUSTOMER_ID_META_KEY, [], 'post', true, false );

		if ( $customer_id ) {
			$cache[ $cache_key ] = $customer_id;

			return $customer_id;
		}

		$email = (string) get_post_meta( $order_id, Commerce_Order::$purchaser_email_meta_key, true );

		if ( ! ( $email && is_email( $email ) ) ) {
			// We can'd do a lot without a mail!
			return '';
		}

		// Search for a customer first.
		$results = Requests::post(
			'customers/search',
			[],
			[
				'body' => [
					'limit' => 1,
					'query' => [
						'filter' => [
							'email_address' => [ 'exact' => $email ],
						],
					],
				],
			]
		);

		if ( ! empty( $results['customers'][0]['id'] ) ) {
			$customer_id         = $results['customers'][0]['id'];
			$cache[ $cache_key ] = $customer_id;

			Commerce_Meta::set( $order_id, Commerce_Order::GATEWAY_CUSTOMER_ID_META_KEY, $customer_id, [], 'post', false );

			if ( is_user_logged_in() ) {
				Commerce_Meta::set( get_current_user_id(), '_tec_tickets_commerce_gateways_square_customer_id_%s', $customer_id, [], 'user' );
			}

			return $customer_id;
		}

		// Search failed, now create the customer.
		$response = Requests::post(
			'customers',
			[],
			[
				'body' => [
					'idempotency_key' => 'creating_customer_for_order_' . $order_id,
					'email_address'   => $email,
					'given_name'      => get_post_meta( $order_id, Commerce_Order::$purchaser_first_name_meta_key, true ),
					'family_name'     => get_post_meta( $order_id, Commerce_Order::$purchaser_last_name_meta_key, true ),
				],
			]
		);

		if ( empty( $response['customer']['id'] ) ) {
			return '';
		}

		$customer_id         = $response['customer']['id'];
		$cache[ $cache_key ] = $customer_id;

		Commerce_Meta::set( $order_id, Commerce_Order::GATEWAY_CUSTOMER_ID_META_KEY, $customer_id, [], 'post', false );

		if ( is_user_logged_in() ) {
			Commerce_Meta::set( get_current_user_id(), '_tec_tickets_commerce_gateways_square_customer_id_%s', $customer_id, [], 'user' );
		}

		return $customer_id;
	}

	/**
	 * Get the location IDs.
	 *
	 * @since 5.24.0
	 *
	 * @return array The location IDs.
	 */
	protected function get_location_ids(): array {
		$merchant = tribe( Merchant::class );

		return array_filter(
			[
				$merchant->get_location_id(),
			]
		);
	}
}
