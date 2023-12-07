<?php
/**
 * Handles the modifications to the edit flow and data required by Series Passes.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Events_Pro\Custom_Tables\V1\Models\Provisional_Post;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Series_Passes;

/**
 * Class Edit.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
class Edit {
	/**
	 * A reference to the labels' handler.
	 *
	 * @since TBD
	 *
	 * @var Labels
	 */
	private Labels $labels;

	/**
	 * Edit constructor.
	 *
	 * since TBD
	 *
	 * @param Labels $labels The labels' handler.
	 */
	public function __construct( Labels $labels ) {
		$this->labels = $labels;
	}

	/**
	 * Filters the editor configuration data to add the information required to correctly represent
	 * Series Passes in the editor.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The editor configuration data.
	 *
	 * @return array<string,mixed> The editor configuration data with the information required to correctly represent
	 *                             Series Passes.
	 */
	public function filter_configuration_data( array $data ): array {
		if ( ! isset( $data['ticketTypes'] ) ) {
			$data['ticketTypes'] = [];
		}
		$data['ticketTypes'][ Series_Passes::TICKET_TYPE ] = [
			'title' => esc_html( tec_tickets_get_series_pass_plural_uppercase( 'editor-configuration' ) ),
		];

		return $data;
	}

	/**
	 * Prevent Series Passes from being edited outside the context of Series.
	 *
	 * @since TBD
	 *
	 * @param bool $is_ticket_editable Whether the ticket is editable in the context of the post.
	 * @param int  $ticket_id          The ticket ID.
	 * @param int  $post_id            The post ID.
	 *
	 * @return bool Whether the ticket is editable in the context of the post.
	 */
	public function is_ticket_editable_from_post( bool $is_ticket_editable, int $ticket_id, int $post_id ): bool {
		if (
			get_post_meta( $ticket_id, '_type', true ) === Series_Passes::TICKET_TYPE
			&& get_post_type( $post_id ) !== Series_Post_Type::POSTTYPE
		) {
			return false;
		}

		return $is_ticket_editable;
	}

	/**
	 * Filters the editor data localized by Flexible Tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $editor_data The editor data.
	 *
	 * @return array<string,mixed> The updated editor data.
	 */
	public function filter_editor_data( array $editor_data ): array {
		$post                                                     = get_post();
		$event_id                                                 = $post instanceof \WP_Post ? $post->ID : null;
		$provisional_post                                         = tribe( Provisional_Post::class );
		$event_id                                                 = $provisional_post->is_provisional_post_id( $event_id ) ?
			$provisional_post->normalize_provisional_post_id( $event_id )
			: $event_id;
		$relationship                                             = $event_id ? Series_Relationship::find( $event_id, 'event_post_id' ) : null;
		$series_id                                                = $relationship ? $relationship->series_post_id : null;
		$editor_data['defaultTicketTypeEventInSeriesDescriptionTemplate'] = $series_id ?
			$this->labels->get_default_ticket_type_event_in_series_template()
			: '';

		return $editor_data;
	}

	/**
	 * Filters the JavaScript configuration for the Attendees report to include the confirmation strings for
	 * Series Passes.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $config_data The JavaScript configuration.
	 *
	 * @return array<string,mixed> The updated JavaScript configuration.
	 */
	public function filter_tickets_attendees_report_js_config( array $config_data ): array {
		if ( ! isset( $config_data['confirmation'] ) ) {
			$config_data['confirmation'] = [];
		}

		$config_data['confirmation'][ Series_Passes::TICKET_TYPE ] = [
			'singular' => esc_html__(
				'Please confirm you would like to delete this attendee from the Series and all events.',
				'event-tickets'
			),
			'plural'   => esc_html__( "Please confirm you would like to delete these attendees.\n" .
			                          "Records for Series Pass attendees will be deleted from the Series and all events.",
				'event-tickets'
			),
		];

		return $config_data;
	}
}
