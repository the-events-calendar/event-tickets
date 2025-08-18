<?php
/**
 * Models a Ticket.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\Models\Post_Types
 */

namespace TEC\Tickets\Models\Post_Types;

use TEC\Tickets\Commerce\Ticket as Ticket_CPT;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Models\Post_Types\Base;
use Tribe\Utils\Lazy_String;
use Tribe\Utils\Post_Thumbnail;
use Tribe__Date_Utils as Dates;
use Tribe__Utils__Array as Arr;

/**
 * Class Ticket
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\Models\Post_Types
 */
class Ticket extends Base {

	/**
	 * {@inheritDoc}
	 */
	protected function build_properties( $filter ) {
		try {
			$cache_this = $this->get_caching_callback( $filter );

			$post_id   = $this->post->ID;
			$post_meta = get_post_meta( $post_id );

			// Get ticket-specific meta data.
			$price            = Arr::get( $post_meta, [ Ticket_CPT::$price_meta_key, 0 ], 0 );
			$sale_price       = Arr::get( $post_meta, [ '_sale_price', 0 ], null );
			$stock            = Arr::get( $post_meta, [ Ticket_CPT::$stock_meta_key, 0 ], null );
			$stock_mode       = Arr::get( $post_meta, [ Ticket_CPT::$stock_mode_meta_key, 0 ], null );
			$capacity         = Arr::get( $post_meta, [ '_capacity', 0 ], null );
			$sales            = Arr::get( $post_meta, [ Ticket_CPT::$sales_meta_key, 0 ], 0 );
			$sku              = Arr::get( $post_meta, [ Ticket_CPT::$sku_meta_key, 0 ], '' );
			$show_description = Arr::get( $post_meta, [ Ticket_CPT::$show_description_meta_key, 0 ], 'yes' );
			$event_id         = Arr::get( $post_meta, [ Ticket_CPT::$event_relation_meta_key, 0 ], null );

			// Get sale dates.
			$start_date = Arr::get( $post_meta, [ '_ticket_start_date', 0 ], null );
			$start_time = Arr::get( $post_meta, [ '_ticket_start_time', 0 ], null );
			$end_date   = Arr::get( $post_meta, [ '_ticket_end_date', 0 ], null );
			$end_time   = Arr::get( $post_meta, [ '_ticket_end_time', 0 ], null );

			// Combine date and time for sale start/end.
			$sale_start = null;
			$sale_end   = null;

			if ( $start_date ) {
				$sale_start = $start_time ? $start_date . ' ' . $start_time : $start_date . ' 00:00:00';
			}

			if ( $end_date ) {
				$sale_end = $end_time ? $end_date . ' ' . $end_time : $end_date . ' 23:59:59';
			}

			// Calculate availability.
			$now                  = Dates::build_date_object( 'now' );
			$is_available         = true;
			$availability_message = '';

			if ( $sale_start && Dates::build_date_object( $sale_start ) > $now ) {
				$is_available         = false;
				$availability_message = __( 'Ticket sales have not started yet', 'event-tickets' );
			} elseif ( $sale_end && Dates::build_date_object( $sale_end ) < $now ) {
				$is_available         = false;
				$availability_message = __( 'Ticket sales have ended', 'event-tickets' );
			} elseif ( $stock !== null && $stock <= 0 ) {
				$is_available         = false;
				$availability_message = __( 'Sold out', 'event-tickets' );
			}

			// Build properties array.
			$properties = [
				'price'                => $price,
				'sale_price'           => $sale_price,
				'price_value'          => Value::create( $price ),
				'sale_price_value'     => $sale_price ? Value::create( $sale_price ) : null,
				'formatted_price'      => ( new Lazy_String(
					static function () use ( $price ) {
						return Value::create( $price )->get_currency();
					},
					false
				) )->on_resolve( $cache_this ),
				'formatted_sale_price' => $sale_price ? ( new Lazy_String(
					static function () use ( $sale_price ) {
						return Value::create( $sale_price )->get_currency();
					},
					false
				) )->on_resolve( $cache_this ) : null,
				'stock'                => $stock,
				'stock_mode'           => $stock_mode,
				'capacity'             => $capacity,
				'sales'                => (int) $sales,
				'sku'                  => $sku,
				'show_description'     => tribe_is_truthy( $show_description ),
				'event_id'             => $event_id ? (int) $event_id : null,
				'sale_start'           => $sale_start,
				'sale_end'             => $sale_end,
				'is_available'         => $is_available,
				'availability_message' => $availability_message,
				'thumbnail'            => ( new Post_Thumbnail( $post_id ) )->on_resolve( $cache_this ),
				'permalink'            => ( new Lazy_String(
					static function () use ( $post_id ) {
						$permalink = get_permalink( $post_id );
						return (string) ( empty( $permalink ) ? '' : $permalink );
					},
					false
				) )->on_resolve( $cache_this ),
				'title'                => ( new Lazy_String(
					static function () use ( $post_id ) {
						$title = get_the_title( $post_id );
						return (string) ( empty( $title ) ? '' : $title );
					},
					false
				) )->on_resolve( $cache_this ),
				'description'          => ( new Lazy_String(
					static function () use ( $post_id ) {
						return get_post_field( 'post_content', $post_id );
					},
					false
				) )->on_resolve( $cache_this ),
				'excerpt'              => ( new Lazy_String(
					static function () use ( $post_id ) {
						return get_post_field( 'post_excerpt', $post_id );
					},
					false
				) )->on_resolve( $cache_this ),
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
	 * Returns the properties to add to the ticket.
	 *
	 * @since 5.26.0
	 *
	 * @return array<string,bool>
	 */
	public static function get_properties_to_add(): array {
		/**
		 * Filters the properties to add to the ticket.
		 *
		 * @since 5.26.0
		 *
		 * @param array<string,bool> $properties The properties to add to the ticket.
		 *
		 * @return array<string,bool>
		 */
		return (array) apply_filters(
			'tec_rest_ticket_properties_to_add',
			[
				'price'                => true,
				'sale_price'           => true,
				'price_value'          => true,
				'sale_price_value'     => true,
				'formatted_price'      => true,
				'formatted_sale_price' => true,
				'stock'                => true,
				'stock_mode'           => true,
				'capacity'             => true,
				'sales'                => true,
				'sku'                  => true,
				'show_description'     => true,
				'event_id'             => true,
				'sale_start'           => true,
				'sale_end'             => true,
				'is_available'         => true,
				'availability_message' => true,
				'permalink'            => true,
				'description'          => true,
				'excerpt'              => true,
			]
		);
	}
}
