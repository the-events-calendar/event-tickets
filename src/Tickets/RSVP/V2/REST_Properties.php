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
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Attendee as TC_Attendee;
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

		$show_not_going               = get_post_meta( $post->ID, Constants::SHOW_NOT_GOING_META_KEY, true );
		$properties['show_not_going'] = tribe_is_truthy( $show_not_going );

		/*
		 * Always include not_going_count to match the REST API schema. When show_not_going is disabled,
		 * the count is 0 because the "Not Going" option is not shown and no count is computed.
		 */
		$properties['not_going_count'] = 0;

		if ( $properties['show_not_going'] ) {
			/*
			 * Why is this value not cached?
			 * Caching this value would be done by Ticket ID.
			 * But that value would have to be invalidated on each Ticket or connected Attendee
			 * update. To capture Ticket and Attendee updates, the required logic would be to
			 * listen for all updates/deletion of posts (Attendees) and post meta (going/not-going).
			 * That filtering would likely cost more than this query.
			 */
			$count = (int) DB::get_var(
				DB::prepare(
					'SELECT COUNT(*) FROM %i p
						INNER JOIN %i pm_ticket ON p.ID = pm_ticket.post_id
						INNER JOIN %i pm_status ON p.ID = pm_status.post_id
						WHERE p.post_type = %s
						AND pm_ticket.meta_key = %s
						AND pm_ticket.meta_value = %s
						AND pm_status.meta_key = %s
						AND pm_status.meta_value IN (%s, %s)',
					DB::prefix( 'posts' ),
					DB::prefix( 'postmeta' ),
					DB::prefix( 'postmeta' ),
					TC_Attendee::POSTTYPE,
					TC_Attendee::$ticket_relation_meta_key,
					$post->ID,
					Constants::RSVP_STATUS_META_KEY,
					'no',
					'0'
				)
			);

			$properties['not_going_count'] = (int) $count;
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
}
