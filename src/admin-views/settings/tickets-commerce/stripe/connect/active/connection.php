<?php
/**
 * The Template for displaying the Tickets Commerce Stripe connection details.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 */

if ( empty( $is_merchant_connected ) ) {
	return;
}

$name           = $merchant->get_merchant_id();
$disconnect_url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'stripe-disconnect' ] );

?>
<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-col1">
		<?php esc_html_e( 'Connected as:', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-col2">
		<span class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-text-name">
			<?php echo esc_html( $name ); ?>
		</span>
		<a
			href="<?php echo esc_url( $disconnect_url ); ?>"
			class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-text-disconnect-link"
		>
			<?php esc_html_e( 'Disconnect', 'event-tickets' ); ?>
		</a>
	</div>
</div>
