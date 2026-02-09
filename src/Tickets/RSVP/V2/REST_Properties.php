<?php
/**
 * RSVP V2 REST Properties handler.
 *
 * Handles adding RSVP-specific properties to REST API responses and documentation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Boolean;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Tickets\Commerce\Ticket;
use WP_Post;

/**
 * Class REST_Properties
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class REST_Properties {
	/**
	 * Add the "show not going" property to ticket model properties for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $properties Properties to add to the model.
	 * @param WP_Post             $post       The ticket post object.
	 *
	 * @return array<string,mixed> Modified properties.
	 */
	public function add_show_not_going_to_properties( array $properties, WP_Post $post ): array {
		$type = $properties['type'] ?? get_post_meta( $post->ID, '_type', true );

		if ( Constants::TC_RSVP_TYPE !== $type ) {
			return $properties;
		}

		$show_not_going                = get_post_meta( $post->ID, Constants::SHOW_NOT_GOING_META_KEY, true );
		$properties['show_not_going']  = tribe_is_truthy( $show_not_going );
		$properties['not_going_count'] = 0;

		if ( $properties['show_not_going'] ) {
			/** @var Ticket $ticket_data */
			$ticket_data   = tribe( Ticket::class );
			$ticket_object = $ticket_data->load_ticket_object( $post->ID );
			$provider      = $ticket_object->get_provider();

			remove_filter(
				'tec_tickets_build_ticket_properties',
				tribe()->callback( self::class, 'add_show_not_going_to_properties' ),
			);

			$attendees = $provider->get_attendees_by_id( $ticket_object->ID );

			add_filter(
				'tec_tickets_build_ticket_properties',
				tribe()->callback( self::class, 'add_show_not_going_to_properties' ),
			);

			$properties['not_going_count'] = count(
				array_filter(
					$attendees,
					static fn( array $attendee ): bool => 'no' === get_post_meta( $attendee['ID'], Constants::RSVP_STATUS_META_KEY, true )
				)
			);
		}

		return $properties;
	}

	/**
	 * Add "show not going" to the list of REST properties for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,bool> $properties The properties to expose in REST.
	 *
	 * @return array<string,bool> Modified properties.
	 */
	public function add_show_not_going_to_rest_properties( array $properties ): array {
		$properties['show_not_going']  = true;
		$properties['not_going_count'] = true;

		return $properties;
	}

	/**
	 * Add "show_not_going" to REST API request body documentation for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $documentation The Swagger documentation array.
	 *
	 * @return array<string,mixed> Modified documentation.
	 */
	public function add_show_not_going_to_request_body_docs( array $documentation ): array {
		$properties = $documentation['allOf'][1]['properties'] ?? null;

		if ( ! $properties instanceof PropertiesCollection ) {
			return $documentation;
		}

		$properties[] = (
			new Boolean(
				'show_not_going',
				fn() => __( 'Whether to show the "Not Going" option for RSVP tickets.', 'event-tickets' ),
			)
		)->set_example( false );

		return $documentation;
	}

	/**
	 * Add "show_not_going" to REST API response documentation for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $documentation The Swagger documentation array.
	 *
	 * @return array<string,mixed> Modified documentation.
	 */
	public function add_show_not_going_to_response_docs( array $documentation ): array {
		$properties = $documentation['allOf'][1]['properties'] ?? null;

		if ( ! $properties instanceof PropertiesCollection ) {
			return $documentation;
		}

		$properties[] = (
			new Boolean(
				'show_not_going',
				fn() => __( 'Whether to show the "Not Going" option. Only present for RSVP tickets.', 'event-tickets' ),
			)
		)->set_example( false );

		$properties[] = (
			new Positive_Integer(
				'not_going_count',
				fn() => __( 'The number of "Not Going" responses for RSVP tickets.', 'event-tickets' ),
			)
		)->set_example( 7 );

		return $documentation;
	}

	/**
	 * Add the show_not_going parameter to upsert params for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $ticket_params The filtered ticket params.
	 * @param array<string,mixed> $params        The original REST params.
	 *
	 * @return array<string,mixed> Modified ticket params.
	 */
	public function add_show_not_going_to_upsert_params( array $ticket_params, array $params ): array {
		if ( ! isset( $params['show_not_going'] ) ) {
			return $ticket_params;
		}

		$ticket_params['show_not_going'] = $params['show_not_going'];

		return $ticket_params;
	}
}
