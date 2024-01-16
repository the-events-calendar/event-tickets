<?php
/**
 * Handles the modifications to the Tickets metabox required to support Series Passes.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Labels;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;
use Tribe__Date_Utils as Dates;

/**
 * Class Metabox.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Metabox {

	/**
	 * A reference to the Admin Views handler for Flexible Tickets.
	 *
	 * @since 5.8.0
	 *
	 * @var Admin_Views
	 */
	private Admin_Views $admin_views;

	/**
	 * A reference to the labels' handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Labels
	 */
	private Labels $labels;

	/**
	 * Metabox constructor.
	 *
	 * since 5.8.0
	 *
	 * @param Admin_Views $admin_views A reference to the Admin Views handler for Flexible Tickets.
	 */
	public function __construct( Admin_Views $admin_views, Labels  $labels) {
		$this->admin_views = $admin_views;
		$this->labels = $labels;
	}

	/**
	 * Renders the button to toggle the Series Pass form.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The post ID context of the metabox.
	 *
	 * @return void
	 */
	public function render_form_toggle( int $post_id ) {
		$post = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post && $post->post_type === Series_Post_Type::POSTTYPE ) ) {
			return;
		}

		$ticket_providing_modules = array_diff_key( Tickets::modules(), [ RSVP::class => true ] );
		$this->admin_views->template( 'series-pass-form-toggle', [
			'disabled' => count( $ticket_providing_modules ) === 0,
		] );
	}

	/**
	 * Updates the panels data to add the end date help text and the end date and time values.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $data      The panels data.
	 * @param int                 $ticket_id The post ID of the Series Pass.
	 *
	 * @return array<string,mixed> The panels data with the end date help text and the end date and time values.
	 */
	public function update_panel_data( array $data, int $ticket_id ): array {
		$data['ticket_end_date_help_text'] = esc_attr_x(
			'If you do not set an end sale date, passes will be available until the last event in the Series.',
			'Help text for the end date field in the Series Passes meta box.',
			'event-tickets'
		);

		$set_end_date = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$set_end_time = get_post_meta( $ticket_id, '_ticket_end_time', true );

		$datepicker_format       = Dates::datepicker_formats( Dates::get_datepicker_format_index() );
		$data['ticket_end_date'] = $set_end_date ? Dates::date_only( $set_end_date, false, $datepicker_format ) : '';
		$data['ticket_end_time'] = $set_end_time ? Dates::time_only( $set_end_time ) : '';

		return $data;
	}

	/**
	 * Prints a notice letting the user know that the event is part of a Series
	 * and Series Passes should be edited from the Series edit screen.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The post ID context of the metabox.
	 *
	 * @return void
	 */
	public function display_pass_notice( int $post_id ): void {
		$series_ids = tec_series()->where( 'event_post_id', $post_id )->get_ids();

		if ( ! count( $series_ids ) ) {
			return;
		}

		$series = reset( $series_ids );

		$this->admin_views->template( 'series-pass-event-notice', [
			'series_edit_link' => get_edit_post_link( $series ),
			'series_title'     => get_the_title( $series ),
		] );
	}

	/**
	 * Prints the link to the Series edit screen in the context of the Ticket list,
	 * replacing the default Ticket edit actions.
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_post_id The post ID of the Series Pass.
	 *
	 * @return void
	 */
	public function render_link_to_series( int $ticket_post_id ): void {
		$this->admin_views->template( 'series-pass-edit-link', [
			'series_edit_link' => get_edit_post_link( $ticket_post_id ),
		] );
	}

	/**
	 * Prints the Series Pass icon in the context of the Ticket list.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function print_series_pass_icon(): void {
		$this->admin_views->template( 'series-pass-icon' );
	}

	/**
	 * Renders the Series Pass type header in the context of the Ticket add and edit form.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function render_type_header(): void {
		$this->admin_views->template( 'series-pass-type-header' );
	}

	/**
	 * Returns the help text for the default ticket type in the ticket form.
	 *
	 * @since 5.8.0
	 *
	 * @param int $event_id  The post ID context of the metabox.
	 * @param int $series_id The post ID of the Series Pass.
	 *
	 * @return string The help text for the default ticket type in the ticket form.
	 */
	public function get_default_ticket_type_header_description( int $event_id, int $series_id ): string {
		return $this->labels->get_default_ticket_type_event_in_series_description( $series_id, $event_id );
	}

	/**
	 * Get the helper text for series post type ticket panel.
	 *
	 * @since 5.8.0
	 *
	 * @param string $text The helper text with link.
	 * @param WP_Post $post The Post object.
	 *
	 * @return string The helper text with link.
	 */
	public function get_tickets_panel_list_helper_text( string $text, WP_Post $post ): string {
		$helper_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer ">%2$s</a>',
			esc_url( 'https://evnt.is/manage-tickets' ),
			esc_html__( 'Learn more about ticket management', 'event-tickets' )
		);

		return sprintf(
		// Translators: %1$s: dynamic "series pass" label text, %2$s: dynamic learn more link.
			esc_html__( 'Create and manage %1$s for this Series. %2$s', 'event-tickets' ),
			tec_tickets_get_series_pass_plural_uppercase(),
			$helper_link,
		);
	}
}