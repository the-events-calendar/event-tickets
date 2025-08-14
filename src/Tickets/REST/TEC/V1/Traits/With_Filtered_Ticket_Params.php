<?php
/**
 * Trait to provide filtered ticket params.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use Tribe__Tickets__Global_Stock as Global_Stock;
use stdClass;
use TEC\Tickets\Commerce\Ticket;

/**
 * Trait With_Filtered_Ticket_Params.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Filtered_Ticket_Params {
	/**
	 * Filters the create params.
	 *
	 * @since TBD
	 *
	 * @param array $params The params to filter.
	 *
	 * @return array
	 *
	 * @throws InvalidRestArgumentException If the event ID is missing.
	 * @throws InvalidRestArgumentException If the event's post type is not enabled for tickets.
	 */
	public function filter_create_params( array $params ): array {
		$event_id = $params['event'] ?? null;

		if ( ! $event_id ) {
			$exception = new InvalidRestArgumentException( __( 'Event ID is required', 'event-tickets' ) );
			$exception->set_argument( 'event' );
			$exception->set_internal_error_code( 'tec_rest_invalid_event_parameter' );

			// translators: 1) is the name of the parameter.
			$exception->set_details( sprintf( __( 'The parameter `{`%1$s}` is missing.', 'event-tickets' ), 'event' ) );
			throw $exception;
		}

		if ( ! in_array( get_post_type( $event_id ), (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			$exception = new InvalidRestArgumentException( __( 'Event ID is not enabled for tickets', 'event-tickets' ) );
			$exception->set_argument( 'event' );
			$exception->set_internal_error_code( 'tec_rest_invalid_event_parameter' );

			// translators: 1) is the name of the parameter.
			$exception->set_details( sprintf( __( 'The parameter `{`%1$s}` is not enabled for tickets.', 'event-tickets' ), 'event' ) );
			throw $exception;
		}

		if ( ! empty( $params['start_date'] ) ) {
			$start_date = explode( ' ', $params['start_date'] );

			$params['ticket_start_date'] = $start_date[0];
			$params['ticket_start_time'] = $start_date[1] ?? '00:00:00';
		}

		if ( ! empty( $params['end_date'] ) ) {
			$end_date = explode( ' ', $params['end_date'] );

			$params['ticket_end_date'] = $end_date[0];
			$params['ticket_end_time'] = $end_date[1] ?? '23:59:59';
		}

		if ( isset( $params['capacity'] ) && ! isset( $params['stock'] ) ) {
			$params['stock'] = $params['capacity'];
		}

		if ( isset( $params['stock'] ) && ! isset( $params['capacity'] ) ) {
			$params['capacity'] = $params['stock'];
		}

		if ( ! isset( $params['stock_mode'] ) && ( ! empty( $params['stock'] ) || ! empty( $params['capacity'] ) ) ) {
			$params['stock_mode'] = -1 === $params['stock'] || -1 === $params['capacity'] ? 'unlimited' : Global_Stock::OWN_STOCK_MODE;
		}

		if ( 'unlimited' === $params['stock_mode'] ) {
			$params['stock_mode'] = '';
		}

		$stock       = new Global_Stock( $event_id );
		$stock_level = $stock->get_stock_level();
		$stock_level = $stock_level > 0 ? $stock_level : null;

		$ticket_post = ! empty( $params['id'] ) ? get_post( $params['id'] ) : new stdClass();
		$ticket_data = ! empty( $params['id'] ) ? get_post_meta( $params['id'] ) : [];

		/** @var \Tribe__Tickets__Tickets_Handler $ticket_handler */
		$ticket_handler = tribe( 'tickets.handler' );

		$tribe_ticket = [
			'event_capacity' => $params['event_capacity'] ?? $stock_level,
			'capacity'       => $params['capacity'] ?? $ticket_data[ $ticket_handler->key_capacity ] ?? null,
			'stock'          => $params['stock'] ?? $ticket_data[ Ticket::$stock_meta_key ] ?? null,
			'mode'           => $params['stock_mode'] ?? $ticket_data[ Ticket::$stock_mode_meta_key ] ?? null,
		];

		$new_params = [
			'ticket_id'               => $params['id'] ?? null,
			'ticket_name'             => $params['title'] ?? $ticket_post->post_title ?? null,
			'ticket_description'      => esc_html( $params['content'] ?? $params['excerpt'] ?? null ) ?? $ticket_post->post_excerpt ?? null,
			'ticket_price'            => $params['price'] ?? $ticket_data[ Ticket::$price_meta_key ] ?? null,
			'ticket_show_description' => $params['show_description'] ?? $ticket_data[ Ticket::$show_description_meta_key ] ?? null,
			'ticket_type'             => $params['type'] ?? $ticket_data[ Ticket::$type_meta_key ] ?? null,
			'ticket_sku'              => $params['sku'] ?? $ticket_data[ Ticket::$sku_meta_key ] ?? null,
			'ticket_start_date'       => $params['ticket_start_date'] ?? $ticket_data[ Ticket::START_DATE_META_KEY ] ?? null,
			'ticket_start_time'       => $params['ticket_start_time'] ?? $ticket_data[ Ticket::START_TIME_META_KEY ] ?? null,
			'ticket_end_date'         => $params['ticket_end_date'] ?? $ticket_data[ Ticket::END_DATE_META_KEY ] ?? null,
			'ticket_end_time'         => $params['ticket_end_time'] ?? $ticket_data[ Ticket::END_TIME_META_KEY ] ?? null,
			'tribe-ticket'            => array_filter( $tribe_ticket, fn( $value ) => null !== $value ),
			'ticket_add_sale_price'   => isset( $params['sale_price'] ) || ! empty( $ticket_data[ Ticket::$sale_price_checked_key ] ),
			'ticket_sale_price'       => $params['sale_price'] ?? $ticket_data[ Ticket::$sale_price_key ] ?? null,
			'ticket_sale_start_date'  => $params['sale_price_start_date'] ?? $ticket_data[ Ticket::$sale_price_start_date_key ] ?? null,
			'ticket_sale_end_date'    => $params['sale_price_end_date'] ?? $ticket_data[ Ticket::$sale_price_end_date_key ] ?? null,
		];

		return array_filter( $new_params, fn( $value ) => null !== $value );
	}

	/**
	 * Filters the update params.
	 *
	 * @since TBD
	 *
	 * @param array $params The params to filter.
	 *
	 * @return array
	 */
	public function filter_update_params( array $params ): array {
		return $this->filter_create_params( $params );
	}
}
