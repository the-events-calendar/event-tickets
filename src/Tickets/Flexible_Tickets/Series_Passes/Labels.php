<?php
/**
 * Filters the labels used by the Tickets plugin in  the admin and frontend of the site to suite the Series Passes
 * wording.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

/**
 * Class Labels.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Labels {
	public function start_filtering_labels(): void {
		add_filter( 'tribe_get_ticket_label_plural_lowercase', [
			$this,
			'filter_ticket_label_plural_lowercase'
		], 10, 2 );
		add_filter( 'tribe_get_ticket_label_singular_lowercase', [
			$this,
			'filter_ticket_label_singular_lowercase'
		], 10, 2 );
		add_filter( 'tribe_get_ticket_label_singular', [ $this, 'filter_ticket_label_singular' ], 10, 2 );
		add_filter( 'tribe_get_ticket_label_plural', [ $this, 'filter_ticket_label_plural' ], 10, 2 );
	}

	/**
	 * Either hook or unhook the ticket labels filters.
	 *
	 * @since 5.8.0
	 *
	 * @param bool $filter Whether to hook or unhook the ticket labels filters.
	 *
	 * @return void Ticket labels filters are hooked or unhooked.
	 */
	public function stop_filtering_labels(): void {
		// Remove the "Ticket" labels filters.
		remove_filter( 'tribe_get_ticket_label_plural_lowercase', [ $this, 'filter_ticket_label_plural_lowercase' ] );
		remove_filter( 'tribe_get_ticket_label_singular_lowercase', [
			$this,
			'filter_ticket_label_singular_lowercase'
		] );
		remove_filter( 'tribe_get_ticket_label_singular', [ $this, 'filter_ticket_label_singular' ] );
		remove_filter( 'tribe_get_ticket_label_plural', [ $this, 'filter_ticket_label_plural' ] );
	}

	/**
	 * Filters a ticket label, if the context requires it.
	 *
	 * @since 5.8.0
	 *
	 * @param string $type    The type of label to filter.
	 * @param string $label   The label to filter.
	 * @param string $context The context in which the label is filtered.
	 *
	 * @return string The filtered label, if the context requires it.
	 */
	private function filter_ticket_label( string $type, string $label, string $context ): string {
		if ( ! str_ends_with( $context, 'metabox_capacity' ) ) {
			return $label;
		}

		switch ( $type ) {
			case 'plural_lowercase':
				$label = \tec_tickets_get_series_pass_plural_uppercase();
				break;
			case 'singular_lowercase':
				$label = \tec_tickets_get_series_pass_singular_uppercase();
				break;
			case 'singular_uppercase':
				$label = \tec_tickets_get_series_pass_singular_uppercase();
				break;
			case 'plural_uppercase':
				$label = \tec_tickets_get_series_pass_plural_uppercase();
				break;
		}

		return $label;
	}

	/**
	 * Filters the plural uppercase version of the ticket label, from "Tickets" to "Series Passes".
	 *
	 * @since 5.8.0
	 *
	 * @param string $label   The plural uppercase version of the ticket label.
	 * @param string $context The context in which the label is filtered.
	 *
	 * @return string The filtered label, if the context requires it.
	 */
	public function filter_ticket_label_plural_lowercase( string $label, string $context ): string {
		return $this->filter_ticket_label( 'plural_lowercase', $label, $context );
	}

	/**
	 * Filters the singular lowercase version of the ticket label, from "ticket" to "series pass".
	 *
	 * @since 5.8.0
	 *
	 * @param string $label   The singular lowercase version of the ticket label.
	 * @param string $context The context in which the label is filtered.
	 *
	 * @return string The filtered label, if the context requires it.
	 */
	public function filter_ticket_label_singular_lowercase( string $label, string $context ): string {
		return $this->filter_ticket_label( 'singular_lowercase', $label, $context );
	}

	/**
	 * Filters the singular uppercase version of the ticket label, from "Ticket" to "Series Pass".
	 *
	 * @since 5.8.0
	 *
	 * @param string $label   The singular uppercase version of the ticket label.
	 * @param string $context The context in which the label is filtered.
	 *
	 * @return string The filtered label, if the context requires it.
	 */
	public function filter_ticket_label_singular( string $label, string $context ): string {
		return $this->filter_ticket_label( 'singular_uppercase', $label, $context );
	}

	/**
	 * Filters the plural uppercase version of the ticket label, from "Tickets" to "Series Passes".
	 *
	 * @since 5.8.0
	 *
	 * @param string $label   The plural uppercase version of the ticket label.
	 * @param string $context The context in which the label is filtered.
	 *
	 * @return string The filtered label, if the context requires it.
	 */
	public function filter_ticket_label_plural( string $label, string $context ): string {
		return $this->filter_ticket_label( 'plural_uppercase', $label, $context );
	}

	/**
	 * Returns the help text for the default ticket type in the ticket form when the Event is part of a Series.
	 *
	 * @since 5.8.0
	 *
	 * @param int $series_id The post ID of the Series.
	 * @param int $event_id  The post ID of the Event.
	 *
	 * @return string The help text for the default ticket type in the ticket form when the Event is part of a Series.
	 */
	public function get_default_ticket_type_event_in_series_description( int $series_id, int $event_id ): string {
		$edit_link        = get_edit_post_link( $series_id, 'admin' ) . '#tribetickets';
		$series_edit_link = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			$edit_link,
			get_post_field( 'post_title', $series_id )
		);
		$description      = sprintf(
			$this->get_default_ticket_type_event_in_series_template(),
			$series_edit_link
		);

		return wp_kses( $description, [ 'a' => [ 'href' => [], 'target' => [] ] ] );
	}

	/**
	 * Returns the template for the help text for the default ticket type in the ticket form when the Event is part
	 * of a Series.
	 *
	 * @since 5.8.0
	 *
	 * @return string The template for the help text for the default ticket type in the ticket form when the Event
	 *                is part of a Series.
	 */
	public function get_default_ticket_type_event_in_series_template(): string {
		return sprintf(
			// Translators: %1$s is the ticket type label, %2$s is the Event type label, %3$s is the Series Pass type label, %4$s is the Series edit link.
			_x(
				'A %1$s is specific to this %2$s. You can add a %3$s from the %%s Series page.',
				'The help text for the default ticket type in the ticket form.',
				'event-tickets'
			),
			tec_tickets_get_default_ticket_type_label_lowercase( 'ticket_type_default_header_description' ),
			tribe_get_event_label_singular_lowercase(),
			tec_tickets_get_series_pass_singular_uppercase( 'ticket_type_default_header_description' ),
		);
	}
}