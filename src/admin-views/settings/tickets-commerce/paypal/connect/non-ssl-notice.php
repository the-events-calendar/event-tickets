<?php
/**
 * The Template for displaying the Tickets Commerce PayPal Settings notice when `https` is not being used.
 *
 * @version 5.6.4
 *
 * @since 5.6.4
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 */

defined( 'ABSPATH' ) || exit;
if ( is_ssl() ) {
	return;
}

$link_element = sprintf(
	'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
	esc_url( 'https://evnt.is/1axw' ),
	esc_html__( 'Learn more', 'event-tickets' )
);

$notice = sprintf(
	// Translators: %s: link to Knowledgebase article.
	esc_html__( 'A valid SSL certificate and secure (https) connection are required to connect with PayPal. %s', 'event-tickets' ),
	$link_element
);

?>
<div class="event-tickets">
	<div id="tec-tickets__admin-settings-tickets-commerce-gateway-modal-notice-error" class="tribe-tickets__notice tribe-tickets__notice--error tec-tickets__admin-settings-tickets-commerce-gateway-modal-notice-error">
		<div class="tribe-common-b2 tribe-tickets-notice__content">
			<h4 class="tribe-tickets-notice__title">
				<?php esc_html_e( 'PayPal connection unavailable', 'event-tickets' ); ?>
			</h4>
			<div  class="tribe-tickets-notice__message">
				<?php
					echo wp_kses_post( $notice );
				?>
			</div>
		</div>
	</div>
</div>
<?php
