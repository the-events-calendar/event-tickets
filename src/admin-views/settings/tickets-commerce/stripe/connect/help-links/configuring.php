<?php
/**
 * The Template for displaying the Tickets Commerce Stripe help links (configuring).
 *
 * @version TBD
 *
 * @since   TBD
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 */

if ( ! empty( $is_merchant_connected ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-stripe-help-link">
	<?php $this->template( 'components/icons/lightbulb' ); ?>
	<a
		href="https://evnt.is/1axt" <!-- @todo: We need to update this link. -->
		target="_blank"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-tickets-commerce-stripe-help-link-url"
	><?php esc_html_e( 'Learn more about configuring Stripe payments', 'event-tickets' ); ?></a>
</div>
