<?php
/**
 * Singular order status notices.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin
 */

namespace TEC\Tickets\Commerce\Admin;

/**
 * Class Singular_Order_Notices
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Singular_Order_Notices {
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_STATUS_SUCCESSFULLY_UPDATED = 'updated';
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_STATUS_REFUNDED = 'status-refunded';
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_SUCCESSFULLY_REFUNDED = 'refunded';
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_PARTIALLY_REFUNDED = 'partial-refunded';
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_STATUS_CHANGE_FAILED = 'change-failed';
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_REFUND_FAILED = 'refund-failed';
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_GATEWAY_REFUND_FAILED = 'gateway-refund-failed';
	/**
	 * @since TBD
	 *
	 * @var string Notice key.
	 */
	const ORDER_GATEWAY_PARTIAL_REFUND_FAILED = 'gateway-partial-refund-failed';

	/**
	 * List of error notices for the admin.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected array $error_messages = [
		self::ORDER_GATEWAY_PARTIAL_REFUND_FAILED,
		self::ORDER_REFUND_FAILED,
		self::ORDER_GATEWAY_REFUND_FAILED,
		self::ORDER_STATUS_CHANGE_FAILED,
	];

	/**
	 * List of successful admin notices.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected array $success_messages = [
		self::ORDER_STATUS_REFUNDED,
		self::ORDER_STATUS_SUCCESSFULLY_UPDATED,
		self::ORDER_SUCCESSFULLY_REFUNDED,
		self::ORDER_PARTIALLY_REFUNDED,
	];

	/**
	 * This array contains the memoized translated order notices.
	 *
	 * @since TBD
	 *
	 * @var array<string,string> List of translated order notices.
	 */
	protected static array $generated_messages = [];

	/**
	 * @since TBD
	 *
	 * @return string[] Success message keys.
	 */
	public function get_success_keys(): array {
		return $this->success_messages;
	}

	/**
	 * @since TBD
	 *
	 * @return string[] Error message keys.
	 */
	public function get_error_keys(): array {
		return $this->error_messages;
	}

	/**
	 * Generates and returns the list of translated admin notices.
	 *
	 * @since TBD
	 *
	 * @return array<string, string> Key value of the translated admin notices.
	 */
	public function messages(): array {
		if ( ! empty( self::$generated_messages ) ) {
			return self::$generated_messages;
		}

		self::$generated_messages = [
			/**
			 * Success messages.
			 */
			/* translators: %1$s: order number. %2$s: status the order was changed from. %3$s: status the order was changed to. */
			self::ORDER_STATUS_SUCCESSFULLY_UPDATED   => _x(
				'Order #%1$s has been successfully updated from %2$s to %3$s',
				'The message after an order status was changed successfully.',
				'event-tickets'
			),
			/* translators: %1$s: order number. */
			self::ORDER_STATUS_REFUNDED               => _x(
				'Order #%1$s has been marked as refunded. Remember to process the refund in your payment gateway.',
				'The message after an order status was changed to refunded but requires an update in the payment gateway.',
				'event-tickets'
			),
			/* translators: %1$s: order number. %2$s: the payment gateway that processed the refund. */
			self::ORDER_SUCCESSFULLY_REFUNDED         => _x(
				'Order #%1$s has been successfully refunded via %2$s',
				'The message after an order status was changed to refunded and refunded by the payment gateway.',
				'event-tickets'
			),
			/* translators: %1$s: order number. %2$s: the currency code, e.g. USD. %3$s: the amount refunded. */
			self::ORDER_PARTIALLY_REFUNDED            => _x(
				'Order #%1$s has been partially refunded %2$s %3$s',
				'The message after an order status was changed to refunded and partially refunded by the payment gateway.',
				'event-tickets'
			),
			/**
			 * Error messages.
			 */
			/* translators: %1$s: order number. %2$s: the current status. %3$s: the status to apply. */
			self::ORDER_STATUS_CHANGE_FAILED          => _x(
				'Order #%1$s could not be changed from %2$s to %3$s. Please try again or get help.',
				'The message after an order status failed to change.',
				'event-tickets'
			),
			/* translators: %1$s: order number. */
			self::ORDER_REFUND_FAILED                 => _x(
				'Order #%1$s could not be marked as refunded. Please try again. You can still refund directly in the payment gateway.',
				'The message after an order refund failed.',
				'event-tickets'
			),
			/* translators: %1$s: order number. %2$s: payment gateway. */
			self::ORDER_GATEWAY_REFUND_FAILED         => _x(
				'Order #%1$s could not be refunded via %2$s. Please try again or contact us for help.',
				'The message after an order refund failed in the gateway.',
				'event-tickets'
			),
			/* translators: %1$s: refund amount. %2$s: order number. %3$s: payment gateway. */
			self::ORDER_GATEWAY_PARTIAL_REFUND_FAILED => _x(
				'The partial refund of %1$s could not be processed for Order #%2$s via %3$s.',
				'The message after a partial order refund failed in the gateway.',
				'event-tickets'
			),
		];

		return self::$generated_messages;
	}

	/**
	 * Registers the admin notice to be displayed.
	 *
	 * @since TBD
	 *
	 * @param string $message_code The message key to be displayed.
	 * @param mixed  ...$params    [optional] Dynamic params for the message.
	 */
	public function do_message( string $message_code, ...$params ) {
		$success = in_array( $message_code, $this->success_messages );

		tribe_transient_notice(
			'tec-tickets-commerce-order-status-update-notice',
			$this->get_message( $message_code, ...$params ),
			[
				'type'     => $success ? 'success' : 'error',
				'dismiss'  => true,
				'action'   => 'admin_notices',
				'priority' => 1,
			],
			1
		);
	}

	public function get_message( $message_code, ...$params) {
		$message = sprintf( $this->messages()[ $message_code ], ...$params );

		return "<p>$message</p>";
	}
}
