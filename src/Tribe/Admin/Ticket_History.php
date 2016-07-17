<?php
/**
 * Integrates with the attendee list table to make each ticket's
 * post history available for viewing.
 */
class Tribe__Tickets__Admin__Ticket_History {
	public function __construct() {
		add_filter( 'tribe_tickets_attendees_table_order_status', array( $this, 'add_history_link' ), 10, 2 );
		add_action( 'wp_ajax_get_ticket_history', array( $this, 'supply_history' ) );
	}

	/**
	 * Add view/hide history links to each attendee table row where history
	 * is available.
	 *
	 * @param string $column_html
	 * @param array  $item
	 *
	 * @return string
	 */
	public function add_history_link( $column_html, array $item ) {
		if ( ! isset( $item[ 'attendee_id' ] ) ) {
			return $column_html;
		}

		$history = Tribe__Post_History::load( $item[ 'attendee_id' ] );

		if ( ! $history->has_entries() ) {
			return $column_html;
		}

		$ticket_id = absint( $item[ 'attendee_id' ] );
		$check = wp_create_nonce( 'view-ticket-history-' . $ticket_id );
		$view = esc_html_x( 'View history', 'attendee table', 'event-tickets' );
		$hide = esc_html_x( 'Hide history', 'attendee table', 'event-tickets' );

		$history_link = "
			<div> 
				<a href='#' class='ticket-history' data-ticket-id='$ticket_id' data-check='$check'> $view </a>
				<a href='#' class='hide-ticket-history'> $hide </a>
			</div>
		";

		return $column_html . $history_link;
	}

	/**
	 * Responds to ajax requests to access the ticket history.
	 */
	public function supply_history() {
		if ( ! wp_verify_nonce( @$_POST[ 'check' ], 'view-ticket-history-' . @$_POST[ 'ticket_id' ] ) ) {
			return;
		}

		$html = '<table>';
		$history = Tribe__Post_History::load( $_POST[ 'ticket_id' ] );

		foreach ( $history->get_entries() as $entry ) {
			$html .= '<tr> <td>' . esc_html( $entry->datetime ) . '</td> <td>' . esc_html( $entry->message ) . '</td> </tr>';
		}

		$html .= '</table>';

		if ( ! $history->has_entries() ) {
			$html = '<p>' . esc_html__( 'No history available', 'event-tickets' ) . '</p>';
		}

		wp_send_json_success( array(
			'html' => $html,
		) );
	}
}