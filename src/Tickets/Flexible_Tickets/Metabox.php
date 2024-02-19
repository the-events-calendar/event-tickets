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
	 * The Metabox constructor.
	 *
	 * @since 5.8.0
	 *
	 * @param Admin_Views $admin_views A reference to the Admin Views handler for Flexible Tickets.
	 * @param Labels      $labels      A reference to the labels' handler.
	 */
	public function __construct( Admin_Views $admin_views, Labels $labels ) {
		$this->admin_views = $admin_views;
		$this->labels      = $labels;
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

		if ( ! ( $post instanceof WP_Post && Series_Post_Type::POSTTYPE === $post->post_type ) ) {
			return;
		}

		$ticket_providing_modules = array_diff_key( Tickets::modules(), [ RSVP::class => true ] );
		$this->admin_views->template(
			'series-pass-form-toggle',
			[
				'disabled' => count( $ticket_providing_modules ) === 0,
			]
		);
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

		if ( tribe_is_recurring_event( $post_id ) ) {
			return;
		}

		$series = reset( $series_ids );

		$helpler_link_text = sprintf(
			// Translators: %s is the label for the link.
			esc_html__( 'Learn more about %s', 'event-tickets' ),
			tec_tickets_get_series_pass_plural_uppercase()
		);

		$helper_link = sprintf(
			// Translators: %1$s is a link to the documentation, %2$s is the label for the link.
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( 'https://evnt.is/-series-passes' ),
			esc_html( $helpler_link_text )
		);

		$series_edit_link = sprintf(
			// Translators: %1$s is a link to the series edit screen, %2$s is the title of the series.
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( get_edit_post_link( $series ) ),
			esc_html( get_the_title( $series ) )
		);

		$this->admin_views->template(
			'series-pass-event-notice',
			[
				'series_edit_link' => $series_edit_link,
				'helper_link'      => $helper_link,
			]
		);
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
		$this->admin_views->template(
			'series-pass-edit-link',
			[
				'series_edit_link' => get_edit_post_link( $ticket_post_id ),
			]
		);
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
	 * Includes the warning message for recurring events in context of series.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $context The context of the ticket form.
	 *
	 * @return array<string,mixed> The context array.
	 */
	public function get_recurring_warning_message( array $context ): array {
		if ( ! isset( $context['post_id'] ) ) {
			return $context;
		}

		$series  = tec_series()->where( 'event_post_id', (int) $context['post_id'] )->first();
		$message = $series ? $this->get_warning_message_for_saved_event( $series ) : $this->get_warning_message_for_unsaved_event();

		$context['messages'] = array_merge( [ 'recurring-warning-message' => $message ], $context['messages'] );
		return $context;
	}

	/**
	 * Returns the warning message for saved recurring events.
	 *
	 * @since 5.8.2
	 *
	 * @param WP_Post $series The post object of the Series Pass.
	 *
	 * @return string The warning message for saved recurring events.
	 */
	public function get_warning_message_for_saved_event( WP_Post $series ): string {
		$learn_more_text = sprintf(
			// Translators: %s is the pluralized name of the series pass.
			__( 'Learn more about %s', 'event-tickets' ),
			tec_tickets_get_series_pass_plural_uppercase()
		);

		$learn_more_link = sprintf(
			// Translators: %1$s is a link to the documentation, %2$s is the label for the link.
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( 'https://evnt.is/-series-passes' ),
			esc_html( $learn_more_text )
		);

		$series_link = sprintf(
			// Translators: %2$s is the title of the series.
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( get_edit_post_link( $series ) ),
			esc_html( get_the_title( $series ) )
		);

		return sprintf(
			// Translators: %1$s is the pluralized name of the series pass, %2$s is the singular name of the event, %3$s is a link to the series edit screen, %4$s is a link to the documentation.
			__( 'This recurring %2$s is part of a Series. Create and manage %1$s for this %2$s from the %3$s Series admin. %4$s', 'event-tickets' ),
			tec_tickets_get_series_pass_plural_uppercase( 'ticket editor message' ),
			tribe_get_event_label_singular_lowercase(),
			$series_link,
			$learn_more_link,
		);
	}

	/**
	 * Returns the warning message for unsaved recurring events.
	 *
	 * @since 5.8.2
	 *
	 * @return string The warning message for unsaved recurring events.
	 */
	public function get_warning_message_for_unsaved_event(): string {
		$learn_more_text = sprintf(
			// Translators: %s is the pluralized name of the series pass.
			__( 'Learn more about %s', 'event-tickets' ),
			tec_tickets_get_series_pass_plural_uppercase()
		);

		$learn_more_link = sprintf(
			// Translators: %1$s is a link to the documentation, %2$s is the label for the link.
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( 'https://evnt.is/-series-passes' ),
			esc_html( $learn_more_text )
		);

		return sprintf(
			// Translators: %1$s is the singular name of the event, %2$s is the pluralized name of the series pass, %3$s is a link to the documentation.
			__( 'Once you save this %1$s, you can add %2$s to its parent Series. %3$s.', 'event-tickets' ),
			tribe_get_event_label_singular_lowercase(),
			tec_tickets_get_series_pass_plural_uppercase( 'ticket editor message' ),
			$learn_more_link,
		);
	}

	/**
	 * Returns the warning message when there is no commerce provider configured.
	 *
	 * @since 5.8.2
	 *
	 * @return string The warning message when there is no commerce provider configured.
	 */
	public function get_no_commerce_provider_warning_message(): string {
		$kb_url = 'https://evnt.is/1ao5';

		/* translators: %1$s: URL for help link, %2$s: Label for help link. */
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $kb_url ),
			esc_html_x( 'Learn More', 'Helper link in Ticket Editor', 'event-tickets' )
		);

		return sprintf(
		/* Translators: %1$s: link to help article. */
			__( 'There is no payment gateway configured. To create %1$s, you\'ll need to enable and configure an ecommerce solution. %2$s', 'event-tickets' ),
			tec_tickets_get_series_pass_plural_uppercase( 'ticket editor message' ),
			$link
		);
	}
}
