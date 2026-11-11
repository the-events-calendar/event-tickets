<?php
/**
 * The Template for displaying the Tickets Commerce Square help links.
 *
 * @since 5.24.0
 * @since 5.31.1 Removed the troubleshooting link.
 *
 * @version 5.31.1
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Square\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Square\Merchant $merchant        [Global] The Signup class.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway-help-links">

	<?php $this->template( 'settings/tickets-commerce/square/connect/help-links/configuring' ); ?>

</div>
