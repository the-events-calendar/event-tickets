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
use TEC\Tickets\Commerce\Traits\Type;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets;
use WP_Post;

/**
 * Class Order_Summary.
 *
 * @since 5.6.7
 *
 * @package TEC\Tickets\Commerce\Reports\Data
 */
class Order_Summary {

	use Type;

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
	 * @since 5.21.0
	 *
	 * @var Currency_Value[] The event completed fees.
	 */
	protected array $event_completed_fees = [];

	/**
	 * @since 5.21.0
	 *
	 * @var Currency_Value[] The event completed discounts.
	 */
	protected array $event_completed_discounts = [];

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
	}

	/**
	 * Initialize the data.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	public function init() {
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
			'qty'             => 0,
			'amount'          => 0,
			'price'           => $this->format_price( 0 ),
			'total_fees'      => $this->format_price( 0 ),
			'fees_qty'        => 0,
			'total_discounts' => $this->format_price( 0 ),
			'discounts_qty'   => 0,
		];

		$this->total_ordered = [
			'qty'             => 0,
			'amount'          => 0,
			'price'           => $this->format_price( 0 ),
			'total_fees'      => $this->format_price( 0 ),
			'fees_qty'        => 0,
			'total_discounts' => $this->format_price( 0 ),
			'discounts_qty'   => 0,
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
		$this->format_prices();
	}

	/**
	 * Format the prices for all of the data.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	protected function format_prices() {
		$all_fees      = [];
		$all_discounts = [];

		// Handle the event sales by status.
		foreach ( $this->event_sales_by_status as &$data ) {
			// Add the fees and discounts to their respective arrays.
			$all_fees      = array_merge( $all_fees, $data['total_fee_amounts'] );
			$all_discounts = array_merge( $all_discounts, $data['total_discount_amounts'] );

			// Update the different prices with the formatted values.
			$data['total_sales_price']    = Currency_Value::create_from_float( $data['total_sales_amount'] )->get();
			$data['total_fee_price']      = Currency_Value::sum( ...$data['total_fee_amounts'] )->get();
			$data['total_discount_price'] = Currency_Value::sum( ...$data['total_discount_amounts'] )->get();
		}

		// Handle the total ordered.
		$this->total_ordered['price']           = Currency_Value::create_from_float( $this->total_ordered['amount'] )->get();
		$this->total_ordered['total_fees']      = Currency_Value::sum( ...$all_fees )->get();
		$this->total_ordered['total_discounts'] = Currency_Value::sum( ...$all_discounts )->get();

		// Handle the total sales.
		$this->total_sales['price']           = Currency_Value::create_from_float( $this->total_sales['amount'] )->get();
		$this->total_sales['total_fees']      = Currency_Value::sum( ...$this->event_completed_fees )->get();
		$this->total_sales['total_discounts'] = Currency_Value::sum( ...$this->event_completed_discounts )->get();
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
			$this->process_order_sales_data( $order );
		}
	}

	/**
	 * Process the order sales data.
	 *
	 * @since 5.9.0
	 * @since 5.21.0 Updated this method to handle an order directly, instead of individual items.
	 *
	 * @param WP_Post $order The order object with extra properties.
	 */
	protected function process_order_sales_data( WP_Post $order ): void {
		// Handle items first.
		foreach ( $order->items as $item ) {
			if ( $this->is_ticket( $item ) ) {
				$this->process_ticket_item_data( $order->status_slug, $item );
			}
		}

		// Handle fees.
		foreach ( $order->fees as $fee ) {
			if ( ! isset( $this->tickets[ $fee['ticket_id'] ] ) ) {
				continue;
			}
			$this->process_fee_item_data( $order->status_slug, $fee );
		}

		// Handle coupons.
		foreach ( $order->coupons as $coupon ) {
			$this->process_coupon_item_data( $order->status_slug, $coupon );
		}
	}

	/**
	 * Process the ticket item data.
	 *
	 * @since 5.21.0
	 *
	 * @param string $status_slug The status slug.
	 * @param array  $item        The item. It should already be validated as a ticket.
	 *
	 * @return void
	 */
	protected function process_ticket_item_data( string $status_slug, array $item ) {
		$ticket_id = $item['ticket_id'];
		$tickets   = $this->get_tickets();

		if ( ! isset( $tickets[ $ticket_id ] ) || ! $this->should_include_event_sales_data( $tickets[ $ticket_id ], $item ) ) {
			return;
		}

		$this->maybe_initialize_status( $status_slug );

		$sales_amount = $item['sub_total'];
		$quantity     = $item['quantity'];

		$this->event_sales_by_status[ $status_slug ]['qty_sold']           += $quantity;
		$this->event_sales_by_status[ $status_slug ]['total_sales_amount'] += $sales_amount;

		// process the total ordered data.
		$this->total_ordered['qty']    += $quantity;
		$this->total_ordered['amount'] += $sales_amount;

		// Only completed orders should be counted in the total sales.
		if ( Completed::SLUG === $status_slug ) {
			$this->total_sales['qty']    += $quantity;
			$this->total_sales['amount'] += $sales_amount;
		}
	}

	/**
	 * Process the fee item data.
	 *
	 * @since 5.21.0
	 *
	 * @param string $status_slug The status slug.
	 * @param array  $item        The item.
	 *
	 * @return void
	 */
	protected function process_fee_item_data( string $status_slug, array $item ) {
		$amount = Currency_Value::create_from_float( $item['sub_total'] );

		// Add the fee amount to the total fees for the status.
		$this->event_sales_by_status[ $status_slug ]['total_fee_amounts'][] = $amount;

		// Include the fee quantity in the total ordered.
		$this->total_ordered['fees_qty'] += $item['quantity'];

		// Include the fee data in the total sales.
		if ( Completed::SLUG === $status_slug ) {
			$this->total_sales['amount']   += $item['sub_total'];
			$this->total_sales['fees_qty'] += $item['quantity'];
			$this->event_completed_fees[]   = $amount;
		}
	}

	/**
	 * Process the coupon item data.
	 *
	 * @since 5.21.0
	 *
	 * @param string $status_slug The status slug.
	 * @param array  $item        The item.
	 *
	 * @return void
	 */
	protected function process_coupon_item_data( string $status_slug, array $item ) {
		$amount = Legacy_Value_Factory::to_currency_value( $item['sub_total'] );

		// Add the discount amount to the total discounts for the status.
		$this->event_sales_by_status[ $status_slug ]['total_discount_amounts'][] = $amount;

		// Include the discount quantity in the total ordered.
		$this->total_ordered['discounts_qty'] += $item['quantity'];

		// Decrease total sales by the coupon amount. The sub_total is negative, so we can add it.
		if ( Completed::SLUG === $status_slug ) {
			$this->total_sales['amount']        += $item['sub_total']->get_decimal();
			$this->total_sales['discounts_qty'] += $item['quantity'];
			$this->event_completed_discounts[]   = $amount;
		}
	}

	/**
	 * Initialize the data for the status slug if we haven't already.
	 *
	 * @since 5.21.0
	 *
	 * @param string $status_slug The status slug.
	 *
	 * @return void
	 */
	protected function maybe_initialize_status( string $status_slug ) {
		if ( isset( $this->event_sales_by_status[ $status_slug ] ) ) {
			return;
		}

		$status = tribe( Status_Handler::class )->get_by_slug( $status_slug );

		$this->event_sales_by_status[ $status_slug ] = [
			'label'                  => $status->get_name(),
			'qty_sold'               => 0,
			'total_fee_amounts'      => [],
			'total_discount_amounts' => [],
			'total_sales_amount'     => 0,
			'total_sales_price'      => $this->format_price( 0 ),
		];
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
		 * @param Order_Summary $instance  The order summary object.
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
		 * @param Order_Summary $instance  The order summary object.
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
		 * @param string $type             The ticket type.
		 * @param Order_Summary $instance  The order summary object.
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
		 * @param Order_Summary        $instance         The order summary object.
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
		 * @param bool              $should_include     Whether to include the sales data into event sales data.
		 * @param Ticket_Object     $ticket             The ticket object.
		 * @param array<string,int> $quantity_by_status The quantity by status.
		 * @param Order_Summary     $instance           The order summary object.
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
