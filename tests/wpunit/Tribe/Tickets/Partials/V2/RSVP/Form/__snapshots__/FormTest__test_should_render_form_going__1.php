<?php return '
<form
	name="tribe-tickets-rsvp-form"
	data-rsvp-id="69"
>
	<input type="hidden" name="product_id[]" value="69">
	<input type="hidden" name="attendee[order_status]" value="going">

	<div class="tribe-tickets__rsvp-form-wrapper">

		<div class="tribe-tickets__rsvp-form-title">
	<h3 class="tribe-common-h5">
		Please submit your RSVP information, including the total number of guests.	</h3>
</div>
		<div class="tribe-tickets__rsvp-form-content tribe-tickets__form">

			<div class="tribe-tickets__form-message tribe-tickets__form-message--error tribe-common-b3 tribe-common-a11y-hidden">
	<svg  class="tribe-tickets-svgicon tribe-tickets__form-message--error-icon"  xmlns="http://www.w3.org/2000/svg" width="18" height="18"><g fill="none" fill-rule="evenodd" transform="translate(1 1)"><circle cx="8" cy="8" r="7.467" stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><circle id="dot" cx="8" cy="11.733" r="1.067" fill="#141827" fill-rule="nonzero"/><path stroke="#141827" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3.733v4.8"/></g></svg>	<span class="tribe-tickets__form-message-text">
		<strong>
			Whoops		</strong>
		<p>There is a field that requires information.</p>
	</span>
</div>

			<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-name"
	>
		Name<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="text"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-field-name"
		name="attendee[full_name]"
		value=""
		required
		placeholder="John Doe"
	>
</div>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-email"
	>
		Email<span class="screen-reader-text">required</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="email"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-field-email"
		name="attendee[email]"
		value=""
		required
		placeholder="your@email.com"
	>
</div>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="quantity_69"
	>
		Number of Guests<span class="screen-reader-text">(required)</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="number"
		name="quantity_69"
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
