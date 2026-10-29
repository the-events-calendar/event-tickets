<?php
/**
 * The sales summary of a recurring event's Orders page.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use TEC\Tickets\Commerce\Reports\Data\Order_Summary as Base_Order_Summary;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;

/**
 * Sums a recurring event's sales over every date, by template, and those of its next date apart.
 *
 * The orders of a row hold its date's ID and the row's ticket ID: the summary reads them as their template's, the
 * ticket the event lists.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Order_Summary extends Base_Order_Summary {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The sales of each template over every date.
	 *
	 * @since TBD
	 *
	 * @var Template_Sales
	 */
	private Template_Sales $template_sales;

	/**
	 * The event's dates: the start by the date's post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,string>
	 */
	private array $dates;

	/**
	 * The next date, 0 when none.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $next_date;

	/**
	 * The label of the next date.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $next_date_label;

	/**
	 * The quantity of each template ordered, by status.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<string,int>>
	 */
	private array $template_quantities = [];

	/**
	 * The sales of the next date.
	 *
	 * @since TBD
	 *
	 * @var array{by_status: array<string,array{label: string, qty_sold: int, total_sales_amount: float, total_sales_price: string}>, total_sales: array{qty: int, amount: float, price: string}}
	 */
	private array $next_date_sales = [
		'by_status'   => [],
		'total_sales' => [
			'qty'    => 0,
			'amount' => 0.0,
			'price'  => '',
		],
	];

	/**
	 * Order_Summary constructor.
	 *
	 * @since TBD
	 *
	 * @param int               $post_id         The event.
	 * @param Rows              $rows            The rows repository.
	 * @param Template_Sales    $template_sales  The sales of each template over every date.
	 * @param array<int,string> $dates           The event's dates: the start by the date's post ID.
	 * @param int               $next_date       The next date, 0 when none.
	 * @param string            $next_date_label The label of the next date.
	 */
	public function __construct( int $post_id, Rows $rows, Template_Sales $template_sales, array $dates, int $next_date, string $next_date_label ) {
		parent::__construct( $post_id );
		$this->rows            = $rows;
		$this->template_sales  = $template_sales;
		$this->dates           = $dates;
		$this->next_date       = $next_date;
		$this->next_date_label = $next_date_label;
	}

	/**
	 * Returns the next date.
	 *
	 * @since TBD
	 *
	 * @return int The date's post ID, 0 when none.
	 */
	public function get_next_date(): int {
		return $this->next_date;
	}

	/**
	 * Returns the label of the next date.
	 *
	 * @since TBD
	 *
	 * @return string The label.
	 */
	public function get_next_date_label(): string {
		return $this->next_date_label;
	}

	/**
	 * Returns the sales of the next date.
	 *
	 * @since TBD
	 *
	 * @return array{by_status: array<string,array{label: string, qty_sold: int, total_sales_amount: float, total_sales_price: string}>, total_sales: array{qty: int, amount: float, price: string}} The sales.
	 */
	public function get_next_date_sales(): array {
		return $this->next_date_sales;
	}

	/**
	 * Returns the label of a ticket type: the recurring event tickets' says which dates their sales are of.
	 *
	 * @since TBD
	 *
	 * @param string $type The ticket type.
	 *
	 * @return string The label.
	 */
	public function get_label_for_type( string $type ): string {
		if ( Template_Guard::TICKET_TYPE === $type ) {
			return __( 'Recurring Event Tickets · all dates', 'event-tickets' );
		}

		return parent::get_label_for_type( $type );
	}

	/**
	 * Returns the title of the sales totals: they are of every date.
	 *
	 * @since TBD
	 *
	 * @return string The title.
	 */
	public function get_sales_totals_title(): string {
		return __( 'All Dates', 'event-tickets' );
	}

	/**
	 * Builds the data: the orders first, since they hold the only sales of a template.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function build_data(): void {
		$this->build_order_sales_data();

		$sales = $this->template_sales->for_event( $this->get_post_id() );
		foreach ( $this->get_tickets() as $ticket ) {
			if ( Template_Guard::TICKET_TYPE === $ticket->type() ) {
				$this->tickets_by_type[ $ticket->type ][] = $this->template_line( $ticket, $ticket->type, $this->template_quantities[ (int) $ticket->ID ] ?? [], $sales[ (int) $ticket->ID ]['available'] ?? 0 );
				continue;
			}

			$quantities = $this->add_available_data( array_filter( tribe( Commerce_Ticket::class )->get_status_quantity( $ticket->ID ) ), $ticket );

			$this->tickets_by_type[ $ticket->type ][] = $this->line( $ticket, $ticket->type(), $quantities );
		}

		$this->format_prices();

		foreach ( $this->next_date_sales['by_status'] as &$status_sales ) {
			$status_sales['total_sales_price'] = Currency_Value::create_from_float( $status_sales['total_sales_amount'] )->get();
		}
		unset( $status_sales );
		$this->next_date_sales['total_sales']['price'] = Currency_Value::create_from_float( $this->next_date_sales['total_sales']['amount'] )->get();
	}

	/**
	 * Reads the orders of the event and of each of its dates.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function build_order_sales_data() {
		$orders = tec_tc_orders()->by_args(
			[
				'events' => array_merge( [ $this->get_post_id() ], array_keys( $this->dates ) ),
				'status' => 'any',
			]
		)->all();

		foreach ( $orders as $order ) {
			$this->process_order_sales_data( $order );
		}
	}

	/**
	 * Reads an order's rows as their templates, counting what each sold over every date, and the sales of the next date.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order.
	 *
	 * @return void
	 */
	protected function process_order_sales_data( WP_Post $order ): void {
		$order = clone $order;
		$items = (array) $order->items;
		foreach ( $items as &$item ) {
			$template_id = $this->is_ticket( $item ) ? $this->template_of( (int) $item['ticket_id'] ) : 0;

			if ( ! $template_id ) {
				continue;
			}

			$status_slug = (string) $order->status_slug;
			$quantity    = (int) $item['quantity'];

			$this->template_quantities[ $template_id ][ $status_slug ] = ( $this->template_quantities[ $template_id ][ $status_slug ] ?? 0 ) + $quantity;

			if ( $this->next_date && $this->next_date === (int) ( $item['event_id'] ?? 0 ) ) {
				$this->add_next_date_sale( $status_slug, $quantity, (float) $item['sub_total'] );
			}

			$item['ticket_id'] = $template_id;
		}
		unset( $item );

		$fees = (array) $order->fees;
		foreach ( $fees as &$fee ) {
			$fee['ticket_id'] = $this->template_of( (int) $fee['ticket_id'] ) ?: $fee['ticket_id'];
		}
		unset( $fee );

		$order->items = $items;
		$order->fees  = $fees;

		parent::process_order_sales_data( $order );
	}

	/**
	 * Returns the Sales by Ticket line of a template.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object     $template   The template.
	 * @param string            $type       The type the line is listed under.
	 * @param array<string,int> $quantities The quantity ordered, by status.
	 * @param int               $available  The quantity available, -1 when unlimited.
	 *
	 * @return array{ticket: Ticket_Object, label: string, type: string, qty_data: array<string,int|string>, qty_by_status: string} The line.
	 */
	private function template_line( Ticket_Object $template, string $type, array $quantities, int $available ): array {
		$quantities = array_filter( $quantities );

		$quantities[ $template->availability_slug() ] = -1 === $available ? __( 'Unlimited', 'event-tickets' ) : $available;

		return $this->line( $template, $type, $quantities );
	}

	/**
	 * Returns a Sales by Ticket line.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object            $ticket     The ticket.
	 * @param string                   $type       The type the line is listed under.
	 * @param array<string,int|string> $quantities The quantity of each status, and the availability.
	 *
	 * @return array{ticket: Ticket_Object, label: string, type: string, qty_data: array<string,int|string>, qty_by_status: string} The line.
	 */
	private function line( Ticket_Object $ticket, string $type, array $quantities ): array {
		return [
			'ticket'        => $ticket,
			'label'         => sprintf( '%1$s %2$s', $ticket->name, $this->format_price( $ticket->price ) ),
			'type'          => $type,
			'qty_data'      => $quantities,
			'qty_by_status' => implode( ' | ', array_map( static fn( $k, $v ) => "$v $k", array_keys( $quantities ), $quantities ) ),
		];
	}

	/**
	 * Returns the template of a row's ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int The template's ID, 0 when the ticket is not a row's.
	 */
	private function template_of( int $ticket_id ): int {
		if ( ! Ticket_ID::is_table_ticket( $ticket_id ) ) {
			return 0;
		}

		// ponytail: a removed date's row is gone with its template link; its orders stay listed, but out of the sums.
		$row = $this->rows->find( Ticket_ID::to_row_id( $ticket_id ) );

		return $row ? (int) $row->parent_id : 0;
	}

	/**
	 * Adds an order line of the next date to its sales.
	 *
	 * @since TBD
	 *
	 * @param string $status_slug The order's status.
	 * @param int    $quantity    The quantity.
	 * @param float  $amount      The line's amount.
	 *
	 * @return void
	 */
	private function add_next_date_sale( string $status_slug, int $quantity, float $amount ): void {
		if ( ! isset( $this->next_date_sales['by_status'][ $status_slug ] ) ) {
			$this->next_date_sales['by_status'][ $status_slug ] = [
				'label'              => tribe( Status_Handler::class )->get_by_slug( $status_slug )->get_name(),
				'qty_sold'           => 0,
				'total_sales_amount' => 0.0,
				'total_sales_price'  => '',
			];
		}

		$this->next_date_sales['by_status'][ $status_slug ]['qty_sold']           += $quantity;
		$this->next_date_sales['by_status'][ $status_slug ]['total_sales_amount'] += $amount;

		if ( Completed::SLUG === $status_slug ) {
			$this->next_date_sales['total_sales']['qty']    += $quantity;
			$this->next_date_sales['total_sales']['amount'] += $amount;
		}
	}
}
