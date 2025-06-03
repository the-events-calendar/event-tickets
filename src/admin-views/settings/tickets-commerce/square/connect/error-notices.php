<?php
/**
 * The Template for displaying Square connection error notices.
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 *
 * @var Tribe__Tickets__Admin__Views                  $this              [Global] Template object.
 * @var TEC\Tickets\Commerce\Gateways\Square\Merchant $merchant          [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Square\Gateway  $gateway           [Global] The gateway class.
 */

defined( 'ABSPATH' ) || exit;

// Get the status from the URL.
$tc_status = tribe_get_request_var( 'tc-status', '' );

// Bail if no status.
if ( empty( $tc_status ) ) {
	return;
}

$error_class   = 'error';
$error_message = '';

switch ( $tc_status ) {
	case 'tc-square-signup-error':
		$error_message = __( 'There was an error connecting to Square. Please try again.', 'event-tickets' );
		break;
	case 'tc-square-user-denied':
		$error_message = __( 'You have denied the connection to Square. Please try again if this was a mistake.', 'event-tickets' );
		break;
	case 'tc-square-token-error':
		$error_message = __( 'Unable to complete connection to Square. Please try again.', 'event-tickets' );
		break;
	default:
		return;
}

if ( empty( $error_message ) ) {
	return;
}
?>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connection-error notice <?php echo esc_attr( $error_class ); ?>">
	<p><?php echo esc_html( $error_message ); ?></p>
</div>
