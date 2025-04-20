<?php
/**
 * The Template for displaying the Tickets Commerce Square Webhook Status.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Admin__Views $this [Global] Template object.
 */

defined( 'ABSPATH' ) || exit;

$webhook_id = tribe_get_option( \TEC\Tickets\Commerce\Gateways\Square\Webhooks::$option_webhook_id );
$webhook_status = tribe_get_option( \TEC\Tickets\Commerce\Gateways\Square\Webhooks::$option_webhook_last_check, [] );
$is_healthy = ! empty( $webhook_status['is_healthy'] ) && $webhook_status['is_healthy'];
?>
<!-- Webhook Status -->
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-webhook-label">
		<?php esc_html_e( 'Webhooks:', 'event-tickets' ); ?>
	</span>
	<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-webhook-label">
		<?php if ( empty( $webhook_id ) ) : ?>
			<span class="dashicons dashicons-warning tec-tickets__admin-settings-square-webhook-warning" aria-hidden="true"></span>
			<?php esc_html_e( 'Not Registered', 'event-tickets' ); ?>
		<?php elseif ( $is_healthy ) : ?>
			<span class="dashicons dashicons-yes" aria-hidden="true"></span>
			<?php esc_html_e( 'Active', 'event-tickets' ); ?>
		<?php else : ?>
			<span class="dashicons dashicons-warning tec-tickets__admin-settings-square-webhook-warning" aria-hidden="true"></span>
			<?php esc_html_e( 'Not Functioning', 'event-tickets' ); ?>
		<?php endif; ?>
		<?php if ( ! empty( $webhook_status['last_checked'] ) ) : ?>
			<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-subtext">
				<?php
				printf(
					/* translators: %s: human readable time difference */
					esc_html__( 'Last checked: %s ago', 'event-tickets' ),
					esc_html( human_time_diff( $webhook_status['last_checked'], time() ) )
				);
				?>
			</span>
		<?php endif; ?>
	</span>
</div>
