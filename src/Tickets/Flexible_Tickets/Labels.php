<?php
/**
 * Filters the labels used by the Tickets plugin in  the admin and frontend of the site to suite the Series Passes
 * wording.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

/**
 * Class Labels.
 *
 * @since   TBD
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
	 * @since TBD
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
	 * @since TBD
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
				$label = \tec_tickets_get_series_pass_plural_lowercase();
				break;
			case 'singular_lowercase':
				$label = \tec_tickets_get_series_pass_singular_lowercase();
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param string $label   The plural uppercase version of the ticket label.
	 * @param string $context The context in which the label is filtered.
	 *
	 * @return string The filtered label, if the context requires it.
	 */
	public function filter_ticket_label_plural( string $label, string $context ): string {
		return $this->filter_ticket_label( 'plural_uppercase', $label, $context );
	}
}