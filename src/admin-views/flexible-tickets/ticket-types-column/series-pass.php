<?php
/**
 * Series pass template.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var boolean $has_series_pass Whether the event has Series Pass enabled.
 * @var Tribe__Tickets__Admin__Views $admin_views The admin views instance for flexible tickets.
 */

if ( empty( $has_series_pass ) ) {
	return;
}
?>
<div class="tec-tickets__series_attached_ticket_types__label">
	<?php
		$admin_views->template( 'series-pass-icon' );
		echo esc_html( tec_tickets_get_series_pass_singular_uppercase() );
	?>
</div>