<?php
/**
 * Models an Tickets Commerce Ticket.
 *
 * @since    5.1.9
 *
 * @package  TEC\Tickets\Commerce\Models
 */

namespace TEC\Tickets\Commerce\Models;

use DateInterval;
use DatePeriod;
use DateTimeZone;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Models\Post_Types\Base;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Utils\Lazy_Collection;
use Tribe\Utils\Lazy_String;
use Tribe\Utils\Post_Thumbnail;
use Tribe__Date_Utils as Dates;
use Tribe__Utils__Array as Arr;

/**
 * Class Attendee.
 *
 * @since    5.1.9
 *
 * @package  TEC\Tickets\Commerce\Models
 */
class Ticket_Model extends Base {

	/**
	 * {@inheritDoc}
	 */
	protected function build_properties( $filter ) {
		try {
			$cache_this = $this->get_caching_callback( $filter );

			$post_id = $this->post->ID;

			$post_meta = get_post_meta( $post_id );

//			$total_value = isset( $post_meta[ Ticket::$total_value_meta_key ][0] ) ? $post_meta[ Ticket::$total_value_meta_key ][0] : null;

			$properties = [
				'price'      => Arr::get( $post_meta, [ Ticket::$price_meta_key, 0 ] ),
				'sale_price' => get_post_meta( $post_id, Ticket::$sale_price_key, true ),
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
		return 'tc_tickets';
	}

	/**
	 * Returns the Value object representing this Ticket price.
	 *
	 * @since 5.2.3
	 * @deprecated 5.9.0 Use get_price_value | get_sale_price_value instead.
	 *
	 * @return Value
	 */
	public function get_value() {
		_deprecated_function( __METHOD__, '5.9.0', 'get_price_value' );
		$props = $this->get_properties( 'raw' );

		return Value::create( $props['price'] );
	}

	/**
	 * Returns the Value object representing this Ticket price.
	 *
	 * @since 5.9.0
	 *
	 * @return Value
	 */
	public function get_price_value() {
		$props = $this->get_properties( 'raw' );
		return Value::create( $props['price'] );
	}

	/**
	 * Returns the Value object representing this Ticket sale price.
	 *
	 * @since 5.9.0
	 *
	 * @return Value
	 */
	public function get_sale_price_value() {
		$props = $this->get_properties( 'raw' );
		return $props['sale_price'] instanceof Value ? $props['sale_price'] : Value::create( $props['sale_price'] );
	}
}
