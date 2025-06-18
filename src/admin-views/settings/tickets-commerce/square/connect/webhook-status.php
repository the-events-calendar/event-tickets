<?php
/**
 * The Template for displaying the Tickets Commerce Square Webhook Status.
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 *
 * @var Tribe__Tickets__Admin__Views $this [Global] Template object.
 */

use TEC\Tickets\Commerce\Gateways\Square\Webhooks;
use Tribe__Date_Utils as Dates;

defined( 'ABSPATH' ) || exit;

$webhooks = tribe( Webhooks::class );

$webhook_id    = $webhooks->get_webhook_id();
$is_healthy    = $webhooks->is_webhook_healthy();
$is_expired    = $webhooks->is_webhook_expired();
$fetched_date  = $webhooks->get_fetched_date();
$webhook_nonce = wp_create_nonce( 'square-webhook-register' );
?>
<!-- Webhook Status -->
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-webhook-label">
		<?php esc_html_e( 'Webhooks:', 'event-tickets' ); ?>
	</span>
	<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-webhook-label">
		<span class="tec-tickets__admin-settings-square-webhook-status">
			<?php if ( empty( $webhook_id ) ) : ?>
				<span class="dashicons dashicons-warning tec-tickets__admin-settings-square-webhook-warning" aria-hidden="true"></span>
				<?php esc_html_e( 'Not Registered', 'event-tickets' ); ?>
			<?php elseif ( $is_healthy ) : ?>
				<span class="dashicons dashicons-yes" aria-hidden="true"></span>
				<?php esc_html_e( 'Active', 'event-tickets' ); ?>
			<?php elseif ( $is_expired ) : ?>
				<span class="dashicons dashicons-warning tec-tickets__admin-settings-square-webhook-warning" aria-hidden="true"></span>
				<?php esc_html_e( 'Expired', 'event-tickets' ); ?>
			<?php else : ?>
				<span class="dashicons dashicons-warning tec-tickets__admin-settings-square-webhook-warning" aria-hidden="true"></span>
				<?php esc_html_e( 'Not Functioning', 'event-tickets' ); ?>
			<?php endif; ?>
		</span>
		<?php if ( ! $is_healthy || $is_expired ) : ?>
			<button
				type="button"
				class="button button-secondary tec-tickets__admin-settings-square-webhook-register-button"
				id="tec-tickets__admin-settings-square-webhook-register"
				data-nonce="<?php echo esc_attr( $webhook_nonce ); ?>"
				<?php if ( $is_healthy || $is_expired ) : ?>
				aria-label="<?php esc_attr_e( 'Reregister Square webhooks', 'event-tickets' ); ?>"
				<?php else : ?>
				aria-label="<?php esc_attr_e( 'Register Square webhooks', 'event-tickets' ); ?>"
				<?php endif; ?>
			>
				<?php esc_html_e( 'Re-connect', 'event-tickets' ); ?>
			</button>
			<span class="spinner tec-tickets__admin-settings-square-webhook-spinner"></span>
		<?php endif; ?>
	</span>
</div>
<?php if ( $is_healthy && $fetched_date ) : ?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="last-connection-label">
		<?php esc_html_e( 'Last Connection:', 'event-tickets' ); ?>
	</span>
	<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="last-connection-label">
		<abbr title="<?php echo esc_attr( $fetched_date->format( Dates::DBDATETIMEFORMAT ) ); ?>">
			<?php echo esc_html( human_time_diff( $fetched_date->getTimestamp(), time() ) ); ?>
		</abbr>
	</span>
</div>
<?php endif; ?>
