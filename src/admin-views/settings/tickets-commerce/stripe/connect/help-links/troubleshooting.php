<?php
/**
 * The Template for displaying the Tickets Commerce Stripe help links (troubleshooting).
 *
 * @since   5.3.0
 *
 * @version 5.3.0
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */
?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-help-link">
	<?php $this->template( 'components/icons/lightbulb' ); ?>
	<!-- @todo: We need to update this link. -->
	<a
		href="https://evnt.is/1axw"
		target="_blank"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-tickets-commerce-gateway-help-link-url"
	><?php esc_html_e( 'Get troubleshooting help', 'event-tickets' ); ?></a>
</div>
