<?php
/**
 * The template used to render the Seats Report tab.
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 */

use Tribe__Tickets__Main as Tickets_Main;

$main = Tickets_Main::instance();
?>

<div class="tec-admin__upsell-container">
	<div class="tec-admin__upsell-banner">
		<div class="tec-admin__upsell-banner-content">
			<div class="tec-admin__upsell-banner-header">
				<img
					src="<?php echo esc_url( tribe_resource_url( 'icons/seating-icon.svg', false, null, $main ) ); ?>"
					class="tec-admin__upsell-banner-logo"
					role="presentation"
					alt="<?php esc_attr_e( 'Seating Icon', 'event-tickets' ); ?>"
				/>
				<h3 class="tec-admin__upsell-banner-title">
					<?php esc_html_e( 'Seating', 'event-tickets' ); ?>
				</h3>
			</div>
			<p>
				<?php esc_html_e( 'Maximize event revenue and improve the attendee experience by adding assigned seats with this innovative add-on.', 'event-tickets' ); ?>
			</p>
			<a href="https://evnt.is/get-seating" class="tec-admin__upsell-banner-btn" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Check out Seating for Event Tickets', 'event-tickets' ); ?>
			</a>
		</div>
		<img
			class="tec-admin__upsell-banner-image"
			src="<?php echo esc_url( tribe_resource_url( 'icons/seating-banner.png', false, null, $main ) ); ?>"
			alt="<?php esc_attr_e( 'Seating Banner Icon', 'event-tickets' ); ?>"
		/>
	</div>
</div>

