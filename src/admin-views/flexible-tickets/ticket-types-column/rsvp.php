<?php
/**
 * RSVP type template.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 *
 * @var array $tickets Array of RSVP tickets.
 */

if ( empty( $tickets ) ) {
	return;
}
?>
<div class="tec-tickets__series_attached_ticket-type">
	<div class="tec-tickets__series_attached_ticket-type__icon tec-tickets__series_attached_ticket-type__icon--rsvp"></div>
	<div class="tickets__series_attached_ticket-type__title">
		<?php
			echo esc_html( tribe_get_rsvp_label_singular() );
		?>
	</div>
</div>