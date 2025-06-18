<?php
/**
 * The Template for displaying the Square Sandbox mode notice.
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 *
 * @var Tribe__Tickets__Admin__Views $this [Global] Template object.
 */

defined( 'ABSPATH' ) || exit;
$test_mode = TEC\Tickets\Commerce\Gateways\Square\Gateway::is_test_mode();

if ( ! $test_mode ) {
	return;
}
?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-notice-message" id="square-sandbox-notice-message">
	<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
	<span>
	<?php
	echo wp_kses(
		sprintf(
			/* translators: %1$s: opening link tag, %2$s: closing link tag */
			__( 'You are in Sandbox mode. Before connecting, you need to %1$sopen your Square Sandbox Dashboard%2$s first to ensure proper authentication.', 'event-tickets' ),
			'<a href="https://developer.squareup.com/console/en/sandbox-test-accounts" target="_blank" rel="noopener noreferrer">',
			'</a>'
		),
		[
			'a' => [
				'href'   => [],
				'target' => [],
				'rel'    => [],
			],
		]
	);
	?>
	</span>
</div>
<?php
