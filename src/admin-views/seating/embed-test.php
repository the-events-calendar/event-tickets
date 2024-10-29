<?php
/**
 * The Embed Test page.
 *
 * @since 5.16.0
 *
 * @var TEC\Events_Assigned_Seating\Admin\Embed_Test $controller The controller object.
 * @var string $iframe_url The URL to the service iframe.
 * @var string $token The ephemeral token used to secure the iframe communication with the service.
 * @var string $error The error message returned by the service.
 * @var string $route The route to the service.
 */

?>
<div class="tec-tickets__seating-tab-wrapper wrap">
	<div id="tec-tickets-seating-notice" class="notice" style="display: none;"></div>
	<div
		class="tec-tickets-seating__iframe-container"
		data-token="<?php echo esc_attr( $token ); ?>"
		data-error="<?php echo esc_attr( $error ); ?>"
		data-route="<?php echo esc_attr( $route ); ?>"
	>
		<iframe
			data-src="<?php echo esc_url( $iframe_url ); ?>"
			id="tec-tickets-seating-iframe-embed-test"
			class="tec-tickets-seating__iframe tec-tickets-seating__iframe--embed-test"
			title="<?php esc_html_e( 'Embed Test', 'event-tickets' ); ?>"></iframe>
	</div>
</div>
