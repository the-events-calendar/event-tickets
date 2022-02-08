<?php
/**
 * Template to display a list of gateway options.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this     Template object.
 * @var array                        $gateways Array of gateway objects.
 */

$manager = tribe( TEC\Tickets\Commerce\Gateways\Manager::class );

$brand_info = [
	'paypal' => [
		'logo_url' =>  Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/admin/paypal_logo.png',
		'subtitle' => esc_html__( 'Enable payments through PayPal, Venmo, and credit card', 'event-tickets' ),
	],
	'stripe' => [
		'logo_url' =>  Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/admin/stripe-logo.png',
		'subtitle' => esc_html__( 'Enable credit card payments, Afterpay, AliPay, Giropay, Klarna and more.', 'event-tickets' ),
	],
];

$gateways = $manager->get_gateways();
?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways">
	<?php
	foreach ( $gateways as $gateway ) :
		if ( ! $gateway::should_show() ) {
			continue;
		}
		$key = $gateway->get_key();
		$button_url = \Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-section' => $key ] );
		?>
		<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item">
			<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-toggle">
				<label class="tec-tickets__admin-settings-tickets-commerce-toggle">
					<input
						type="checkbox"
						disabled="disabled"
						name="tickets_commerce_enabled"
						<?php checked( $manager->is_gateway_enabled( $gateway ), true ); ?>
						id="tickets-commerce-enable-input"
						class="tec-tickets__admin-settings-tickets-commerce-toggle-checkbox">
					<span class="tec-tickets__admin-settings-tickets-commerce-toggle-switch"></span>
				</label>
			</div>
			<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand">
				<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-logo">
					<img src="<?php echo esc_attr( $brand_info[$key]['logo_url'] ); ?>" alt="<?php echo esc_attr( $gateway->get_label() ); ?>" />
				</div>
				<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-subtitle">
					<?php echo esc_html( $brand_info[ $key ]['subtitle'] ); ?>
				</div>
			</div>
			<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button">
				<a href="<?php echo esc_url( $button_url ); ?>">Connect to <?php echo esc_html( $gateway->get_label() ); ?></a>
			</div>
		</div>
		<?php
	endforeach;
	?>
</div>
