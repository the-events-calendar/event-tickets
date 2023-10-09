<?php
namespace TEC\Tickets\Commerce\Reports\Data;

use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Tickets;

class Order_Summary {
	private int $post_id;

	/**
	 * @var \Tribe__Tickets__Ticket_Object[]
	 */
	private array $tickets;

	/**
	 * @var array|array[]
	 */
	private array $tickets_by_type;

	/**
	 * Order_Summary constructor.
	 *
	 * @param int $post_id
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$this->build_data();
	}

	public function build_data(): void {
		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $this->post_id );
		$this->tickets  = $provider->get_tickets( $this->post_id );
		foreach ( $this->tickets as $ticket ) {
			$quantities = array_filter( tribe( Ticket::class )->get_status_quantity( $ticket->ID ) );
			$quantities[ $ticket->availability_slug() ] = $ticket->available();

			$ticket_data = [
				'ticket'     => $ticket,
				'label'      => sprintf( '%1$s %2$s', $ticket->name, Value::create( $ticket->price )->get_currency() ),
				'type'       => $ticket->type(),
				'quantities' => $quantities,
				'qty_string' => implode( ' | ', array_map( fn( $k, $v ) => "$v $k", array_keys( $quantities ), $quantities ) ),
			];

			$this->tickets_by_type[ $ticket->type ][] = $ticket_data;
		}
	}

	/**
	 * Get the tickets by type.
	 *
	 * @since TBD
	 *
	 * @return \Tribe__Tickets__Ticket_Object[]
	 */
	public function get_tickets_by_type(): array {
		/**
		 * Filters the tickets by type in the order summary report.
		 *
		 * @since TBD
		 *
		 * @param array $tickets_by_type The tickets by type.
		 * @param int   $post_id The post ID.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_tickets_by_type', $this->tickets_by_type, $this->post_id );
	}

	/**
	 * Get the ticket type label.
	 *
	 * @since TBD
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_label_for_type( string $type ): string {
		if ( 'default' === $type ) {
			$type = __( 'Single Ticket', 'event-tickets' );
		}

		/**
		 * Filters the label for a ticket type in the order summary report.
		 *
		 * @since TBD
		 *
		 * @param string $type The ticket type.
		 * @param int    $post_id The post ID.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_label_for_type', $type, $this->post_id );
	}
}