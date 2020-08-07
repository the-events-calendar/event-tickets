<?php return '
<form
	name="tribe-tickets-rsvp-form"
	data-rsvp-id="96"
>
	<input type="hidden" name="tribe_tickets[96][ticket_id]" value="96">
	<input type="hidden" name="tribe_tickets[96][attendees][0][order_status]" value="going">

	<div class="tribe-tickets__rsvp-form-wrapper">

		<div class="tribe-tickets__rsvp-form-title">
	<h3 class="tribe-common-h5">
		Please submit your RSVP information, including the total number of guests.	</h3>
</div>
		<div class="tribe-tickets__rsvp-form-content tribe-tickets__form">

			<div class="tribe-common-b1 tribe-common-b2--min-medium tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-name-96"
	>
		Name<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="text"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-field-name"
		name="tribe_tickets[96][attendees][0][full_name]"
		id="tribe-tickets-rsvp-name-96"
		value=""
		required
		placeholder="Your Name"
	>
</div>
<div class="tribe-common-b1 tribe-common-b2--min-medium tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-email-96"
	>
		Email<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="email"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-field-email"
		name="tribe_tickets[96][attendees][0][email]"
		id="tribe-tickets-rsvp-email-96"
		value=""
		required
		placeholder="your@email.com"
	>
</div>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="quantity_96"
	>
		Number of Guests<span class="screen-reader-text">(required)</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="number"
		name="tribe_tickets[96][quantity]"
		id="quantity_96"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-input-number tribe-tickets__rsvp-form-field-quantity"
		value="1"
		required
		min="1"
		max="5"
	>
</div>

			<div class="tribe-tickets__rsvp-form-buttons">
	<button
	class="tribe-common-h7 tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--cancel"
	type="reset"
>
	Cancel</button>
	<button
	class="tribe-common-c-btn tribe-tickets__rsvp-form-button"
	type="submit"
>
	Finish</button>
</div>

		</div>

	</div>

</form>
';
