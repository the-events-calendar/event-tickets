<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Not_Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Status.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Status {

	/**
	 * Convert the Square payment status to a Tickets Commerce status.
	 *
	 * @since TBD
	 *
	 * @param string $status Square payment status string.
	 *
	 * @return Status_Interface
	 */
	public function convert_to_commerce_status( string $status ): Status_Interface {
		switch ( $status ) {
			case 'APPROVED':
			case 'COMPLETED':
				return tribe( Completed::class );
			case 'PENDING':
			case 'OPEN':
			case 'DRAFT':
				return tribe( Pending::class );
			case 'FAILED':
			case 'CANCELED':
				return tribe( Denied::class );
			default:
				return tribe( Not_Completed::class );
		}
	}

	/**
	 * Convert payment data to a Commerce status.
	 *
	 * @since TBD
	 *
	 * @param array $payment The Square payment data.
	 *
	 * @return Status_Interface|null
	 */
	public function convert_payment_to_commerce_status( array $payment ): ?Status_Interface {
		if ( empty( $payment ) || ! is_array( $payment ) ) {
			return null;
		}

		if ( ! isset( $payment['status'] ) ) {
			return null;
		}

		return $this->convert_to_commerce_status( $payment['status'] );
	}

	/**
	 * Convert a payment refund to a Commerce status.
	 *
	 * @since TBD
	 *
	 * @param array $refund The Square refund data.
	 *
	 * @return Status_Interface|null
	 */
	public function convert_refund_to_commerce_status( array $refund ): ?Status_Interface {
		if ( empty( $refund ) || ! is_array( $refund ) ) {
			return null;
		}

		if ( ! isset( $refund['status'] ) ) {
			return null;
		}

		// Process refund status.
		if ( in_array( $refund['status'], [ 'COMPLETED', 'APPROVED' ], true ) ) {
			return tribe( Refunded::class );
		}

		return $this->convert_to_commerce_status( $refund['status'] );
	}
}
