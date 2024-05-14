<?php
/**
 * Seating iFrame view template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/iframe-view.php
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string $iframe_url The URL to the service iframe.
 * @var string $token      The ephemeral token used to secure the iframe communication with the service.
 * @var string $error      The error message returned by the service.
 */
?>

<div
	class="tec-tickets-seating__iframe-container"
	data-token="<?php echo esc_attr( $token ); ?>"
	data-error="<?php echo esc_attr( $error ); ?>"
>
	<iframe
		data-src="<?php echo esc_url( $iframe_url ); ?>"
		id="tec-tickets-seating-iframe-tickets-block"
		class="tec-tickets-seating__iframe tec-tickets-seating__iframe--tickets-block"
		title="<?php esc_html_e( 'Seat selection', 'event-tickets' ); ?>"
	>
	</iframe>
</div>
