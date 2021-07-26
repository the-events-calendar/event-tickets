<?php
/**
 * Models an Tickets Commerce Orders.
 *
 * @since    TBD
 *
 * @package  TEC\Tickets\Commerce\Models
 */

namespace TEC\Tickets\Commerce\Models;

use DateInterval;
use DatePeriod;
use DateTimeZone;
use Tribe\Events\Collections\Lazy_Post_Collection;
use Tribe\Models\Post_Types\Base;
use TEC\Tickets\Commerce\Order as Order_Manager;
use Tribe\Utils\Lazy_Collection;
use Tribe\Utils\Lazy_String;
use Tribe\Utils\Post_Thumbnail;
use Tribe__Date_Utils as Dates;
use Tribe__Events__Featured_Events as Featured;
use Tribe__Events__Organizer as Organizer;
use Tribe__Events__Timezones as Timezones;
use Tribe__Events__Venue as Venue;

/**
 * Class Order.
 *
 * @since    TBD
 *
 * @package  TEC\Tickets\Commerce\Models
 */
class Order extends Base {


	/**
	 * {@inheritDoc}
	 */
	protected function build_properties( $filter ) {
		try {
			$cache_this = $this->get_caching_callback( $filter );

			$post_id = $this->post->ID;

			$post_meta = get_post_meta( $post_id );

			$cart_items  = isset( $post_meta[ Order_Manager::$cart_items_meta_key ][0] ) ? maybe_unserialize( $post_meta[ Order_Manager::$cart_items_meta_key ][0] ) : null;
			$total_value = isset( $post_meta[ Order_Manager::$total_value_meta_key ][0] ) ? $post_meta[ Order_Manager::$total_value_meta_key ][0] : null;
			$currency    = isset( $post_meta[ Order_Manager::$currency_meta_key ][0] ) ? $post_meta[ Order_Manager::$currency_meta_key ][0] : null;

			$purchaser_full_name  = isset( $post_meta[ Order_Manager::$purchaser_full_name_meta_key ][0] ) ? $post_meta[ Order_Manager::$purchaser_full_name_meta_key ][0] : null;
			$purchaser_first_name = isset( $post_meta[ Order_Manager::$purchaser_first_name_meta_key ][0] ) ? $post_meta[ Order_Manager::$purchaser_first_name_meta_key ][0] : null;
			$purchaser_last_name  = isset( $post_meta[ Order_Manager::$purchaser_last_name_meta_key ][0] ) ? $post_meta[ Order_Manager::$purchaser_last_name_meta_key ][0] : null;
			$purchaser_email      = isset( $post_meta[ Order_Manager::$purchaser_email_meta_key ][0] ) ? $post_meta[ Order_Manager::$purchaser_email_meta_key ][0] : null;

			$properties = [
				'total_value' => $total_value,
				'currency'    => $currency,
				'purchaser'   => [
					'first_name' => $purchaser_first_name,
					'last_name'  => $purchaser_last_name,
					'full_name'  => $purchaser_full_name,
					'email'      => $purchaser_email,
				],
				'cart_items'  => $cart_items,

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
