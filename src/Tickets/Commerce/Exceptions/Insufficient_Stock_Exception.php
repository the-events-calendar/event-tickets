<?php
/**
 * Exception for handling insufficient stock scenarios during ticket purchases.
 *
 * This file defines the custom exception thrown when users attempt to purchase
 * more tickets than are available, preventing overselling situations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Exceptions
 */

namespace TEC\Tickets\Commerce\Exceptions;

/**
 * Exception thrown when there's insufficient stock for an order.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Exceptions
 */
class Insufficient_Stock_Exception extends \Exception {
	
	/**
	 * Array of stock validation errors.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $stock_errors = [];
	
	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param array           $stock_errors Array of stock validation errors.
	 * @param string          $message      Exception message.
	 * @param int             $code         Exception code.
	 * @param \Throwable|null $previous     Previous exception.
	 */
	public function __construct( array $stock_errors = [], $message = '', $code = 0, \Throwable $previous = null ) {
		$this->stock_errors = $stock_errors;
		
		if ( empty( $message ) ) {
			$message = __( 'Insufficient stock available for requested tickets.', 'event-tickets' );
		}
		
		parent::__construct( $message, $code, $previous );
	}
	
	/**
	 * Get the stock errors.
	 *
	 * @since TBD
	 *
	 * @return array Array of stock validation errors.
	 */
	public function get_stock_errors(): array {
		return $this->stock_errors;
	}
	
	/**
	 * Get a user-friendly error message with ticket details.
	 *
	 * @since TBD
	 *
	 * @return string User-friendly error message.
	 */
	public function get_user_friendly_message(): string {
		if ( empty( $this->stock_errors ) ) {
			return $this->getMessage();
		}
		
		$error_message = __( 'Sorry, some tickets are no longer available:', 'event-tickets' ) . "\n";
		
		foreach ( $this->stock_errors as $error ) {
			$error_message .= sprintf(
				/* translators: %1$s: ticket name, %2$d: available quantity, %3$d: requested quantity */
				__( 'For %1$s: Only %2$d available (you requested %3$d)', 'event-tickets' ),
				$error['ticket_name'],
				$error['available'],
				$error['requested']
			) . "\n";
		}
		
		return $error_message;
	}
} 
 