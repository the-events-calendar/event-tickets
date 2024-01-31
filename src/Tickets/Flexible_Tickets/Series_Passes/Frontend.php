<?php
namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Templates\Series_Filters;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Tickets_View;

/**
 * Class Frontend handler.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */
class Frontend {
	/**
	 * Filters the data for the "My Tickets" link.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string, mixed> $data The data for the "My Tickets" link.
	 * @param int $event_id              The event ID.
	 * @param int $user_id               The user ID.
	 *
	 * @return array<string, array> The updated data.
	 */
	public function filter_my_tickets_link_data( array $data, int $event_id, int $user_id ): array {
		$post_type = get_post_type( $event_id );

		// Only filter Events and Series, skip other post types.
		if ( TEC::POSTTYPE !== $post_type && Series_Post_Type::POSTTYPE !== $post_type ) {
			return $data;
		}

		// If we are on series page then replace ticket data with series counts.
		if ( Series_Post_Type::POSTTYPE === $post_type ) {
			$data['series'] = [
				'count'    => $data['ticket']['count'],
				'singular' => __( 'Pass', 'event-tickets' ),
				'plural'   => __( 'Passes', 'event-tickets' ),
			];

			// Remove the ticket data.
			$data['ticket']['count'] = 0;

			return $data;
		}

		// Process series pass count for single event.
		$series = tec_series()->where( 'event_post_id', $event_id )->first_id();

		if ( empty( $series ) ) {
			// Not part of a Series, bail.
			return $data;
		}

		// Get the tickets purchased by this user and for this series.
		$view              = Tribe__Tickets__Tickets_View::instance();
		$series_pass_count = $view->count_ticket_attendees( $series, $user_id );

		if ( empty( $series_pass_count ) ) {
			return $data;
		}

		$data['series'] = [
			'count'    => $series_pass_count,
			'singular' => __( 'Pass', 'event-tickets' ),
			'plural'   => __( 'Passes', 'event-tickets' ),
		];

		if ( $data['ticket']['count'] > 0 ) {
			$data['ticket']['count'] -= $series_pass_count;
		}

		return $data;
	}

	/**
	 * Skip rendering the Series content when on the My Tickets page.
	 *
	 * @since 5.8.0
	 *
	 * @param string $content The post content.
	 *
	 * @return string The filtered post content.
	 */
	public function skip_rendering_series_content_for_my_tickets_page( string $content ): string {
		// Check if we are on my ticket page.
		$is_ticket_edit_page = 'tickets' === get_query_var( 'eventDisplay', false );

		if ( ! $is_ticket_edit_page ) {
			return $content;
		}

		$series_filters = tribe( Series_Filters::class );
		remove_filter( 'the_content', [ $series_filters, 'inject_content' ], 20 );

		// It's enough to run this once.
		remove_filter( 'the_content', [ $this, 'skip_rendering_series_content_for_my_tickets_page' ], 1 );

		return $content;
	}
}