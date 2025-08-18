<?php
/**
 * Models an Tickets Commerce Ticket.
 *
 * @since 5.1.9
 *
 * @package  TEC\Tickets\Commerce\Models
 */

namespace TEC\Tickets\Commerce\Models;

use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Models\Post_Types\Base;
use TEC\Tickets\Commerce\Ticket;

/**
 * Class Attendee.
 *
 * @since 5.1.9
 *
 * @package  TEC\Tickets\Commerce\Models
 */
class Ticket_Model extends Base {

	/**
	 * {@inheritDoc}
	 */
	protected function build_properties( $filter ) {
		try {
			/**
			 * Filters the properties to add to a ticket model in the REST API.
			 *
			 * @since 5.26.0
			 *
			 * @param array<string,mixed> $properties Properties to add to the model.
			 */
			$properties = apply_filters( 'tec_tickets_pre_build_ticket_properties', [], $this->post, $filter );

			if ( ! empty( $properties ) ) {
				return $properties;
			}

			$ticket_data   = tribe( Ticket::class );
			$ticket_object = $ticket_data->get_ticket( $this->post->ID );

			$sale_start_date = get_post_meta( $ticket_object->ID, Ticket::$sale_price_start_date_key, true );
			$sale_end_date   = get_post_meta( $ticket_object->ID, Ticket::$sale_price_end_date_key, true );
			$sale_price      = get_post_meta( $ticket_object->ID, Ticket::$sale_price_key, true );

			$sale_price = $sale_price && $sale_price instanceof Value ? $sale_price->get_string() : $sale_price;
			$sale_price = $sale_price ? (float) $sale_price : null;

			$properties = [
				'description'           => $ticket_object->description,
				'on_sale'               => $ticket_object->on_sale,
				'sale_price'            => $sale_price ? (float) $sale_price : null,
				'price'                 => (float) $ticket_object->price,
				'regular_price'         => (float) $ticket_data->get_regular_price( $ticket_object->ID ),
				'show_description'      => $ticket_object->show_description,
				'start_date'            => trim( $ticket_object->start_date . ' ' . $ticket_object->start_time ),
				'end_date'              => trim( $ticket_object->end_date . ' ' . $ticket_object->end_time ),
				'sale_price_start_date' => $sale_start_date ? $sale_start_date : null,
				'sale_price_end_date'   => $sale_end_date ? $sale_end_date : null,
				'event_id'              => (int) $ticket_object->get_event_id(),
				'manage_stock'          => $ticket_object->managing_stock(),
				'stock'                 => $ticket_object->stock(),
				'sold'                  => $ticket_object->qty_sold(),
				'sku'                   => $ticket_object->sku,
			];
		} catch ( \Exception $e ) {
			return [];
		}

		return $properties;
	}

	/**
	 * Get properties to add to the model.
	 *
	 * @since 5.26.0
	 *
	 * @return array<string,mixed> Properties to add to the model.
	 */
	public static function get_properties_to_add(): array {
		$properties = [
			'description'           => true,
			'on_sale'               => true,
			'sale_price'            => true,
			'price'                 => true,
			'regular_price'         => true,
			'show_description'      => true,
			'start_date'            => true,
			'end_date'              => true,
			'sale_price_start_date' => true,
			'sale_price_end_date'   => true,
			'event_id'              => true,
			'manage_stock'          => true,
			'stock'                 => true,
			'sold'                  => true,
			'sku'                   => true,
		];

		/**
		 * Filters the properties to add to a ticket model in the REST API.
		 *
		 * @since 5.26.0
		 *
		 * @param array<string,mixed> $properties Properties to add to the model.
		 */
		return apply_filters( 'tec_rest_ticket_properties_to_add', $properties );
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
