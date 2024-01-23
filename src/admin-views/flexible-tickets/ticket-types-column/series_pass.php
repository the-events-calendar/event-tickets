<?php
/**
 * Series pass template.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 *
 * @var array $tickets Array of Series-pass tickets.
 */

if ( empty( $tickets ) ) {
	return;
}
?>
<div class="tec-tickets__series_attached_ticket-type">
	<div class="tec-tickets__series_attached_ticket-type__icon tec-tickets__series_attached_ticket-type__icon--series-pass"></div>
	<div class="tickets__series_attached_ticket-type__title">
		<?php
			echo esc_html( tec_tickets_get_series_pass_singular_uppercase() );
		?>
	</div>
</div>