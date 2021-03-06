<?php
/**
 * Block: RSVP form not going.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-kitchen-sink/form-not-going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since 4.12.3
 * @since 5.0.0 Updated the placeholder text used.
 *
 * @version 5.0.0
 */
?>
<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="51">





<form name="tribe-rsvp-form" data-product-id="51">
	<input type="hidden" name="product_id[]" value="51">
	<input type="hidden" name="attendee[order_status]" value="not-going">

	<div class="tribe-tickets__rsvp-form-wrapper">

		<div class="tribe-tickets__rsvp-form-title">
	<h3 class="tribe-common-h5">
		Please submit your information even if you are unable to attend.	</h3>
</div>

		<div class="tribe-tickets__rsvp-form-content tribe-tickets__form">

			<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-rsvp-name">
		Name<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input type="text" id="tribe-tickets-rsvp-name" class="tribe-common-form-control-text__input tribe-tickets__form-field-input" name="attendee[full_name]" value="" required="" placeholder="Your Name">
</div>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-rsvp-email">
		Email<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input type="email" id="tribe-tickets-rsvp-email" class="tribe-common-form-control-text__input tribe-tickets__form-field-input" name="attendee[email]" value="" required="" placeholder="your@email.com">
</div>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="quantity_51">
		Number of Guests Not Attending<span class="screen-reader-text">(required)</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input type="number" name="quantity_51" class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-input-number" value="1" required="" min="1" max="40">
</div>

			<div class="tribe-tickets__rsvp-form-buttons">
	<button class="tribe-common-h7 tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--cancel" type="reset">
	Cancel</button>
	<button class="tribe-common-c-btn tribe-tickets__rsvp-form-button" type="submit">
	Finish</button>
</div>

		</div>

	</div>

</form>


			</div>
