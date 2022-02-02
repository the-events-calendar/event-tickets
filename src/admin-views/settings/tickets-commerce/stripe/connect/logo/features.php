<?php
/**
 * The Template for displaying the Tickets Commerce Stripe features.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

?>
<ul>
	<li>
		<?php esc_html_e( 'Credit and debit card payments', 'event-tickets' ); ?>
	</li>
	<li>
		<?php esc_html_e( 'Easy no-API key connection', 'event-tickets' ); ?>
	</li>
	<li>
		<?php esc_html_e( 'Accept payments from around the world', 'event-tickets' ); ?>
	</li>
	<li>
		<?php esc_html_e( 'Supports 3D Secure payments', 'event-tickets' ); ?>
	</li>
</ul>
