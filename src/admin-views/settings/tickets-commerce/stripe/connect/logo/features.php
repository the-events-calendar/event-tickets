<?php
/**
 * The Template for displaying the Tickets Commerce Stripe features.
 *
 * @version 5.3.0
 *
 * @since 5.3.0
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

?>
<ul>
	<li>
		<?php esc_html_e( 'Credit, debit card payments and more!', 'event-tickets' ); ?>
	</li>
	<li>
		<?php esc_html_e( 'Easy, streamlined connection', 'event-tickets' ); ?>
	</li>
	<li>
		<?php esc_html_e( 'Accept payments from around the world', 'event-tickets' ); ?>
	</li>
	<li>
		<?php esc_html_e( 'Supports 3D Secure payments', 'event-tickets' ); ?>
	</li>
</ul>
