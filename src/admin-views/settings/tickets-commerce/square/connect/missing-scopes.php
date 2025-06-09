<?php
/**
 * The Template for displaying the Square missing scopes warning.
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 *
 * @var Tribe__Tickets__Admin__Views                  $this              [Global] Template object.
 * @var array                                         $scope_verification [Global] The scope verification results.
 * @var bool                                          $has_missing_scopes [Global] Whether there are missing scopes.
 */

defined( 'ABSPATH' ) || exit;
// Verify merchant has all required scopes for Square integration.
$scope_verification = tribe( \TEC\Tickets\Commerce\Gateways\Square\WhoDat::class )->verify_merchant_scopes();
$has_missing_scopes = ! empty( $scope_verification['missing_scopes'] );

$test_mode = TEC\Tickets\Commerce\Gateways\Square\Gateway::is_test_mode();

// Only show this if there are missing scopes.
if ( ! $has_missing_scopes || empty( $scope_verification['missing_scopes'] ) ) {
	return;
}
?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row tec-tickets__admin-settings-tickets-commerce-gateway-connected-warning">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-warning-message tec-tickets__admin-settings-tickets-commerce-gateway-connected-message" id="square-scope-warning-message">
		<span class="dashicons dashicons-warning" aria-hidden="true"></span>
		<?php esc_html_e( 'Your Square connection is missing required permissions. This may cause payment processing issues. Please reconnect your account to update permissions.', 'event-tickets' ); ?>
	</div>
	<?php $this->template( 'settings/tickets-commerce/square/connect/sandbox-notice' ); ?>
	<a
		href="#"
		class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button-link tec-tickets__admin-settings-tickets-commerce-gateway-reconnect-square-button"
		id="tec-tickets__admin-settings-tickets-commerce-gateway-reconnect-square"
		aria-describedby="square-scope-warning-message"
		data-required-scopes="<?php echo esc_attr( implode( ',', $scope_verification['missing_scopes'] ) ); ?>"
	>
		<?php esc_html_e( 'Reconnect Account', 'event-tickets' ); ?>
	</a>
</div>
