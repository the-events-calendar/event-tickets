<?php
/**
 * @var Tribe__Tickets__Admin__Views  $admin_views
 * @var Tribe__Tickets__Ticket_Object $tc_rsvp The TC RSVP object.
 * @var WP_Post                       $post
 * @var int                           $post_id
 * @var int                           $rsvp_id
 */

?>

<div id="tec_tickets_rsvp_metabox" class="eventtable tec-event-tickets-from__wrap tribe-common" aria-live="polite">
	<?php
		$admin_views->template( 'components/loader' );

		$admin_views->template(
			[ 'components', 'switch-field' ],
			[
				'id'          => 'tec_tickets_rsvp_enable',
				'name'        => 'tec_tickets_rsvp_enable',
				'label'       => esc_html_x( 'Enable RSVP', 'Label for the toggle switch to enable RSVP functionality in the metabox.', 'event-tickets' ),
				'description' => esc_html_x( 'Allow users to register as attendees for this event', 'Description text explaining what happens when RSVP is enabled for an event.', 'event-tickets' ),
				'tooltip'     => '',
				'value'       => ! empty( $tc_rsvp->ID )
			]
		);
	?>

	<?php $admin_views->template( [ 'editor', 'rsvp', 'panel', 'rsvp' ], get_defined_vars() ); ?>

</div>
