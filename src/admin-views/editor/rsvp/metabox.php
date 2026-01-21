<?php
/**
 * @var Tribe__Tickets__Admin__Views  $admin_views A reference to the admin views object.
 * @var Tribe__Tickets__Ticket_Object $tc_rsvp The TC RSVP object.
 * @var WP_Post                       $post The post context of the metabox rendering
 * @var int                           $post_id The ID of the post context of the metabox rendering
 * @var int                           $rsvp_id The ID of the RSVP ticket, if any.
 *
 * @since TBD
 */

defined( 'ABSPATH' ) || die();
$switch_classes = [
	'tec-tickets-rsvp-switch__wrap',
	! empty( $tc_rsvp->ID ) ? 'tribe-common-a11y-hidden' : '',
];
?>

<div id="tec_tickets_rsvp_metabox" class="eventtable tec-event-tickets-from__wrap tribe-common" aria-live="polite">
	<?php
		$admin_views->template( 'components/loader' );
	?>
	<h4 class="tribe-dependent" data-depends="#rsvp_id" data-condition-not-empty>
		<?php $admin_views->template( 'components/icons/mail', [ 'classes' => '' ] ); ?>
		<?php echo esc_html_x( 'RSVP active', 'Status message indicating RSVP is currently active for an event', 'event-tickets' ); ?>
	</h4>
	<div <?php tec_classes( $switch_classes ); ?>>
		<?php
		$admin_views->template(
			[ 'components', 'switch-field' ],
			[
				'id'          => 'tec_tickets_rsvp_enable',
				'name'        => 'tec_tickets_rsvp_enable',
				'label'       => esc_html_x( 'Enable RSVP', 'Label for the toggle switch to enable RSVP functionality in the metabox.', 'event-tickets' ),
				'description' => esc_html_x( 'Allow users to register as attendees for this event', 'Description text explaining what happens when RSVP is enabled for an event.', 'event-tickets' ),
				'tooltip'     => '',
				'value'       => ! empty( $tc_rsvp->ID ),
			]
		);
		?>
	</div>

	<?php $admin_views->template( [ 'editor', 'rsvp', 'panel', 'rsvp' ], get_defined_vars() ); ?>

</div>
