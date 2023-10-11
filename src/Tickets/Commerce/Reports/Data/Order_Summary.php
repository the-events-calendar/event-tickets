<?php
namespace TEC\Tickets\Commerce\Reports\Data;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets;

/**
 * Class Order_Summary.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Reports\Data
 */
class Order_Summary {
	/**
	 * @var int The post ID.
	 */
	private int $post_id;

	/**
	 * @var Ticket_Object[] The tickets.
	 */
	private array $tickets;

	/**
	 * @var array|array[] The tickets by type.
	 */
	private array $tickets_by_type;

	/**
	 * @var array|array[] The event sales by status.
	 */
	private array $event_sales_by_status = [];

	/**
	 * @var array|array[] The total sales.
	 */
	private array $total_sales = [];

	/**
	 * @var array|array[] The total ordered.
	 */
	private array $total_ordered = [];

	/**
	 * Order_Summary constructor.
	 *
	 * @param int $post_id The post ID.
	 */
	public function __construct( int $post_id ) {
		$this->post_id       = $post_id;
		$this->total_sales   = [
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

	/**
	 * Build the data.
	 *
	 * @since TBD
	 *
	 */
	protected function build_data(): void {
		foreach ( $this->get_tickets() as $ticket ) {
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
	 * Get the tickets.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_tickets(): array {
		$provider      = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $this->post_id );
		$this->tickets = $provider->get_tickets( $this->post_id );

		/**
		 * Filters the tickets in the order summary report.
		 *
		 * @since TBD
		 *
		 * @param array $tickets The tickets.
		 * @param Order_Summary $this The order summary object.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_tickets', $this->tickets, $this );
	}

	/**
	 * Get the tickets by type.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_tickets_by_type(): array {
		/**
		 * Filters the tickets by type in the order summary report.
		 *
		 * @since TBD
		 *
		 * @param array $tickets_by_type The tickets by type.
		 * @param Order_Summary $this The order summary object.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_tickets_by_type', $this->tickets_by_type, $this );
	}

	/**
	 * Get the ticket type label.
	 *
	 * @since TBD
	 *
	 * @param string $type The ticket type.
	 *
	 * @return string The ticket type label.
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
		 * @param Order_Summary $this The post ID.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_label_for_type', $type, $this );
	}

	/**
	 * Process the event sales data.
	 *
	 * @since TBD
	 *
	 * @param array $quantity_by_status The quantity by status.
	 * @param Ticket_Object $ticket The ticket object.
	 */
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