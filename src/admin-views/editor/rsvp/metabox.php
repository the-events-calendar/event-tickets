<?php
/**
 * @var Tribe__Tickets__Admin__Views  $admin_views
 * @var Tribe__Tickets__Ticket_Object $tc_rsvp The TC RSVP object.
 * @var WP_Post                       $post
 * @var int                           $post_id
 * @var int                           $rsvp_id
 */

?>

<div class="tribe-tickets-editor-blocker">
	<span class="spinner"></span>
</div>

<div id="tec_tickets_rsvp_metabox" class="eventtable tec-event-tickets-from__wrap" aria-live="polite">
	<?php
		$admin_views->template(
			[ 'components', 'switch-field' ],
			[
				'id'      => 'tec_tickets_rsvp_enable',
				'name'    => 'tec_tickets_rsvp_enable',
				'label'   => 'Enable RSVP ',
				'tooltip' => 'Allow users to register as attendees for this event',
				'value'   => ! empty( $tc_rsvp->ID )
			]
		);
	?>

	<?php $admin_views->template( [ 'editor', 'rsvp', 'panel', 'rsvp' ], get_defined_vars() ); ?>
</div>
