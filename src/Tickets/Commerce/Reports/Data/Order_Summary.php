<?php
/**
 * Order Summary Class to generate data for Order report page header sections.
 *
 * @since 5.6.7
 *
 * @package TEC\Tickets\Commerce\Reports\Data
 */

namespace TEC\Tickets\Commerce\Reports\Data;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets;

/**
 * Class Order_Summary.
 *
 * @since 5.6.7
 *
 * @package TEC\Tickets\Commerce\Reports\Data
 */
class Order_Summary {

	use Is_Ticket;

	/**
	 * @since 5.6.7
	 *
	 * @var int The post ID.
	 */
	protected int $post_id;

	/**
	 * @since 5.6.7
	 *
	 * @var Ticket_Object[] The tickets.
	 */
	protected array $tickets = [];

	/**
	 * @since 5.6.7
	 *
	 * @var array<string,array<string,mixed>> The tickets by type.
	 */
	protected array $tickets_by_type = [];

	/**
	 * @since 5.6.7
	 *
	 * @var array<string,array{label: string, qty_sold: int, total_sales_amount: float|string, total_sales_price: string}> The event sales by status.
	 */
	protected array $event_sales_by_status = [];

	/**
	 * @since 5.6.7
	 *
	 * @var array{qty: int, amount: float|string, price: string} The total sales.
	 */
	protected array $total_sales = [];

	/**
	 * @since 5.6.7
	 *
	 * @var array{qty: int, amount: float|string, price: string} The total ordered.
	 */
	protected array $total_ordered = [];

	/**
	 * @since 5.6.7
	 *
	 * @var array<string, array> All event sales data.
	 */
	protected array $event_sales_data = [];

	/**
	 * Order_Summary constructor.
	 *
	 * @since 5.6.7
	 *
	 * @param int $post_id The post ID.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$this->init_vars();
		$this->build_data();
	}

	/**
	 * Get the post ID.
	 *
	 * @since 5.8.0
	 *
	 * @return int The post ID.
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Format the price.
	 *
	 * @since 5.6.7
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
	 * @since 5.6.7
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
	 * Add the ticket available data.
	 *
	 * @since 5.6.7
	 *
	 * @param array<string,int> $quantities The quantities.
	 * @param Ticket_Object     $ticket     The ticket object.
	 *
	 * @return array<string,int> The quantities with stock data added.
	 */
	protected function add_available_data( array $quantities, Ticket_Object $ticket ): array {
		$ticket_available = $ticket->available();

		$quantities[ $ticket->availability_slug() ] = -1 === $ticket_available ? __( 'Unlimited', 'event-tickets' ) : $ticket_available;
		return $quantities;
	}

	/**
	 * Build the data.
	 *
	 * @since 5.6.7
	 */
	protected function build_data(): void {
		foreach ( $this->get_tickets() as $ticket ) {
			$quantities = array_filter( tribe( Ticket::class )->get_status_quantity( $ticket->ID ) );
			// We need to show the total available for each ticket type.
			$quantities = $this->add_available_data( $quantities, $ticket );

			$ticket_data = [
				'ticket'        => $ticket,
				'label'         => sprintf( '%1$s %2$s', $ticket->name, $this->format_price( $ticket->price ) ),
				'type'          => $ticket->type(),
				'qty_data'      => $quantities,
				'qty_by_status' => implode( ' | ', array_map( fn( $k, $v ) => "$v $k", array_keys( $quantities ), $quantities ) ),
			];

			$this->tickets_by_type[ $ticket->type ][] = $ticket_data;
		}

		$this->build_order_sales_data();
	}

	/**
	 * Build the order sales data.
	 *
	 * @since 5.9.0
	 */
	protected function build_order_sales_data() {
		$args = [
			'events' => $this->get_post_id(),
			'status' => 'any',
		];

		$orders = tec_tc_orders()->by_args( $args )->all();

		foreach ( $orders as $order ) {
			foreach ( $order->items as $item ) {
				$this->process_order_sales_data( $order->status_slug, $item );
			}
		}
	}

	/**
	 * Process the order sales data.
	 *
	 * @since 5.9.0
	 *
	 * @param string            $status_slug The status slug.
	 * @param array<string,int> $item The ticket item data.
	 */
	protected function process_order_sales_data( string $status_slug, $item ): void {
		if ( ! $this->is_ticket( $item ) ) {
			return;
		}

		$ticket_id = $item['ticket_id'];
		$tickets   = $this->get_tickets();

		if ( ! isset( $tickets[ $ticket_id ] ) || ! $this->should_include_event_sales_data( $tickets[ $ticket_id ], $item ) ) {
			return;
		}

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

		$sales_amount = $item['sub_total'];
		$quantity     = $item['quantity'];

		$this->event_sales_by_status[ $status_slug ]['qty_sold']           += $quantity;
		$this->event_sales_by_status[ $status_slug ]['total_sales_amount'] += $sales_amount;
		$this->event_sales_by_status[ $status_slug ]['total_sales_price']   = $this->format_price( $this->event_sales_by_status[ $status_slug ]['total_sales_amount'] );

		// process the total ordered data.
		$this->total_ordered['qty']    += $quantity;
		$this->total_ordered['amount'] += $sales_amount;
		$this->total_ordered['price']   = $this->format_price( $this->total_ordered['amount'] );

		// Only completed orders should be counted in the total sales.
		if ( Completed::SLUG === $status_slug ) {
			$this->total_sales['qty']    += $quantity;
			$this->total_sales['amount'] += $sales_amount;
			$this->total_sales['price']   = $this->format_price( $this->total_sales['amount'] );
		}
	}

	/**
	 * Process the event sales data.
	 *
	 * @since 5.6.7
	 * @since 5.9.0 Replaced with process_order_sales_data.
	 *
	 * @param array<string,int> $quantity_by_status The quantity by status.
	 * @param Ticket_Object     $ticket The ticket object.
	 */
	protected function process_event_sales_data( array $quantity_by_status, Ticket_Object $ticket ): void {
		// Do nothing for TicketsCommerce.
	}

	/**
	 * Build the event sales data.
	 *
	 * @since 5.6.7
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
	 * @since 5.6.7
	 *
	 * @return Ticket_Object[] A list of tickets associated with the post.
	 */
	public function get_tickets(): array {
		if ( ! empty( $this->tickets ) ) {
			return $this->tickets;
		}

		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $this->post_id );
		$tickets  = $provider->get_tickets( $this->post_id );

		foreach ( $tickets as $ticket ) {
			$this->tickets[ $ticket->ID ] = $ticket;
		}

		/**
		 * Filters the tickets in the order summary report.
		 *
		 * @since 5.6.7
		 *
		 * @param Ticket_Object[] $tickets A list of tickets associated with the post.
		 * @param Order_Summary $this The order summary object.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_tickets', $this->tickets, $this );
	}

	/**
	 * Get the tickets by type.
	 *
	 * @since 5.6.7
	 *
	 * @return array<string, array{ ticket: Ticket_Object, label: string, type: string, qty_data: array, qty_by_status: string }> A map from ticket types to the Tickets of that type.
	 */
	public function get_tickets_by_type(): array {
		// Sort the tickets by keys to display default types first.
		ksort( $this->tickets_by_type );

		/**
		 * Filters the tickets by type in the order summary report.
		 *
		 * @since 5.6.7
		 *
		 * @param array<string, array{ ticket: Ticket_Object, label: string, type: string, qty_data: array, qty_by_status: string }> $tickets_by_type The tickets by type.
		 * @param Order_Summary $this The order summary object.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_tickets_by_type', $this->tickets_by_type, $this );
	}

	/**
	 * Get the ticket type label.
	 *
	 * @since 5.6.7
	 *
	 * @param string $type The ticket type.
	 *
	 * @return string The ticket type label.
	 */
	public function get_label_for_type( string $type ): string {
		if ( 'default' === $type ) {
			$type = tec_tickets_get_default_ticket_type_label_plural( 'order report summary' );
		}

		/**
		 * Filters the label for a ticket type in the order summary report.
		 *
		 * @since 5.6.7
		 *
		 * @param string $type The ticket type.
		 * @param Order_Summary $this The post ID.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_label_for_type', $type, $this );
	}

	/**
	 * Get the event sales data.
	 *
	 * @since 5.6.7
	 *
	 * @return array<string, array> The event sales data.
	 */
	public function get_event_sales_data(): array {
		$this->build_event_sales_data();

		/**
		 * Filters the event sales data in the order summary report.
		 *
		 * @since 5.6.7
		 *
		 * @param array<string, array> $event_sales_data The event sales data.
		 * @param Order_Summary $this The order summary object.
		 */
		return apply_filters( 'tec_tickets_commerce_order_report_summary_event_sales_data', $this->event_sales_data, $this );
	}

	/**
	 * Get if the ticket sales data should be included into event sales data.
	 *
	 * @since 5.8.0
	 *
	 * @param Ticket_Object     $ticket The ticket object.
	 * @param array<string,int> $quantity_by_status The quantity by status.
	 *
	 * @return bool Whether to include the sales data into event sales data.
	 */
	private function should_include_event_sales_data( Ticket_Object $ticket, array $quantity_by_status ) {
		/**
		 * Filters if the ticket sales data should be included into event sales data.
		 *
		 * @since 5.8.0
		 *
		 * @param bool $should_include Whether to include the sales data into event sales data.
		 * @param Ticket_Object $ticket The ticket object.
		 * @param array<string,int> $quantity_by_status The quantity by status.
		 * @param Order_Summary $this The order summary object.
		 */
		return apply_filters(
			'tec_tickets_commerce_order_report_summary_should_include_event_sales_data',
			true,
			$ticket,
			$quantity_by_status,
			$this
		);
	}
}
