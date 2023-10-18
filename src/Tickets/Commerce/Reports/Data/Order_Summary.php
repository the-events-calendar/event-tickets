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
	 * @since TBD
	 *
	 * @var int The post ID.
	 */
	protected int $post_id;

	/**
	 * @since TBD
	 *
	 * @var Ticket_Object[] The tickets.
	 */
	protected array $tickets = [];

	/**
	 * @since TBD
	 *
	 * @var array<string,array<string,mixed>> The tickets by type.
	 */
	protected array $tickets_by_type = [];

	/**
	 * @since TBD
	 *
	 * @var array<string,array{label: string, qty_sold: int, total_sales_amount: float|string, total_sales_price: string}> The event sales by status.
	 */
	protected array $event_sales_by_status = [];

	/**
	 * @since TBD
	 *
	 * @var array{qty: int, amount: float|string, price: string} The total sales.
	 */
	protected array $total_sales = [];

	/**
	 * @since TBD
	 *
	 * @var array{qty: int, amount: float|string, price: string} The total ordered.
	 */
	protected array $total_ordered = [];

	/**
	 * @since TBD
	 *
	 * @var array|array[] The total ordered.
	 */
	protected array $event_sales_data = [];

	/**
	 * Order_Summary constructor.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$this->init_vars();
		$this->build_data();
	}

	/**
	 * Format the price.
	 *
	 * @since TBD
	 *
	 * @param string $price The price.
	 *
	 * @return string The formatted price.
	 */
	protected function format_price( string $price ): string {
		return Value::create( $price )->get_currency();
	}

	/**
	 * Initialize the variables.
	 *
	 * @since TBD
	 */
	protected function init_vars(): void {
		$this->total_sales   = [
			'qty'    => 0,
			'amount' => 0,
			'price'  => $this->format_price( 0 ),
		];
		$this->total_ordered = [
			'qty'    => 0,
			'amount' => 0,
			'price'  => $this->format_price( 0 ),
		];
	}

	/**
	 * Build the data.
	 *
	 * @since TBD
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
				'label'         => sprintf( '%1$s %2$s', $ticket->name, $this->format_price( $ticket->price ) ),
				'type'          => $ticket->type(),
				'qty_data'      => $quantities,
				'qty_by_status' => implode( ' | ', array_map( fn( $k, $v ) => "$v $k", array_keys( $quantities ), $quantities ) ),
			];

			$this->tickets_by_type[ $ticket->type ][] = $ticket_data;
		}
	}

	/**
	 * Process the event sales data.
	 *
	 * @since TBD
	 *
	 * @param array<string,int> $quantity_by_status The quantity by status.
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
					'total_sales_price'  => $this->format_price( 0 ),
				];
			}
			$sales_amount = $quantity * $ticket->price;
			$this->event_sales_by_status[ $status_slug ]['qty_sold']           += $quantity;
			$this->event_sales_by_status[ $status_slug ]['total_sales_amount'] += $sales_amount;
			$this->event_sales_by_status[ $status_slug ]['total_sales_price']  = $this->format_price( $this->event_sales_by_status[ $status_slug ]['total_sales_amount'] );

			// process the total ordered data.
			$this->total_ordered['qty']    += $quantity;
			$this->total_ordered['amount'] += $sales_amount;
			$this->total_ordered['price']  = $this->format_price( $this->total_ordered['amount'] );

			// Only completed orders should be counted in the total sales.
			if ( Completed::SLUG === $status_slug ) {
				$this->total_sales['qty']    += $quantity;
				$this->total_sales['amount'] += $sales_amount;
				$this->total_sales['price']  = $this->format_price( $this->total_sales['amount'] );
			}
		}
	}

	/**
	 * Build the event sales data.
	 *
	 * @since TBD
	 */
	protected function build_event_sales_data(): void {
		$this->event_sales_data = [
			'by_status'     => $this->event_sales_by_status,
			'total_sales'   => $this->total_sales,
			'total_ordered' => $this->total_ordered,
		];
	}

	/**
	 * Get the tickets.
	 *
	 * @since TBD
	 *
	 * @return Ticket_Object[]
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
	 * Get the event sales data.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_event_sales_data(): array {
		$this->build_event_sales_data();

		/**
		 * Filters the event sales data in the order summary report.
		 *
		 * @since TBD
		 *
		 * @param array $event_sales_data The event sales data.
		 * @param Order_Summary $this The order summary object.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_event_sales_data', $this->event_sales_data, $this );
	}
}