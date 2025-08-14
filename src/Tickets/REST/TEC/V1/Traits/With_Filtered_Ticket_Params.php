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
use TEC\Tickets\Commerce\Utils\Value;

/**
 * Trait With_Filtered_Ticket_Params.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Filtered_Ticket_Params {
	/**
	 * Filters the upsert params.
	 *
	 * @since TBD
	 *
	 * @param array $params The params to filter.
	 *
	 * @return array
	 *
	 * @throws InvalidRestArgumentException If the event ID is missing.
	 * @throws InvalidRestArgumentException If the event's post type is not enabled for tickets.
	 * @throws InvalidRestArgumentException If the ticket price is not a number.
	 */
	public function filter_upsert_params( array $params ): array {
		$ticket_post = ! empty( $params['id'] ) ? get_post( $params['id'] ) : new stdClass();
		$ticket_data = ! empty( $params['id'] ) ? get_post_meta( $params['id'] ) : [];

		if ( isset( $params['id'] ) ) {
			// We don't allow moving tickets to a different event.
			$params['event'] = (int) ( $ticket_data[ Ticket::$event_relation_meta_key ]['0'] ?? null );
		}

		$event_id = (int) ( $params['event'] ?? $ticket_data[ Ticket::$event_relation_meta_key ]['0'] ?? null );

		if ( ! $event_id ) {
			$exception = new InvalidRestArgumentException( __( 'Event ID is required', 'event-tickets' ) );
			$exception->set_argument( 'event' );
			$exception->set_internal_error_code( 'tec_rest_invalid_event_parameter' );

			// translators: 1) is the name of the parameter.
			$exception->set_details( sprintf( __( 'The parameter `{`%1$s}` is missing.', 'event-tickets' ), 'event' ) );
			throw $exception;
		}

		if ( ! in_array( get_post_type( $event_id ), (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			$exception = new InvalidRestArgumentException( __( 'The specified Event ID does not support ticket creation', 'event-tickets' ) );
			$exception->set_argument( 'event' );
			$exception->set_internal_error_code( 'tec_rest_invalid_event_parameter' );

			// translators: 1) is the name of the parameter.
			$exception->set_details( sprintf( __( 'The parameter `{`%1$s}` does not support ticket creation. Make sure its post type is enabled for tickets under Tickets > Settings > Ticket Settings > Post types that can have tickets.', 'event-tickets' ), 'event' ) );
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

		if ( isset( $params['stock_mode'] ) && 'unlimited' === $params['stock_mode'] ) {
			$params['stock_mode'] = '';
		}

		$stock       = new Global_Stock( $event_id );
		$stock_level = $stock->get_stock_level();
		$stock_level = $stock_level > 0 ? $stock_level : null;

		/** @var \Tribe__Tickets__Tickets_Handler $ticket_handler */
		$ticket_handler = tribe( 'tickets.handler' );

		$tribe_ticket = [
			'event_capacity' => $params['event_capacity'] ?? $stock_level,
			'capacity'       => $params['capacity'] ?? $ticket_data[ $ticket_handler->key_capacity ]['0'] ?? null,
			'stock'          => $params['stock'] ?? $ticket_data[ Ticket::$stock_meta_key ]['0'] ?? null,
			'mode'           => $params['stock_mode'] ?? $ticket_data[ Ticket::$stock_mode_meta_key ]['0'] ?? null,
		];

		if ( isset( $ticket_data[ Ticket::START_DATE_META_KEY ]['0'] ) ) {
			$start_date = explode( ' ', $ticket_data[ Ticket::START_DATE_META_KEY ]['0'] );

			$ticket_data[ Ticket::START_DATE_META_KEY ] = $start_date[0] ?? null;
			$ticket_data[ Ticket::START_TIME_META_KEY ] = $start_date[1] ?? '00:00:00';
		}

		if ( isset( $ticket_data[ Ticket::END_DATE_META_KEY ]['0'] ) ) {
			$end_date = explode( ' ', $ticket_data[ Ticket::END_DATE_META_KEY ]['0'] );

			$ticket_data[ Ticket::END_DATE_META_KEY ] = $end_date[0] ?? null;
			$ticket_data[ Ticket::END_TIME_META_KEY ] = $end_date[1] ?? '23:59:59';
		}

		$new_params = [
			'id'                      => (int) ( $params['id'] ?? null ),
			'event'                   => $event_id,
			'ticket_id'               => $params['id'] ?? null,
			'ticket_name'             => $params['title'] ?? $ticket_post->post_title ?? null,
			'ticket_description'      => $params['content'] ?? $params['excerpt'] ?? $ticket_post->post_excerpt ?? null,
			'ticket_price'            => $params['price'] ?? $ticket_data[ Ticket::$price_meta_key ]['0'] ?? null,
			'ticket_show_description' => $params['show_description'] ?? $ticket_data[ Ticket::$show_description_meta_key ]['0'] ?? null,
			'ticket_type'             => $params['type'] ?? $ticket_data[ Ticket::$type_meta_key ]['0'] ?? null,
			'ticket_sku'              => $params['sku'] ?? $ticket_data[ Ticket::$sku_meta_key ]['0'] ?? null,
			'ticket_start_date'       => $params['ticket_start_date'] ?? $ticket_data[ Ticket::START_DATE_META_KEY ] ?? null,
			'ticket_start_time'       => $params['ticket_start_time'] ?? $ticket_data[ Ticket::START_TIME_META_KEY ] ?? null,
			'ticket_end_date'         => $params['ticket_end_date'] ?? $ticket_data[ Ticket::END_DATE_META_KEY ] ?? null,
			'ticket_end_time'         => $params['ticket_end_time'] ?? $ticket_data[ Ticket::END_TIME_META_KEY ] ?? null,
			'tribe-ticket'            => array_filter( $tribe_ticket, fn( $value ) => null !== $value ),
			'ticket_add_sale_price'   => isset( $params['sale_price'] ) || ! empty( $ticket_data[ Ticket::$sale_price_checked_key ]['0'] ),
			'ticket_sale_price'       => $params['sale_price'] ?? $ticket_data[ Ticket::$sale_price_key ]['0'] ?? null,
			'ticket_sale_start_date'  => $params['sale_price_start_date'] ?? $ticket_data[ Ticket::$sale_price_start_date_key ]['0'] ?? null,
			'ticket_sale_end_date'    => $params['sale_price_end_date'] ?? $ticket_data[ Ticket::$sale_price_end_date_key ]['0'] ?? null,
		];

		$new_params['ticket_sale_price'] = maybe_unserialize( $new_params['ticket_sale_price'] );

		if ( $new_params['ticket_sale_price'] instanceof Value ) {
			$new_params['ticket_sale_price'] = $new_params['ticket_sale_price']->get_decimal();
		}

		if ( is_object( $new_params['ticket_sale_price'] ) ) {
			throw new InvalidRestArgumentException( __( 'The ticket price must be a number.', 'event-tickets' ) );
		}

		unset(
			$params['sku'],
			$params['end_date'],
			$params['start_date'],
			$params['type'],
			$params['stock_mode'],
			$params['show_description'],
			$params['stock'],
			$params['event_capacity'],
			$params['capacity'],
			$params['sale_price_end_date'],
			$params['sale_price_start_date'],
			$params['sale_price'],
			$params['price'],
			$params['event'],
			$params['title'],
		);

		$post_params = $params;

		$ticket_params = array_filter( $new_params, fn( $value ) => null !== $value );

		return compact( 'post_params', 'ticket_params' );
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
		return $this->filter_upsert_params( $params );
	}

	/**
	 * Filters the create params.
	 *
	 * @since TBD
	 *
	 * @param array $params The params to filter.
	 *
	 * @return array
	 */
	public function filter_create_params( array $params ): array {
		return $this->filter_upsert_params( $params );
	}
}
