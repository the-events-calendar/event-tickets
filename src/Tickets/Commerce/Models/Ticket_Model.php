<?php
/**
 * Models an Tickets Commerce Ticket.
 *
 * @since 5.1.9
 *
 * @package  TEC\Tickets\Commerce\Models
 */

namespace TEC\Tickets\Commerce\Models;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Models\Post_Types\Base;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Utils__Array as Arr;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

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
			$ticket_data   = tribe( Ticket::class );
			$ticket_object = $ticket_data->get_ticket( $this->post->ID );

			$properties = [
				'description'      => $ticket_object->description,
				'name'             => $ticket_object->name,
				'on_sale'          => $ticket_object->on_sale,
				'sale_price'       => (float) $ticket_data->get_sale_price( $ticket_object->ID ),
				'price'            => (float) $ticket_object->price,
				'regular_price'    => (float) $ticket_data->get_regular_price( $ticket_object->ID ),
				'value'            => $ticket_object->value,
				'provider_class'   => $ticket_object->provider_class,
				'admin_link'       => $ticket_object->admin_link,
				'show_description' => $ticket_object->show_description,
				'start_date'       => $ticket_object->start_date,
				'end_date'         => $ticket_object->end_date,
				'start_time'       => $ticket_object->start_time,
				'end_time'         => $ticket_object->end_time,
				'manage_stock'     => $ticket_object->managing_stock(),
				'event_id'         => (int) $ticket_object->get_event_id(),
				'stock'            => $ticket_object->stock(),
			];
		} catch ( \Exception $e ) {
			return [];
		}

		return $properties;
	}

	/**
	 * Get properties to add to the model.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> Properties to add to the model.
	 */
	public static function get_properties_to_add(): array {
		$properties = [
			'description'      => true,
			'name'             => true,
			'on_sale'          => true,
			'sale_price'       => true,
			'price'            => true,
			'regular_price'    => true,
			'value'            => true,
			'provider_class'   => true,
			'admin_link'       => true,
			'show_description' => true,
			'start_date'       => true,
			'end_date'         => true,
			'start_time'       => true,
			'end_time'         => true,
			'manage_stock'     => true,
			'event_id'         => true,
			'stock'            => true,
		];

		/**
		 * Filters the properties to add to a ticket model in the REST API.
		 *
		 * @since TBD
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
