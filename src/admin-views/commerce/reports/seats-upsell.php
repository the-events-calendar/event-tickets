<?php
/**
 * The template used to render the Seats Report tab.
 *
 * @since TBD
 *
 * @version TBD
 */

use Tribe__Tickets__Main as Tickets_Main;

$main = Tickets_Main::instance();
?>
<div class="tec-tickets-seating-upsell">
	<div class="tec-tickets-seating-upsell__content">
		<div class="tec-tickets-seating-upsell__title">
			<img
				src="<?php echo esc_url( tribe_resource_url( 'icons/seating-icon.svg', false, null, $main ) ); ?>"
				alt="<?php esc_attr_e( 'Seating Icon', 'event-tickets' ); ?>"
			>
			<h3>
				<?php esc_html_e( 'Seating', 'event-tickets' ); ?>
			</h3>
		</div>
		<p>
			<?php esc_html_e( 'Maximize event revenue and improve the attendee experience by adding assigned seats with this innovative add-on.', 'event-tickets' ); ?>
		</p>
		<div class="tec-tickets-seating-upsell__btn">
			<a href="https://evnt.is/get-seating" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Check out Seating for Event Tickets', 'event-tickets' ); ?>
			</a>
		</div>
	</div>

	<div class="tec-tickets-seating-upsell__icon">
		<img
			src="<?php echo esc_url( tribe_resource_url( 'icons/seating-banner.png', false, null, $main ) ); ?>"
			alt="<?php esc_attr_e( 'Seating Banner Icon', 'event-tickets' ); ?>"
		>
	</div>
</div>
