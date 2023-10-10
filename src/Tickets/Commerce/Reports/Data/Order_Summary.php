<?php
namespace TEC\Tickets\Commerce\Reports\Data;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets;

class Order_Summary {
	private int $post_id;

	/**
	 * @var Ticket_Object[]
	 */
	private array $tickets;

	/**
	 * @var array|array[]
	 */
	private array $tickets_by_type;

	/**
	 * @var array|array[]
	 */
	private array $event_sales_by_status = [];
	private array $total_sales = [];
	private array $total_ordered = [];

	/**
	 * Order_Summary constructor.
	 *
	 * @param int $post_id
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$this->total_sales = [
			'qty'    => 0,
			'amount' => 0,
			'price'  => Value::create()->get_currency(),
		];
		$this->total_ordered = [
			'qty'    => 0,
			'amount' => 0,
			'price'  => Value::create()->get_currency(),
		];
		$this->build_data();
	}

	public function build_data(): void {
		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $this->post_id );
		$this->tickets  = $provider->get_tickets( $this->post_id );
		foreach ( $this->tickets as $ticket ) {
			$quantities = array_filter( tribe( Ticket::class )->get_status_quantity( $ticket->ID ) );

			// Process the event sales data.
			$this->process_event_sales_data( $quantities, $ticket );

			// We need to show the total available for each ticket type.
			$quantities[ $ticket->availability_slug() ] = $ticket->available();

			$ticket_data = [
				'ticket'        => $ticket,
				'label'         => sprintf( '%1$s %2$s', $ticket->name, Value::create( $ticket->price )->get_currency() ),
				'type'          => $ticket->type(),
				'qty_data'      => $quantities,
				'qty_by_status' => implode( ' | ', array_map( fn( $k, $v ) => "$v $k", array_keys( $quantities ), $quantities ) ),
			];

			$this->tickets_by_type[ $ticket->type ][] = $ticket_data;
		}
	}

	/**
	 * Get the tickets by type.
	 *
	 * @return Ticket_Object[]
	 * @since TBD
	 *
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

	protected function process_event_sales_data( array $quantity_by_status, Ticket_Object $ticket ): void {
		foreach ( $quantity_by_status as $status_slug => $quantity ) {
			if ( ! isset( $this->event_sales_by_status[ $status_slug ] ) ) {
				$status = tribe( Status_Handler::class )->get_by_slug( $status_slug );

				// This is the first time we've seen this status, so initialize it.
				$this->event_sales_by_status[ $status_slug ] = [
					'label'              => $status->get_name(),
					'qty_sold'           => 0,
					'total_sales_amount' => 0,
					'total_sales_price'  => Value::create()->get_currency(),
				];
			}
			$this->event_sales_by_status[ $status_slug ]['qty_sold']           += $quantity;
			$this->event_sales_by_status[ $status_slug ]['total_sales_amount'] += $quantity * $ticket->price;
			$this->event_sales_by_status[ $status_slug ]['total_sales_price']  = Value::create( $this->event_sales_by_status[ $status_slug ]['total_sales_amount'] )->get_currency();

			// process the total ordered data.
			$this->total_ordered['qty']    += $this->event_sales_by_status[ $status_slug ]['qty_sold'];
			$this->total_ordered['amount'] += $this->event_sales_by_status[ $status_slug ]['total_sales_amount'];
			$this->total_ordered['price']  = Value::create( $this->total_ordered['amount'] )->get_currency();

			// Only completed orders should be counted in the total sales.
			if ( Completed::SLUG === $status_slug ) {
				$this->total_sales['qty']    += $this->event_sales_by_status[ $status_slug ]['qty_sold'];
				$this->total_sales['amount'] += $this->event_sales_by_status[ $status_slug ]['total_sales_amount'];
				$this->total_sales['price']  = Value::create( $this->total_sales['amount'] )->get_currency();
			}
		}
	}

	/**
	 * Get the event sales data.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_event_sales_data(): array {

		$data = [
			'by_status'     => $this->event_sales_by_status,
			'total_sales'   => $this->total_sales,
			'total_ordered' => $this->total_ordered,
		];

		/**
		 * Filters the event sales data in the order summary report.
		 *
		 * @since TBD
		 *
		 * @param array $event_sales_data The event sales data.
		 * @param int   $post_id The post ID.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_event_sales_data', $data, $this->post_id );
	}
}