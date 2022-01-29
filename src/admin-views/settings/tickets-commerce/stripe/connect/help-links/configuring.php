<?php
/**
 * The Template for displaying the Tickets Commerce Stripe help links (configuring).
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

if ( false === $merchant_status['connected'] ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-stripe-help-link">
	<?php $this->template( 'components/icons/lightbulb' ); ?>
	<a
		href="https://evnt.is/1axt"
		target="_blank"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-tickets-commerce-stripe-help-link-url"
	><?php esc_html_e( 'Learn more about configuring Stripe payments', 'event-tickets' ); ?></a>
</div>
