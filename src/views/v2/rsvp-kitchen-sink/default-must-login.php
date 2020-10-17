<?php
/**
 * Block: RSVP default must login
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-kitchen-sink/default-must-login.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   4.12.3
 *
 * @version 4.12.3
 *
 * @var Tribe__Tickets__Editor__Template $this                Template object.
 * @var int                              $post_id             [Global] The current Post ID to which RSVPs are attached.
 * @var array                            $attributes          [Global] RSVP attributes (could be empty).
 * @var Tribe__Tickets__Ticket_Object[]  $active_rsvps        [Global] List of RSVPs.
 * @var bool                             $all_past            [Global] True if RSVPs availability dates are all in the past.
 * @var bool                             $has_rsvps           [Global] True if the event has any RSVPs.
 * @var bool                             $has_active_rsvps    [Global] True if the event has any RSVPs available.
 * @var bool                             $must_login          [Global] True if only logged-in users may obtain RSVPs.
 * @var string                           $login_url           [Global] The site's login URL.
 * @var int                              $threshold           [Global] The count at which "number of tickets left" message appears.
 * @var null|string                      $step                [Global] The point we're at in the loading process.
 * @var bool                             $opt_in_checked      [Global] Whether appearing in Attendee List was checked.
 * @var string                           $opt_in_attendee_ids [Global] The list of attendee IDs to send in the form submission.
 * @var string                           $opt_in_nonce        [Global] The nonce for opt-in AJAX requests.
 * @var bool                             $doing_shortcode     [Global] True if detected within context of shortcode output.
 * @var bool                             $block_html_id       [Global] The RSVP block HTML ID. $doing_shortcode may alter it.
 * @var Tribe__Tickets__Ticket_Object    $rsvp                The rsvp ticket object.
 */
?>
<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="16">

<div class="tribe-tickets__rsvp-message tribe-tickets__rsvp-message--must-login tribe-common-b3">
	<svg class="tribe-tickets-svgicon tribe-tickets__rsvp-message--must-login-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"><g fill="none" fill-rule="evenodd" transform="translate(1 1)"><circle cx="8" cy="8" r="7.467" stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></circle><circle id="dot" cx="8" cy="11.733" r="1.067" fill="#141827" fill-rule="nonzero"></circle><path stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3.733v4.8"></path></g></svg>
	<span class="tribe-tickets__rsvp-message-text">
		<strong>
			You must be logged in to RSVP.
			<a href="http://localhost:10025/wp-login.php?redirect_to=http://localhost:10025/event/rsvp-test/?tribe-tickets__rsvp16" class="tribe-tickets__rsvp-message-link">
				Log in here			</a>
		</strong>
	</span>
</div>



	<div class="tribe-tickets__rsvp tribe-common-g-row tribe-common-g-row--gutters">

		<div class="tribe-tickets__rsvp-details-wrapper tribe-common-g-col">
	<div class="tribe-tickets__rsvp-details">
		<h3 class="tribe-tickets__rsvp-title tribe-common-h4">
	Job &amp; Career Fair</h3>

		<div class="tribe-tickets__rsvp-description tribe-common-b3">
	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
</div>

		<div class="tribe-tickets__rsvp-attendance">
	<span class="tribe-tickets__rsvp-attendance-number tribe-common-h4">
		0	</span>
	<span class="tribe-tickets__rsvp-attendance-going tribe-common-b3">
		Going	</span>
</div>

		<div class="tribe-tickets__rsvp-availability tribe-common-b3">
			<span class="tribe-tickets__rsvp-availability-quantity tribe-common-b2--bold"> 100 </span> remaining,
	<span class="tribe-tickets__rsvp-availability-days-left tribe-common-b2--bold"> 48 </span> days left to RSVP
</div>
	</div>
</div>

		<div class="tribe-tickets__rsvp-actions-wrapper tribe-common-g-col">
	<div class="tribe-tickets__rsvp-actions">


			<div class="tribe-tickets__rsvp-actions-rsvp">
	<span class="tribe-common-h6">
		RSVP Here	</span>


<div class="tribe-tickets__rsvp-actions-rsvp-going">
	<button class="tribe-common-c-btn tribe-tickets__rsvp-actions-button-going" type="submit" disabled="" aria-disabled="true">
		Going	</button>
</div>

	<div class="tribe-tickets__rsvp-actions-rsvp-not-going">
	<button class="tribe-common-cta tribe-common-cta--alt" disabled="" aria-disabled="true">
		Can't go	</button>
</div>

</div>

			</div>

</div>

	</div>

			</div>