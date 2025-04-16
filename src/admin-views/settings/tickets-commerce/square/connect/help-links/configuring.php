<?php
/**
 * The Template for displaying the Tickets Commerce Square help links (configuring).
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Square\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Square\Merchant $merchant        [Global] The Signup class.
 */

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-help-link">
	<?php $this->template( 'components/icons/lightbulb' ); ?>
	<!-- @todo: We need to update this link. -->
	<a
		href="https://evnt.is/1axt"
		target="_blank"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-tickets-commerce-gateway-help-link-url"
	><?php esc_html_e( 'Learn more about configuring Square payments', 'event-tickets' ); ?></a>
</div>
