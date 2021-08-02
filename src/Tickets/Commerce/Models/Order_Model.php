<?php
/**
 * Models an Tickets Commerce Orders.
 *
 * @since    TBD
 *
 * @package  TEC\Tickets\Commerce\Models
 */

namespace TEC\Tickets\Commerce\Models;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use Tribe\Models\Post_Types\Base;
use Tribe\Utils\Lazy_Collection;
use Tribe\Utils\Lazy_String;
use Tribe\Utils\Post_Thumbnail;
use Tribe__Date_Utils as Dates;
use Tribe__Utils__Array as Arr;

/**
 * Class Order.
 *
 * @since    TBD
 *
 * @package  TEC\Tickets\Commerce\Models
 */
class Order_Model extends Base {

	/**
	 * {@inheritDoc}
	 */
	protected function build_properties( $filter ) {
		try {
			$cache_this = $this->get_caching_callback( $filter );

			$post_id = $this->post->ID;

			$post_meta = get_post_meta( $post_id );

			$cart_items       = maybe_unserialize( Arr::get( $post_meta, [ Order::$cart_items_meta_key, 0 ] ) );
			$total_value      = Arr::get( $post_meta, [ Order::$total_value_meta_key, 0 ] );
			$currency         = Arr::get( $post_meta, [ Order::$currency_meta_key, 0 ] );
			$gateway_slug     = Arr::get( $post_meta, [ Order::$gateway_meta_key, 0 ] );
			$gateway_order_id = Arr::get( $post_meta, [ Order::$gateway_order_id_meta_key, 0 ] );
			$gateway_payload  = Arr::get( $post_meta, [ Order::$gateway_payload_meta_key, 0 ] );

			$purchaser_full_name  = Arr::get( $post_meta, [ Order::$purchaser_full_name_meta_key, 0 ] );
			$purchaser_first_name = Arr::get( $post_meta, [ Order::$purchaser_first_name_meta_key, 0 ] );
			$purchaser_last_name  = Arr::get( $post_meta, [ Order::$purchaser_last_name_meta_key, 0 ] );
			$purchaser_email      = Arr::get( $post_meta, [ Order::$purchaser_email_meta_key, 0 ] );

			$events_in_order  = Arr::get( $post_meta, [ Order::$events_in_order_meta_key ] );
			$tickets_in_order = Arr::get( $post_meta, [ Order::$tickets_in_order_meta_key ] );

			$properties = [
				'provider'         => Module::class,
				'provider_slug'    => Commerce::ABBR,
				'gateway'          => $gateway_slug,
				'gateway_order_id' => $gateway_order_id,
				'gateway_payload'  => $gateway_payload,
				'total_value'      => $total_value,
				'currency'         => $currency,
				'purchaser'        => [
					'first_name' => $purchaser_first_name,
					'last_name'  => $purchaser_last_name,
					'full_name'  => $purchaser_full_name,
					'email'      => $purchaser_email,
				],
				'cart_items'       => $cart_items,
				'events_in_order'  => $events_in_order,
				'tickets_in_order' => $tickets_in_order,
			];
		} catch ( \Exception $e ) {
			return [];
		}

		return $properties;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_cache_slug() {
		return 'tc_orders';
	}

}
