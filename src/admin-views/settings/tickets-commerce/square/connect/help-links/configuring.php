<?php
/**
 * The Template for displaying the Tickets Commerce Square help links (configuring).
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Square\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Square\Merchant $merchant        [Global] The Signup class.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-help-link">
	<?php $this->template( 'components/icons/lightbulb' ); ?>
	<a
		href="https://evnt.is/1be9"
		target="_blank"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-tickets-commerce-gateway-help-link-url"
	><?php esc_html_e( 'Learn more about configuring Square payments', 'event-tickets' ); ?></a>
</div>
<?php
