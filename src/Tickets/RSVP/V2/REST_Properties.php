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
	 * @param string              $filter     The filter used to build the properties.
	 *
	 * @return array<string,mixed> Modified properties.
	 */
	public function add_show_not_going_to_properties( array $properties, WP_Post $post, string $filter ): array {
		$type = $properties['type'] ?? get_post_meta( $post->ID, '_type', true );

		if ( Constants::TC_RSVP_TYPE !== $type ) {
			return $properties;
		}

		$show_not_going              = get_post_meta( $post->ID, Constants::SHOW_NOT_GOING_META_KEY, true );
		$properties['show_not_going'] = tribe_is_truthy( $show_not_going );

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
		$properties['show_not_going'] = true;

		return $properties;
	}

	/**
	 * Add "show_not_going" to REST API request body documentation for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $documentation The Swagger documentation array.
	 * @param mixed               $definition    The definition instance.
	 *
	 * @return array<string,mixed> Modified documentation.
	 */
	public function add_show_not_going_to_request_body_docs( array $documentation, $definition ): array {
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
	 * @param mixed               $definition    The definition instance.
	 *
	 * @return array<string,mixed> Modified documentation.
	 */
	public function add_show_not_going_to_response_docs( array $documentation, $definition ): array {
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

		return $documentation;
	}
}
