<?php
/**
 * Block: RSVP V2 Kitchen sink
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-kitchen-sink.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 */
?>

<div class="tribe-common event-tickets">

	<?php
	// Default state.
	$this->template( 'v2/rsvp-kitchen-sink/default', [] );

	// Default full.
	$this->template( 'v2/rsvp-kitchen-sink/default-full', [] );

	// Default unlimited.
	$this->template( 'v2/rsvp-kitchen-sink/default-unlimited', [] );

	// Default without description.
	$this->template( 'v2/rsvp-kitchen-sink/default-no-description', [] );

	// Default must login.
	$this->template( 'v2/rsvp-kitchen-sink/default-must-login', [] );

	// Success.
	$this->template( 'v2/rsvp-kitchen-sink/success', [] );

	// Going form.
	$this->template( 'v2/rsvp-kitchen-sink/form-going', [] );

	// Not going form.
	$this->template( 'v2/rsvp-kitchen-sink/form-not-going', [] );

	// Attendee Registration.
	$this->template( 'v2/rsvp-kitchen-sink/ari', [] );
	?>

</div>
