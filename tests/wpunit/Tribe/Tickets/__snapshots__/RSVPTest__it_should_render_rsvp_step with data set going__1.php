<?php return '<div  class="tribe-tickets-loader__dots tribe-common-c-loader tribe-common-a11y-hidden" >
	<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--first"></div>
	<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--second"></div>
	<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--third"></div>
</div>



	
<form
	name="tribe-rsvp-form"
	data-product-id="9"
>
	<input type="hidden" name="product_id[]" value="9">
	<input type="hidden" name="attendee[order_status]" value="going">

	<div class="tribe-tickets__rsvp-form-wrapper">

		<div class="tribe-tickets__rsvp-form-title">
	<h3 class="tribe-common-h5">
		Please submit your RSVP information, including the total number of guests.	</h3>
</div>
		<div class="tribe-tickets__rsvp-form-content tribe-tickets__form">

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
		id="tribe-tickets-rsvp-name"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input"
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
		id="tribe-tickets-rsvp-email"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input"
		name="attendee[email]"
		value=""
		required
		placeholder="your@email.com"
	>
</div>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="quantity_9"
	>
		Number of Guests<span class="screen-reader-text">(required)</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="number"
		name="quantity_9"
		name="quantity_9"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-form-input-number"
		value="1"
		required
		min="1"
		max="0"
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
